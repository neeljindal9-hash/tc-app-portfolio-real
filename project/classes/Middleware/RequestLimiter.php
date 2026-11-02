<?php
require_once 'MiddlewareInterface.php';
require_once __DIR__ . '/../Repositories/RateLimitRepository.php';

class RequestLimiter implements MiddlewareInterface {
    
    public static function handle(RequestContext $request) {
        
        if (isset($request->session["request_limiter_bypass"]) && $request->session["request_limiter_bypass"] === true) {
            return true;
        }
        if (RequestLimiter::check($request->session['user_id'], "Standard", 17, 8, $request)) { // every 8 seconds, clients get 17 requests
            return true;
        }
        $request->status = (429);
        return "429: Too Many Requests";
    }

    private static function check(int $userId, string $type, int $maxTokens, int $refillSeconds, RequestContext $request): bool {
        
        $now = time();
        $record = RateLimitRepository::getLimit($userId, $type, $request->dbconn);

        // creating new record in case
        if ($record === false) {
            RateLimitRepository::createLimit($userId, $type, $maxTokens - 1, $now, $request->dbconn);
            return true;
        }

        $tokens = (int)$record['tokens'];
        $lastRequestReset = (int)$record['last_updated'];

        // If enough time has passed, reset tokens
        if ($now - $lastRequestReset > $refillSeconds) {
            $tokens = $maxTokens;
            $lastRequestReset = $now;
        }

        // Deduct token if available
        if ($tokens > 0) {
            $tokens--;
            RateLimitRepository::updateLimit($userId, $type, $tokens, $lastRequestReset, $request->dbconn);
            return true;
        } else {
            // no tokens available
            return false;
        }
    }
}