<?php
// project/classes/Router.php
// index.php inserts links here and recieves callback locations to serve for the user

class Router {
    private $routes = [];

    // Register a route with its specific middleware and final action
    public function add(string $path, array $middleware, string $callback): bool {
        // verify valid $middleware and $callback
        foreach ($middleware as $mw) {
            if (!isset($mw['file_path']) || !file_exists($mw['file_path'])) {
                return false;
            }
        }

        if (!file_exists($callback)) {
            return false;
        }

        $this->routes[$path] = [
            'middleware' => $middleware,
            'callback' => $callback
        ];
        return true;
    }

    public function dispatch($url, $request): string {
        
        $url = parse_url($url, PHP_URL_PATH);

        if (!isset($this->routes[$url])) {
            $request->status = (404);
            return $url."  404: (Router) No such file";
        }

        $route = $this->routes[$url];
        $middleware = $route['middleware'];

        // If no middleware is defined, just run the controller/callback
        if (empty($middleware)) {
            return $route['callback'];
        }

        // Execute Middleware
        foreach($middleware as $mw) {
            //error_log("entering middleware : " .$mw['class_name']);
            require_once $mw['file_path'];

            $className = $mw['class_name'];
            if (!class_exists($className)) {
                $request->status = (500);
                error_log("Middleware class {$className} not found.");
                return "500: Internal Server Error- Middleware class {$className} not found.";
            }

            $code = $className::handle($request);
            if ($code !== true) {
                return $code; // technically a callback when not true
            }
                
        }
        return $route['callback'];
        
        
    }
}