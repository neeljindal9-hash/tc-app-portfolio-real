<?php
# project/classes/Repositories/ConversationRepository.php

class ConversationRepository {


    // read messages
    public static function fetchMessages(int $par_id, ?string $structure, DatabaseConnection $db): array {
        // must be first layer for now
        return self::fetchMessagesByPath((string)$par_id, $structure, $db);
    }

    private static function fetchMessagesByPath(string $path, ?string $structure, DatabaseConnection $db): array {
        
        if ($structure === null) {
            return $db->fetchAll(
                "SELECT
                    messages.coalesced_par_id AS parent_id,
                    messages.ID AS message_id,
                    messages.data AS message_data,
                    users.name AS author_name,
                    users.public_flags,
                    messages.created_at,
                    messages.convo_id,
                    messages.path,
                    messages.structure,
                    messages.updated_last AS children_updated_last
                FROM messages
                LEFT JOIN users ON messages.user_id = users.ID 
                WHERE messages.path = ?
                ORDER BY messages.ID",
                [$path]
            );
        }
        return $db->fetchAll(
            "SELECT
                messages.coalesced_par_id AS parent_id,
                messages.ID AS message_id,
                messages.data AS message_data, 
                users.name AS author_name, 
                users.public_flags,
                messages.created_at,
                messages.convo_id,
                messages.isAnonymous,
                messages.path,
                messages.structure,
                messages.updated_last AS children_updated_last
            FROM messages 
            LEFT JOIN users ON messages.user_id = users.ID 
            WHERE messages.path = ? AND messages.structure = ?
            ORDER BY messages.ID",
            [$path, $structure]
        );
    }

    public static function fetchAllMessages(int $par_id, int $convo_id, DatabaseConnection $db): array {
        // PRECONDITION - ONLY SUPPORTS TWO LAYER PATHS
        $path = $convo_id . "-" . $par_id;
        return self::fetchAllMessagesByPath($path, $convo_id, $db);
    }

    private static function fetchAllMessagesByPath(string $path, int $convoId, DatabaseConnection $db): array {
        
        return $db->fetchAll(
                "SELECT
                    messages.coalesced_par_id AS parent_id,
                    messages.ID AS message_id,
                    messages.data AS message_data,
                    users.name AS author_name,
                    messages.created_at,
                    users.public_flags,
                    messages.convo_id,
                    messages.path,
                    messages.isAnonymous,
                    messages.updated_last AS children_updated_last
                FROM messages
                LEFT JOIN users ON messages.user_id = users.ID 
                WHERE messages.path LIKE ? AND messages.convo_id = ?
                ORDER BY messages.ID",
                [($path . "%"), $convoId]
            );
    }

    private static function getRequestPathGivenParentID(int $par_id, DatabaseConnection $db): string {
        error_log("getRequestPath expired");
        if (self::isValidConversation($par_id)) {
            return (string)$par_id;
        }
        $response = $db->fetch("SELECT path FROM messages WHERE ID = ?", [$par_id]);

        return ($response['path'] . "-$par_id") ?? "";
    }

    public static function fetchSingleMessage(int $msgID, DatabaseConnection $db): array|false {
        return $db->fetch(
            'SELECT 
                messages.coalesced_par_id AS parent_id,
                messages.user_id AS author_id,
                messages.data AS message_data,
                users.name AS author_name,
                messages.convo_id,
                messages.path,
                users.public_flags,
                messages.created_at,
                messages.isAnonymous,
                messages.structure,
                messages.updated_last AS children_updated_last
            FROM messages
            LEFT JOIN users ON messages.user_id = users.ID
            WHERE messages.ID = ?',
            [$msgID]
        );
    }





    // read conversation information
    public static function fetchConversations(int $user_id, DatabaseConnection $db): array {
        return $db->fetchAll(
            "SELECT conversations.ID, conversations.name, conversations.access_mode 
            FROM conversations JOIN users_conversations ON users_conversations.convo_id = conversations.ID 
            WHERE users_conversations.user_id = ?",
            [$user_id]
        );
    }

    public static function getAllConversationNames(DatabaseConnection $db) {
        return $db->fetchAll('SELECT ID, name FROM conversations');
    }

    public static function getConvoInfo(int $convo_id, DatabaseConnection $db): array|false {
        return $db->fetch('SELECT * FROM conversations WHERE ID = ?', [$convo_id]);
    }

    public static function fetchUsers(int $convo_id, DatabaseConnection $db): array {
        return $db->fetchAll(
            "SELECT users.ID, users.name, users.warnings 
            FROM users 
            JOIN users_conversations ON users_conversations.user_id = users.ID 
            WHERE users_conversations.convo_id = ?", 
            [$convo_id]
        );
    }

    public static function getAbstractConvoInfoGivenCode(string $code, DatabaseConnection $db): array|false {
        $cID = self::getConvoIDGivenCode($code);

        if (!self::isValidConversation($cID)) {
            return false;
        }
        
        try {
            $resp = $db->fetch('SELECT ID, name FROM conversations WHERE ID = ?', [$cID]);
            return $resp;
        } catch(PDOException $e) {
            return false;
        }
    }





    







    // write messages
    public static function sendMessage(int $parent_id, int $user_id, int $convo_id, array $msgData, ?string $structure, bool $isAnonymous, int $watchLayer, bool $useTransaction, DatabaseConnection $db): array {

        // when transaction is disabled (strictly for admin use), the begin and commit transaction statements are conditional
        $parent = self::verifyAccessAndGetConvoID($user_id, $parent_id, $db);
        $returnToken = [];
        $returnToken['success'] = false;

        if (!$parent['access']) {
            error_log("Security Warning: User $user_id attempted to send ". implode(", ", array_values($msgData)). " with convo:$convo_id on parent:$parent_id.");
            $returnToken['error_msg'] = "Unauthorized";
            return $returnToken;
        }

        $accessInfo = self::writeEnabled($parent['convo_id'], $db);
        if (!$accessInfo['access']) {
            if ($accessInfo['status'] > 2) {
                error_log("User " . $user_id . " requested sendMessage to locked conversation...");
            }
            $returnToken['error_msg'] = "Write is currently disabled.";
            return $returnToken;
        }


        try {
            if ($useTransaction) {
                $db->beginTransaction();
            }
            $data = json_encode($msgData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (self::isValidConversation($parent_id)) {
                // if root message

                $structure ??= "other";

                $db->query(
                    "INSERT INTO messages (user_id, convo_id, data, structure, path, isAnonymous)
                    VALUES (?, ?, ?, ?, ?, ?)",
                    [$user_id, $parent_id, $data, $structure, $parent_id, (int)$isAnonymous]
                );
                $mid = $db->lastInsertId();

                $db->query(
                    "UPDATE conversations SET messages_updated = NOW() WHERE ID = ?",
                    [$parent_id]
                );

                $stmt2 = $db->query("UPDATE conversations SET plaza_state = plaza_state + 1 WHERE ID = ?",[$convo_id]);
                if ($stmt2->rowCount() !== 1) {
                    throw new RuntimeException("Update state failed...");
                }

                UserLobbyRepository::addPoints($user_id, $convo_id, PointsSystem::pointsForConversationTopic(), $db);
                   

                

            } else {
                $new_path = $parent['path'] . "-" . $parent_id;
                $watcher_id = (explode("-", $new_path)[$watchLayer]) ?? false; // 2nd parent id in chain is the watcher
                if ($watcher_id === false) {
                    throw new RuntimeException("No watcher found in path: ". $parent['path']);
                }

                $db->query(
                    "INSERT INTO messages (user_id, parent_id, convo_id, data, path, isAnonymous)
                    VALUES (?, ?, ?, ?, ?, ?)",
                    [$user_id, $parent_id, $parent['convo_id'], $data, $new_path, (int)$isAnonymous]
                );
                $mid = $db->lastInsertId();

                $db->query(
                    "UPDATE messages SET updated_last = NOW() WHERE ID = ?",
                    [$watcher_id]
                );
                
                $stmt2 = $db->query("UPDATE messages SET group_state = group_state + 1 WHERE ID = ?",[$watcher_id]);
                if ($stmt2->rowCount() !== 1) {
                    throw new RuntimeException("Update state failed...");
                }
                UserLobbyRepository::addPoints($user_id, $convo_id, PointsSystem::pointsForConversationMessage(), $db);
            }
            
            if ($useTransaction) {
                $db->commit();
            }
            
            $returnToken['mid'] = $mid;
            $returnToken['success'] = true;
            return $returnToken;

        } catch (Throwable $e) {
            $db->rollBack();
            error_log("Send Message failed: " . $e->getMessage());
            $returnToken['error_msg'] = $e->getMessage();
            return $returnToken;
        }
    }

    public static function messagesUpdateTime($parent_id, DatabaseConnection $db) {
        $time = 0;
        try {
            if (self::isValidConversation($parent_id)) {
                $time = ($db->fetch("SELECT messages_updated FROM conversations WHERE ID = ?", [$parent_id])['messages_updated']) ?? null;
            } else {
                $time = ($db->fetch("SELECT updated_last FROM  messages WHERE ID = ?", [$parent_id])["updated_last"]) ?? null;
            }
        } catch(Exception $e) {
            error_log("@CREPO   messagedUpdatedError-- ".$e->getMessage());
            return null;
        }

        if ($time === 0 || $time === null) {
            return null;
        }

        return $time; // this is in SQL UTC btw

    }

    public static function messagesUpdateState($parent_id, DatabaseConnection $db) {
        $state = -1;
        try {
            if (self::isValidConversation($parent_id)) {
                $state = ($db->fetch("SELECT plaza_state FROM conversations WHERE ID = ?", [$parent_id])['plaza_state']) ?? null;
            } else {
                $state = ($db->fetch("SELECT group_state FROM  messages WHERE ID = ?", [$parent_id])["group_state"]) ?? null;
            }
        } catch(Exception $e) {
            error_log("@CREPO   messagedUpdatedError-- ".$e->getMessage());
            return null;
        }

        if ($state === -1 || $state === null) {
            return null;
        }

        return $state;

    }









    // setting user permissions, DatabaseConnection $db
    public static function addUserToConversation(int $user_id, int $convo_id, DatabaseConnection $db): bool {
        if (!self::isValidConversation($convo_id)) {
            return false;
        }
        if (!(self::joinEnabled($convo_id, $db))['access']) {
            error_log("attempt to join locked conversation $convo_id: user $user_id");
            return false;
        }

        try {
            $stmt = $db->query('INSERT INTO users_conversations (user_id, convo_id) VALUES (?,?)', [$user_id, $convo_id]);
            return $stmt->rowCount() === 1;
        } catch(PDOException $e) {
            if (!self::verifyAccess($user_id, $convo_id, $db)) {
                error_log("ALERT: User $user_id attempted to join convo: $convo_id- sending error details...");
                error_log($e->getMessage());
            }
            
            return false;
        }
        
    }

    public static function removeUserFromConversation(int $user_id, int $convo_id, DatabaseConnection $db): bool {
        
        $stmt = $db->query('DELETE FROM users_conversations WHERE user_id = ? AND convo_id = ?', [$user_id, $convo_id]);
        return ($stmt->rowCount() > 0);
        
    }






    // conversation security/access control
    public static function verifyAccess(int $user_id, int $info_id, DatabaseConnection $db): bool { // at least read access
        return (self::verifyAccessAndGetConvoID($user_id, $info_id, $db)['access']) ?? false;
    }

    private static function verifyAccessAndGetConvoID(int $user_id, int $info_id, DatabaseConnection $db): array { // at least read access
        $toReturn = [];
        if (self::isValidConversation($info_id)) { // conversation access
            if ($info_id === App::$publicConversation && $user_id === -10) {
                $toReturn['access'] = true;
                $toReturn['convo_id'] = $info_id;
                $toReturn['path'] = $info_id;
            } else {
                $row = $db->fetch("SELECT 1 FROM users_conversations WHERE user_id = ? AND convo_id = ?", [$user_id, $info_id]);
                $toReturn['access'] = ((bool) $row) && count($row)>0;
                $toReturn['convo_id'] = $info_id;
                $toReturn['path'] = $info_id;
            }

        } else { // message access (via conversation)
            if ($user_id === -10) {
                $row = self::fetchSingleMessage($info_id, $db);
                if (is_array($row) && ($row['convo_id'] ?? -1) === App::$publicConversation) {
                    $toReturn['access'] = true;
                    $toReturn['convo_id'] = App::$publicConversation;
                    $toReturn['path'] = $row['path'];
                } else {
                    $toReturn['access'] = false;
                }
            } else {
                $row = $db->fetch(
                    "SELECT uc.convo_id, m.path FROM users_conversations uc
                    JOIN messages m ON uc.convo_id = m.convo_id
                    WHERE uc.user_id = ? AND m.ID = ?",
                    [$user_id, $info_id]
                );
                if ($row === false) {
                    $toReturn['access'] = false;
                }
                else {
                    $toReturn['access'] = ($row !== false) && count($row)>0;
                    $toReturn['convo_id'] = $row['convo_id'];
                    $toReturn['path'] = $row['path'];
                }
            }
        }
        // finally, check read access for conversation
        if ($toReturn['access'] == true && !(self::readEnabled($toReturn['convo_id'], $db)['access'])) {
            $toReturn['access'] = false;
            return $toReturn;
        }

        return $toReturn;
    }

    private static function getStatus($convo_id, DatabaseConnection $db): int {
        try {
            $row = $db->fetch("SELECT access_mode FROM conversations WHERE ID = ?", [$convo_id]);
            if (!$row) {
                return -1;
            }
            return (int)$row['access_mode'];
        } catch(Exception $e) {
            error_log("status code fetch failed for convo $convo_id, returned 4.");
            return 4;
        }
        /* 
        Server Conversation access_mode status codes:
        0 - read + write
        1 - read only
        2 - disabled until sufficient users (special message)
        3 - disabled due to lock down
        4 - disabled due to default/unknown reason
        */
    }

    public static function readEnabled($convo_id, DatabaseConnection $db): array {
        $returnArray = [];

        $returnArray['status'] = self::getStatus($convo_id, $db);
        $returnArray['access'] = in_array($returnArray['status'], [0,1]);

        return $returnArray;
        
    }

    public static function writeEnabled($convo_id, DatabaseConnection $db): array {
        $returnArray = [];

        $returnArray['status'] = self::getStatus($convo_id, $db);
        $returnArray['access'] = in_array($returnArray['status'], [0]);

        return $returnArray;
    }

    public static function joinEnabled(int $convo_id, DatabaseConnection $db): array {
        $returnArray = [];

        $returnArray['status'] = self::getStatus($convo_id, $db);
        $returnArray['access'] = in_array($returnArray['status'], [0,1,2]);

        return $returnArray;
    }


    







   
    // move out
    public static function getConvoIDGivenCode(string $code): int {
        $ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $BASE = 26;
        $MOD = $BASE ** 5;  
        $A = 912683;  
        $B = 12345;
        $date = date('z');
        $X = 0;
        foreach (str_split($code) as $c) {
            $X = $X * $BASE + strpos($ALPHABET, $c);
        }
        $invA = self::modInverse($A, $MOD);
        $bing = (($X - $B) * $invA) % $MOD;
        return ($bing - $date)/367;
    }

    public static function isValidConversation(int $id): bool {
        return $id >= 0 && $id <= 5000;
        
    }

    private static function modInverse($a, $m) {
        $m0 = $m;
        $x0 = 0;
        $x1 = 1;

        if ($m === 1) return 0;

        while ($a > 1) {
            $q = intdiv($a, $m);
            $t = $m;

            $m = $a % $m;
            $a = $t;

            $t = $x0;
            $x0 = $x1 - $q * $x0;
            $x1 = $t;
        }

        if ($x1 < 0) $x1 += $m0;

        return $x1;
    }

    public static function getCodeGivenConvoID(int $c): string {
        $ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $BASE = 26;
        $MOD = $BASE ** 5;  # 11,881,376
        $A = 912683;       # must be coprime with MOD
        $B = 12345;
        $date = date('z'); // 0 - 365
        $num = $c * 367 + $date;

        $x = ($num * $A + $B) % $MOD;
        $str = "";
        for ( $i = 0; $i < 5; $i++){
            $str .= $ALPHABET[$x % $BASE];
            $x = intdiv($x, $BASE);
        }
        return strrev($str);
    }

    // move out
    public static function incrementWarnings(int $userId, DatabaseConnection $db) {
        $db->query('UPDATE users SET warnings = (warnings+1) WHERE ID = ?', [$userId]);
    }

    public static function getWarnings(int $userId, DatabaseConnection $db): int{
        return $db->fetch('SELECT warnings FROM users WHERE ID = ?', [$userId])['warnings'];
    }
    
   

    

}