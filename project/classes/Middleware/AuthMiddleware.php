<?php
require_once 'MiddlewareInterface.php';


class AuthMiddleware implements MiddlewareInterface {
    
   public static function handle(RequestContext $request) {
        if (isset($request->session["user_id"]) && $request->session['user_id'] >= 0) {
            return true;
        }
        $request->status = (401);
        return __DIR__.'/../../pages/pleaseLogin.php';
   }
}