<?php

class UserLobbyRepository {

    public static function reportNewInfo(int $convo_id, int $user_id, int|array $data_location_id, string|int|array $data, int $multipart, DatabaseConnection $db): array {
        $returnInfo = [];
        $returnInfo['success'] = false;

        if (ConversationRepository::verifyAccess($user_id, $convo_id, $db) !== true) {
            
            $returnInfo['error_msg'] = "Unauthorized";
            return $returnInfo;
        }
        if ($multipart === 1) {
            // checks array of ref_ids
            
            if (empty($data_location_id) || sizeof($data_location_id) !== sizeof($data) || self::verifyLobbyRefMatchArray($convo_id, $data_location_id, $db) !== true) {
                error_log("Potential Security Risk: CLREPO reportNewInfo(); User $user_id attempted to attach lobby_ref array to $convo_id");
                
                $returnInfo['error_msg'] = "Invalid data";
                return $returnInfo;
            }
        } else {
            if (self::verifyLobbyRefMatch($convo_id, $data_location_id, $db) !== true) {
                error_log("Potential Security Risk: CLREPO reportNewInfo(); User $user_id attempted to attach lobby_ref $data_location_id to $convo_id");
                
                $returnInfo['error_msg'] = "Invalid data";
                return $returnInfo;
            }
        }
        
        $accessInfo = ConversationRepository::writeEnabled($convo_id, $db);
        if (!$accessInfo['access']) {
            if ($accessInfo['status'] > 2) {
                error_log("User " . $user_id . " requested reportNewInfo to locked conversation...");
            }
            $returnInfo['error_msg'] = "Write is currently disabled.";
            return $returnInfo;
        }

        if ($multipart === 1) {
            $sqlValues = '';
            $queryParameters = [];
            for ($i = 0; $i < sizeof($data); $i++) {
                $sqlValues .= "(?,?,?),";
                $queryParameters[] = $data_location_id[$i];
                $queryParameters[] = $user_id;
                $queryParameters[] = $data[$i];
            }
            $sqlValues = rtrim($sqlValues, ',');
            $isInt = is_int($data[0]);
        } else {
            $sqlValues = '(?,?,?)';
            $queryParameters = [$data_location_id, $user_id, $data];
            $isInt = is_int($data);
        }

        try {
            $db->beginTransaction();
        
            if ($isInt === true) {
                $stmt = $db->query(
                    "REPLACE INTO lobby_data (lobby_ref_id, user_id, data_int) 
                    VALUES $sqlValues
                    ",
                    $queryParameters
                );
            } else { // is string
                $stmt = $db->query(
                    "REPLACE INTO lobby_data (lobby_ref_id, user_id, data_string) 
                    VALUES $sqlValues
                    ",
                    $queryParameters
                );
            }

            $rowCount = $stmt->rowCount();

            if ($rowCount >= 1) {
                $stmt2 = $db->query("UPDATE users_conversations SET local_state = local_state + 1 WHERE user_id = ? AND convo_id = ?",[$user_id, $convo_id]);
                if ($stmt2->rowCount() !== 1) {
                    throw new RuntimeException("Update state failed...");
                }
                if ($multipart) {
                    $placeholders = implode(',', array_fill(0, count($data_location_id), '?'));
                    $params = array_merge($data_location_id, [$convo_id]);
                    $db->query("UPDATE lobby_items SET up_to_date = false WHERE ID IN ($placeholders) AND convo_id = ?",$params);
                } else {
                    $db->query("UPDATE lobby_items SET up_to_date = false WHERE ID  = ? AND convo_id = ?",[$data_location_id, $convo_id]);
                }
                

                
            }

            if ($rowCount === 1) { // inserted new, not replaced
                $amount_of_contributions = $db->fetch("SELECT COUNT(*) FROM lobby_data WHERE lobby_ref_id = ?", [$data_location_id])["COUNT(*)"];
                self::addPoints($user_id, $convo_id, PointsSystem::pointsForLobbyContribution($amount_of_contributions), $db);
            }
            


            $db->commit();
            $returnInfo['success'] = true;
            return $returnInfo;

        } catch(Throwable $t) {
            $db->rollBack();
            error_log("Report Lobby Info failed: " .$t->getMessage());
            $returnInfo['success'] = false;
            $returnInfo['error_msg'] = "Internal Server Error";
            return $returnInfo;
        }
        
    }

    public static function createTest() {
        
    }

    public static function createScheduleEvent() {
        
    }

    public static function verifyLobbyRefMatch(int $convo_id, int $lobby_ref, DatabaseConnection $db): bool {
        // whether lobbyRef is part of convo_id
        $row = $db->fetch("SELECT 1 FROM lobby_items WHERE convo_id = ? AND ID = ?", [$convo_id, $lobby_ref]);
        return ((bool) $row);
    }

    public static function verifyLobbyRefMatchArray(int $convo_id, array $lobby_refs, DatabaseConnection $db): bool {
        // whether lobbyRef is part of convo_id
        if (empty($lobby_refs)) return false;

        $placeholders = implode(',', array_fill(0, count($lobby_refs), '?'));
        $params = array_merge([$convo_id], $lobby_refs);
        $rows = $db->fetchAll("SELECT 1 FROM lobby_items WHERE convo_id = ? AND ID IN ($placeholders)", $params);

        return (sizeof($rows) === sizeof($lobby_refs));
    }

    public static function getLobby(int $convo_id, int $user_id, DatabaseConnection $db): array {
        $components = $db->fetchAll(
            "SELECT ID as group_id, type, description, approval_status,
                CASE 
                    WHEN author_id = ? THEN TRUE 
                    ELSE FALSE 
                END AS created_by_user,
                JSON_CONTAINS(approvers_ids, CONCAT('', ?)) AS approved_by_user,
            created_at, end_date FROM lobby_components WHERE convo_id = ?", [$user_id, $user_id, $convo_id]);
        $result = $db->fetchAll("SELECT ID as reply_ref, group_id, item_descriptor, current_value, type, date, is_int_data FROM lobby_items WHERE convo_id = ?", [$convo_id]);
        $otherInfo = $db->fetch("SELECT rankings FROM conversations WHERE ID = ?", [$convo_id]);

        return [$result, $components, $otherInfo];
    }

    public static function getUserLobbyContributions(int $user_id, DatabaseConnection $db): array {
        // note that lobby_ref_id is a unique school-wide identifier for the lobby item their contribution was attached to
        $result = $db->fetchAll("SELECT lobby_ref_id, data_string, data_int FROM lobby_data WHERE user_id = ?", [$user_id]);
        return $result;
    }

    public static function getLobbyState(int $convo_id, DatabaseConnection $db): ?int {
        $state = $db->fetch("SELECT lobby_state FROM conversations WHERE ID = ?", [$convo_id])['lobby_state'] ?? null;
        return $state;
    }
    public static function getUserLobbyState(int $convo_id, int $user_id, DatabaseConnection $db): ?int {
        $state = $db->fetch("SELECT local_state FROM users_conversations WHERE convo_id = ? AND user_id = ?" , [$convo_id, $user_id])['local_state'] ?? null;
        return $state;
    }

     // only adds points if limit is allowed, does not change state, is transaction safe
    public static function addPoints(int $userId, int $cid, int $points, DatabaseConnection $db) {
        // MAX POINTS per day: 25
        $db->query("UPDATE users_conversations SET new_points = LEAST(new_points + ?, 25)
            WHERE user_id = ? 
            AND convo_id = ?",
        [$points, $userId, $cid]);
    }

}