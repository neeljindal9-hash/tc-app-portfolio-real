<?php

require_once 'MiddlewareInterface.php';

class AdminMiddleware implements MiddlewareInterface {
    
    public static function handle(RequestContext $request) {
        if ((isset($request->session['impersonation']) && $request->session['impersonation'])) {
            if (isset($request->session['admin']) && $request->session['admin'] === true) {
                error_log("ALERT: impersonated user held admin perms");
            }
            $request->status = (403);
            return "403: Forbidden";
        }

        if ((isset($request->session['admin']) && $request->session['admin'] === true)) {
            return true;
        }

        if (!isset($request->session['user_id'])) {
            $request->status = (401);
            return "401: Unauthorized";
        }

        require_once __DIR__.'/../Repositories/AdminRepository.php';
        

        if (AdminRepository::verifyAdmin($request->session['user_id'], $request, $request->dbconn)) {
            $request->session['admin'] = true;
            return true;
        }
        
        $request->status = (403);
        return "403: Forbidden";
    }
}