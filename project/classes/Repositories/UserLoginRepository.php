<?php
# project/classes/Repositories/UserLoginRepository.php

class UserLoginRepository {


    public static function addNewUser(string $name, string $email, int $school_id, DatabaseConnection $db): array {
        $stmt = $db->query("INSERT IGNORE INTO users (name, email, school_id) VALUES (?,?, ?)", [$name, $email, $school_id]);
        $receipt = [];
        $receipt['status'] =  ($stmt->rowCount() === 1) ? "success" : "failed";

        if ($receipt['status'] === 'success') {
            $receipt['id'] = $db->lastInsertId();
            $stmt = $db->query("INSERT IGNORE INTO user_stats (user_id) VALUES (?)", [$receipt['id']]);
        } else {
            error_log("Potential Security Threat: unverified client: $email // $name attempted to create user account with ID:$school_id");
        }
    
        return $receipt;
    }

    public static function accountExistsAndWelcomeStatus(string $email, string $name, DatabaseConnection $db): array {
        $row = $db->fetch("SELECT name, welcome_status, ID FROM users WHERE email = ?", [$email]);
        if ($row === false) {
            return ["no_account"];
        }
        if (($row['name'] ??= null) !== $name) {
            error_log("updating user name from " . $row['name'] . " to $name.");
            $db->query("UPDATE users SET name = ? WHERE email = ?", [$name, $email]);
        }

        if ($row['welcome_status'] !== "complete") {
            return ["welcome_required", $row['ID']];
        } else {
            return ["all_set", $row['ID']];
        }
        
    }


    public static function getWelcomeDetails(int $user_id, DatabaseConnection $db): array|false {
        try {
            $info = $db->fetch(
                "SELECT
                users.name, users.email, users.welcome_status, users.school_id, 
                info.grade, info.middle_school, info.birthday 
                FROM users JOIN user_profile info ON users.ID = info.ID 
                WHERE users.ID = ?", 
                [$user_id]
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            return false;
        }
        return $info ?? false;

    }

    public static function setWelcomeDetails(int $user_id, int $grade, int $middle_school, string $birthday, DatabaseConnection $db): bool {
        try {
            $success = $db->query("UPDATE user_profile SET grade = ?, middle_school = ?, birthday = ? WHERE ID = ?", [$grade, $middle_school, $birthday, $user_id]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            return false;
        }
        return true;
    }

}