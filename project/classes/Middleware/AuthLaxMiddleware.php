<?php
require_once 'MiddlewareInterface.php';


class AuthLaxMiddleware implements MiddlewareInterface {
    
   public static function handle(RequestContext $request) {
        if (isset($request->session["user_id"])) {
            return true;
        }
        $request->status = (401);
        return __DIR__.'/../../pages/pleaseLogin.php';
   }
}