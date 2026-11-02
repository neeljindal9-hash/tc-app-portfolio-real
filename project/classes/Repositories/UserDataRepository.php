<?php 
# project/classes/Repositories/UserDataRepository.php

class UserDataRepository {

    public static function addUserToClass(int $user_id, ?int $class_id, int $period, DatabaseConnection $db): bool {
        try {
            $stmt = $db->query("INSERT INTO users_classes (user_id, class_id, period) VALUES (?,?,?)", [$user_id, $class_id, $period]);
            return $stmt->rowCount() > 0;
        } catch(Exception $e) {
            return false;
        }
    }

    public static function removeUserFromClass(int $user_id, ?int $class_id, int $period, DatabaseConnection $db): bool {
        try {
            $stmt = $db->query("DELETE FROM users_classes WHERE user_id = ? AND period = ?", [$user_id, $period]);
            return $stmt->rowCount() > 0;
        } catch(Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public static function requestClass(string $subject, string $teacher, string $room, DatabaseConnection $db){
        $db->query(
            "INSERT INTO classes (subject, teacher, room)
            VALUES (?, ?, ?)",
            [$subject, $teacher, $room]
        );
    }

    public static function getUserClasses(int $user_id, DatabaseConnection $db): array {
        return $db->fetchAll(
            "SELECT
                classes.subject,
                classes.teacher,
                classes.room,
                classes.ID as class_id,
                uc.period AS period
            FROM users_classes uc
            LEFT JOIN classes ON classes.ID = uc.class_id
            WHERE uc.user_id = ?",
            [$user_id]
        );
    }

    public static function getAllClasses( DatabaseConnection $db): array {
        return $db->fetchAll(
            "SELECT
            classes.teacher, classes.subject,
            classes.ID as class_id
            FROM classes"
        );
    }
    public static function getConvoIDGivenClassID(int $id, DatabaseConnection $db): ?int {
        $result = $db->fetch(
            "SELECT convo_id 
            FROM classes
            WHERE ID = ?",
            [$id]
        );

        if (!$result || $result['convo_id'] === null) {
            return null;
        }

        return (int)$result['convo_id'];
    }


    public static function getUserClassesData(int $user_id, DatabaseConnection $db): array {
        // 1. Get all classes for the user
        $classes = self::getUserClasses($user_id, $db);
        
        if (empty($classes)) {
            return [[], []];
        }

        // 2. Extract class IDs and their corresponding periods
        $classIds = array_column($classes, 'class_id');
        $classIds = array_filter($classIds, function ($value1) {return $value1 !== null;}); // NOT NULL CLASS IDs
        $periods = array_unique(array_column($classes, 'period'));

        

        $placeholders = implode(',', array_fill(0, count($classIds), '?'));

        if (count($classIds) === 0) { // subtle edge-case, no valid class_ids
            $placeholders = "?";
            $classIds = [-1];
        }

        $periodPlaceholders = empty($periods) ? '' : implode(',', array_fill(0, count($periods), '?'));

        // 3. Build query ensuring free periods (class_id IS NULL) are restricted to matching periods
        $sql = "SELECT
                    users_classes.class_id,
                    users.ID ^ 162 AS user_id,
                    users.name,
                    users_classes.period,
                    users_classes.joined_at,
                    up.middle_school,
                    up.grade,
                    up.birthday
                FROM users_classes
                JOIN users ON users.ID = users_classes.user_id
                LEFT JOIN user_profile up ON users.ID = up.ID
                WHERE users.ID != ?
                AND (
                    users_classes.class_id IN ($placeholders)";

        // Append the condition for free periods that share the same period slot if periods exist
        $queryParams = array_merge([$user_id], $classIds);
        
        if (!empty($periods)) {
            $sql .= " OR (users_classes.class_id IS NULL AND users_classes.period IN ($periodPlaceholders))";
            $queryParams = array_merge($queryParams, $periods);
        }

        $sql .= ") ORDER BY users.name ASC";

        $students = $db->fetchAll($sql, $queryParams);

        return [$classes, $students];
    }


    // no longer used
    public static function getClassData(int $id, int $user_id, DatabaseConnection $db): array {
        error_log("Expired- getClassData");
        $class = $db->fetch(
            "SELECT 
                classes.subject,
                classes.teacher,
                classes.room
            FROM classes
            WHERE classes.ID = ?
            ",
            [$id]
        );

        if (!$class) {
            return [];
        }


        $students = $db->fetchAll(
            "SELECT
                users.ID ^ 162 AS user_id,
                users.name,
                users_classes.period,
                users_classes.joined_at
            FROM users_classes
            JOIN users
                ON users.ID = users_classes.user_id
            WHERE users_classes.class_id = ?
            AND users.ID != ?
            ORDER BY users.name ASC
            ",
            [$id, $user_id]
        );


        return [
            "subject" => $class["subject"],
            "teacher" => $class["teacher"],
            "students" => $students
        ];
    }
    public static function fetchNameAndSchedule(int $id, DatabaseConnection $db): array {

        // Get user name
        $user = $db->fetch(
            "SELECT name 
            FROM users 
            WHERE ID = ?",
            [$id]
        );

        if (!$user) {
            return [];
        }


        // Get schedule through periods
        $schedule = $db->fetchAll(
            "SELECT
                uc.period,
                uc.class_id,
                c.subject,
                c.teacher
            FROM users_classes uc
            LEFT JOIN classes c
                ON uc.class_id = c.ID
            WHERE uc.user_id = ?
            ORDER BY uc.period ASC",
            [$id]
        );


        return [
            "name" => $user["name"],
            "schedule" => $schedule
        ];
    }





    

    // FRIENDS
    public static function fetchFriends(int $id, DatabaseConnection $db): array {

        $sql = 
            "SELECT 
                u.ID ^ 162 as user_id,
                u.ID as ID,
                u.name,
                uc.period,
                uc.class_id,
                c.subject,
                c.teacher
            FROM friends f
            JOIN users u
                ON u.ID = f.friend_id
            LEFT JOIN users_classes uc
                ON uc.user_id = u.ID
            LEFT JOIN classes c
                ON c.ID = uc.class_id
            WHERE f.user_id = ?
            ORDER BY u.ID, uc.period
        ";

        $rows = $db->fetchAll($sql, [$id]);

        $friends = [];

        foreach ($rows as $row) { // this does create duplicates but it's fine

            $friendId = $row['user_id'];

            if (!isset($friends[$friendId])) {
                $friends[$friendId] = [
                    "user_id" => $friendId,
                    "hash" => ShareHash::encode($row['ID']),
                    "name" => $row["name"],
                    "schedule" => []
                ];
            }

            if ($row["period"] !== null) {
                $friends[$friendId]["schedule"][] = [
                    "period" => (int)$row["period"],
                    "subject" => $row["subject"] ?? "Free Period",
                    "teacher" => $row["teacher"],
                    "class_id" => $row["class_id"]
                ];
            }
        }
        return array_values($friends);
    }
    
    public static function addFriend(int $userId, int $friendId, DatabaseConnection $db): bool {
        if ($userId === $friendId) {
            return false;
        }

        $sql = "
            INSERT IGNORE INTO friends (user_id, friend_id)
            VALUES (?, ?), (?, ?)
        ";

        try {
            $db->query($sql, [
                $userId,
                $friendId,
                $friendId,
                $userId
            ]);

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }
    public static function removeFriend(int $userId, int $friendId, DatabaseConnection $db): bool {
        $sql = "
            DELETE FROM friends
            WHERE (user_id = ? AND friend_id = ?)
            OR (user_id = ? AND friend_id = ?)
        ";

        try {
            $result = $db->query($sql, [
                $userId,
                $friendId,
                $friendId,
                $userId
            ]);

            return $result->rowCount() > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}



?>