<?php


class AdminRepository {


    public static function setAdmin(string $email, RequestContext $request, DatabaseConnection $db): bool {
        
        if ( (isset($request->session["admin"]) && $request->session["admin"]) || (self::verifyAdmin($request->session['user_id'], $request, $db)) ) {
            try {
                $db->query("UPDATE users SET is_admin = 1 WHERE email = ?", [$email]);
                return true;
            } catch (PDOException $e) {
                return false;
            }
            
        } else {
            error_log("ALERT:  User ". $request->session["user_id"]. " breached admin.");
            return false;

        }
        
    }

    public static function verifyAdmin(int $id, RequestContext $request, DatabaseConnection $db): bool {
        if ($request->session['user_id'] !== $id) {
            error_log("ALERT:  User ". $request->session["user_id"]. " breached admin as id: " . $id);
            return false;
        }
        $stmt = $db->fetch("SELECT is_admin FROM users WHERE ID = ?", [$id]);

        if ($stmt === false) {
            return false;
        }
        return $stmt['is_admin'];

    }
    public static function fetchAllUnverifiedClasses(DatabaseConnection $db): array {
        return $db->fetchAll(
            "SELECT *
            FROM classes
            WHERE approved = ?",
            [false]
        );
    }

    public static function fetchAllVerifiedClasses(DatabaseConnection $db): array {
        return $db->fetchAll(
            "SELECT *
            FROM classes
            WHERE approved = ?",
            [true]
        );
    }

    public static function createModUser($name, $label, DatabaseConnection $db): bool {
        try {
            if (!$db->beginTransaction()) {
               throw new RuntimeException("Already in transaction"); 
            }

            $stmt = $db->query("INSERT INTO users (name, public_flags) VALUES (?, ?)", [$name, json_encode(['label' => $label])]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException("Add user to users failed."); 
            }
            $id = $db->lastInsertId();
            if ($id !== false) {
                $id = (int)$id;
            } else {
                throw new RuntimeException("Invalid lastInsertId in createModUser.");
            }

            $stmt = $db->query("INSERT INTO user_stats (user_id, rel_1, rel_2, rel_3) VALUES (?, ?, ?, ?)", [$id, 0.05, 0.8, 1.0] );
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException("Add user to stats failed.");
            }

            $db->commit();
            return true;

        } catch (Throwable $t) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("we caught:  " . $t->getMessage());
            return false;
        }
       
    }

    // mod messaging
    public static function makeModAnnouncement(int $cID, int $author, string $type, DatabaseConnection $db): bool {
        switch ($type) {
            case "welcome_new":
                return self::setUpWelcomePlazaMessage($cID, $author, $db);
            default:
                return false;
        }
    }

    private static function setUpWelcomePlazaMessage(int $cID, int $authorId, DatabaseConnection $db): bool {
        try {

            if (!$db->beginTransaction()) {
               throw new RuntimeException("Already in transaction in setUpWelcomePlazaMessage()"); 
            }
            $message = json_decode('{ "title": "Welcome to the Plaza!", "text": "What is the Plaza? Who can see this? How should this be used?\n\nA demo of the Plaza. Please read the mandatory rules and guidelines, and get up-to-date official information here!", "flag": "Official Announcement", "banner": 6 }', true);
            $status = ConversationRepository::sendMessage($cID, $authorId, $cID, $message, "discussion", false, 0, false, $db);
            if ($status['success'] !== true) {
                throw new RuntimeException($status['error_msg']);
            }
            $messageID = $status['mid'];

            $message = [
                "text" => '\(\textbf{\large Rules \& Guidelines}\)

                    \(\textbf{1.}\) \(\text{Zero Tolerance Policy}\)

                    Any attempt to break a rule may result in \(\textbf{immediate account deactivation}\).

                    \(\textbf{2.}\) \(\text{Respect}\)

                    Maintain a respectful environment.

                    \(\textbf{3.}\) \(\text{Appropriate Content}\)

                    No content that a school may deem inappropriate or profane.

                    \(\textbf{4.}\) \(\text{Side-Notes / Asides}\)

                    Use the side-notes/asides feature for isolated conversations within the larger context.

                    \(\textbf{5.}\) \(\text{Discussion Continuity}\)

                    Every discussion message must relate, however loosely or implicitly, to the preceding reply. Explicit connections are not required; we understand that accidents happen.'
                ];

            
            $status = ConversationRepository::sendMessage($messageID, $authorId, $cID, $message, null, false, 1, false, $db);
            if ($status['success'] !== true) {
                throw new RuntimeException($status['error_msg']);
            }
            

            $db->commit();
            return true;
        } catch(Throwable $t) {
            $db->rollBack();
            error_log("Why it failed: " . $t->getMessage());
            return false;
        }
    }

    public static function approveClass(int $id, DatabaseConnection $db): bool {
        try {
            if (!$db->beginTransaction()) {
                throw new RuntimeException("Invalid PDO - In transaction. Aborting");   
            }
            $db->query(
                "UPDATE classes
                SET approved = ?
                WHERE ID = ?",
                [true, $id]
            );

            $class = $db->fetch(
                "SELECT subject, teacher
                FROM classes
                WHERE ID = ?",
                [$id]
            );

            if (!$class) {
                throw new RuntimeException("Class $id did not exist...");
            }

            $conversationName = $class['subject'] . " - " . $class['teacher'];

            $conversationID = self::createNewConversationRetID($conversationName, $db);

            if ($conversationID === -1) {
                throw new RuntimeException("new conversation creation failed");
            }

            $db->query(
                "UPDATE classes
                SET convo_id = ?
                WHERE ID = ?",
                [$conversationID, $id]
            );

            if(!self::addUsersInClassToConversation($id, $conversationID, $db)) {
                throw new RuntimeException("adding users to conversation failed..");
            }
            $db->commit();
            return true;
        } catch(Throwable $t) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Admin approveClass $id failed: " . $t->getMessage());
            return false;
        }

    }

    public static function adjustClass(int $class_id, ?string $class_name, ?string $teacher, ?string $room, DatabaseConnection $db): bool {
        try {
            $stmt = $db->query("UPDATE classes 
            SET subject = COALESCE(?, subject),
             teacher = COALESCE(?, teacher),
             room = COALESCE(?, room)
            WHERE ID = ?
            ", [$class_name, $teacher, $room, $class_id]);

            return $stmt->rowCount() > 0;
            
        } catch(Throwable $t) {
            error_log("We caught: " . $t->getMessage());
            return false;
        }
    }

    public static function mergeClasses(int $class_id_final, int $class_id_other, bool $onlyContinueIfNoConvos, DatabaseConnection $db): string {
        if ($class_id_final === $class_id_other) {
            return "Failed; Cannot merge a class into itself.";
        }
        try {
            if (!$db->beginTransaction()) {
                throw new RuntimeException("Invalid PDO - In transaction. Aborting");   
            }

            $convoRow = $db->fetch("SELECT convo_id FROM classes WHERE ID = ?", [$class_id_other]);
            if($convoRow === false) {
                $db->commit();
                return "Failed; Class $class_id_other did not exist.";
            }
            $convo_id_removed = "not_exist";
            
            $convo_id_removed = $convoRow['convo_id'] ?? "not_exist";

            $real_convo_row = $db->fetch("SELECT convo_id FROM classes WHERE ID = ?", [$class_id_final]);
            if ($real_convo_row === false) {
                $db->commit();
                return "Failed; Class $class_id_final did not exist.";
            }
            $real_convo = $real_convo_row['convo_id'];
            
            
            
            if ($convo_id_removed !== "not_exist") { // convo already exists in class that is being removed
                if ($onlyContinueIfNoConvos !== false) {
                    $db->commit();
                    return "Failed; Class $class_id_other already had convo... See Override documentation.";
                }
                $db->query("DELETE FROM conversations WHERE ID = ?", [$convo_id_removed]);
            }
            $db->query("UPDATE users_classes SET class_id = ?, joined_at = NOW() WHERE class_id = ?", [$class_id_final, $class_id_other]);
            $db->query("DELETE FROM classes WHERE ID = ?", [$class_id_other]);

            
            if ($real_convo !== null) {
                // add newly added to existing conversation
                $status = self::addUsersInClassToConversation($class_id_final, $real_convo, $db);
                if ($status !== true) {
                    throw new RuntimeException("We couldn't add newly added users to existing conversation $real_convo... Aborting entire merge operation.");
                }
            }
            $db->commit();
            return "Success; Convo_removed: $convo_id_removed";

        } catch(Throwable $t) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return "Failed; Error: $t";
        }
    }

    private static function addUsersInClassToConversation(int $class_id, int $conversationID, DatabaseConnection $db): bool {
        // is fine if some students in the class are already in the conversation
        try {
            $db->query(
                "INSERT IGNORE INTO users_conversations (user_id, convo_id)
                SELECT user_id, ?
                FROM users_classes
                WHERE class_id = ?
                ", [$conversationID, $class_id]
            );
            return true;
        } catch(Throwable $t) {
            error_log("Caught:  ". $t->getMessage() . " at addUsersInClassToConversatio adminRepo");
            return false;
        }
    }
    public static function rejectClass(int $id, DatabaseConnection $db): bool{
        try {
            $stmt = $db->query(
                "DELETE FROM classes WHERE ID = ?",
                [$id]
            );
            return $stmt->rowCount() > 0;
        } catch(Throwable $t) {
            return false;
        }
    }

    public static function adjustConvoAccess(int $convoId, int $mode, DatabaseConnection $db): bool {
        try {
            $stmt = $db->query(
                "UPDATE conversations SET access_mode = ? WHERE ID = ?",
                [$mode, $convoId]
            );
            return $stmt->rowCount() > 0;
        } catch (Throwable $t) {
            return false;
        }
    }

    public static function fetchAllConvoMessages(int $cid, DatabaseConnection $db): array {
        return $db->fetchAll('SELECT * FROM messages WHERE convo_id = ?', [$cid]);
    }

    public static function deleteMessage(int $mid, DatabaseConnection $db): bool {
        $stmt = $db->query('DELETE FROM messages WHERE ID = ?', [$mid]);
        return ($stmt->rowCount() > 0);
    }

    public static function getUserInfo(int $id, DatabaseConnection $db): array|false {
        return $db->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }
    public static function fetchAllUsers(DatabaseConnection $db): array {
        return $db->fetchAll('SELECT users.name, users.email, users.warnings, users.is_admin, users.school_id, users.ID, us.rel_1, us.rel_3, us.rel_2, users.public_flags FROM users LEFT JOIN user_stats us ON users.ID = us.user_id');
    }
    public static function createNewConversationRetID(string $conversationName, DatabaseConnection $db): int {

        try {
            if (!self::createNewConversation($conversationName, 2, $db)) {
                return -1;
            }

            $conversation = $db->fetch(
                "SELECT ID 
                FROM conversations
                WHERE name = ?
                ORDER BY ID DESC
                LIMIT 1",
                [$conversationName]
            );

            if (!$conversation) {
                error_log("Very strange ERROR - AdminRepo");
                return -1;
            }

            return (int)$conversation['ID'];

        } catch(Exception $e) {
            error_log("AdminRepo: createNewConversation failed -- " . $e->getMessage());
            return -1;
        }
    }

    public static function createNewConversation(string $conversationName, int $access_mode, DatabaseConnection $db): bool {
        try {
            $db->query('INSERT INTO conversations (name, access_mode) VALUES (?, ?) ', [$conversationName, $access_mode]);
        } catch(Exception $e) {
            error_log("AdminRepo: createNewConversation failed--  " . $e->getMessage());
            return false;
        }
        return true;
    }
    public static function removeConversation(int $id, DatabaseConnection $db): bool {
        try {
            $stmt = $db->query('DELETE FROM conversations WHERE ID = (?) ', [$id]);
            return $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            return false;
        }
    }


}