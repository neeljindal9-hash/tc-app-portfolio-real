<?php
// project/classes/Repositories/RateLimitRepository.php

class RateLimitRepository {

    public static function getLimit(int $userId, string $endpointType, DatabaseConnection $db): array|false {
        return $db->fetch('
            SELECT tokens, last_updated 
            FROM api_rate_limits 
            WHERE user_id = ? AND endpoint_type = ?
        ', [$userId, $endpointType]);
    }

    public static function createLimit(int $userId, string $endpointType, int $tokens, int $timestamp, DatabaseConnection $db): bool {
        try {
            $db->query("
                INSERT INTO api_rate_limits (user_id, endpoint_type, tokens, last_updated) 
                VALUES (?, ?, ?, ?)
            ", [$userId, $endpointType, $tokens, $timestamp]);
            return true;
        } catch (Exception $e) {
            error_log("RateLimitRepository: createLimit failed -- " . $e->getMessage());
            return false;
        }
    }

    public static function updateLimit(int $userId, string $endpointType, int $tokens, int $timestamp, DatabaseConnection $db): bool {
        try {
            $db->query("
                UPDATE api_rate_limits 
                SET tokens = ?, last_updated = ? 
                WHERE user_id = ? AND endpoint_type = ?
            ", [$tokens, $timestamp, $userId, $endpointType]);
            return true;
        } catch (Exception $e) {
            error_log("RateLimitRepository: updateLimit failed -- " . $e->getMessage());
            return false;
        }
    }
}