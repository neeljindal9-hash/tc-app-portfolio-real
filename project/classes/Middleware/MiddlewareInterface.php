<?php

interface MiddlewareInterface {
    /**
     * Handle an incoming request.
     * * @param array $request The request data (or a Request object)
     * @return mixed
     */
    public static function handle(RequestContext $request);
}