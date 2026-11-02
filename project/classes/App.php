<?php

class App {
    private Router $router;
    private array $DBConnPool;
    public static int $publicConversation = -1;
    private int $nextConnPoolIndex, $DCpoolSize, $connInUse;
    public string $serverName, $serverType;

    public function __construct(int $DCpoolSize, string $serverName, string $serverType, int $publicConvo = -1) {
        $this->serverName = $serverName;
        self::$publicConversation = $publicConvo;
        require_once __DIR__ . "/Router.php";
        require_once __DIR__ . "/DatabaseConnection.php";
        require_once __DIR__ . "/ProfanityFilter.php";
        require_once __DIR__ . "/ShareHash.php";
        require_once __DIR__ . '/Repositories/ConversationRepository.php';
        require_once __DIR__ . '/Repositories/UserLobbyRepository.php';
        require_once __DIR__ . '/Repositories/UserDataRepository.php';
        require_once __DIR__ . '/Repositories/MessageDataRepository.php';
        require_once __DIR__ . '/Repositories/UserLoginRepository.php';
        require_once __DIR__ . "/Repositories/ServerLobbyRepository.php";

        require_once __DIR__ . "/Model/ReliabilityWeighting.php";
        require_once __DIR__ . "/Model/StatsPrecision.php";
        require_once __DIR__ . "/Model/LearnedDetail.php";
        require_once __DIR__ . '/Model/PointsSystem.php';
        
        $this->router = new Router();
        $this->DBConnPool = [];
        $this->nextConnPoolIndex = 0;
        $this->DCpoolSize = $DCpoolSize;
        $this->serverType = $serverType;
        $this->connInUse = 0;

    }

    public function configureDatabase(): bool {
        try {
            for ($i = 0; $i < $this->DCpoolSize; $i++) { // must be lower can mySQL connection limits
                $this->DBConnPool[] = new DatabaseConnection($i+1);
            }
        } catch(Throwable $t) {
            error_log("Database PDO was not configured: " . $t->getMessage());
            return false;
        }
        return true;
    }

    public function configureRouter(): bool {
        $authMiddleware = [
            'file_path'  => __DIR__ . '/../classes/Middleware/AuthMiddleware.php',
            'class_name' => 'AuthMiddleware'
        ];

        $authLaxMiddleware = [
            'file_path'  => __DIR__ . '/../classes/Middleware/AuthLaxMiddleware.php',
            'class_name' => 'AuthLaxMiddleware'
        ];

        $adminMiddleware = [
            'file_path'  => __DIR__ . '/../classes/Middleware/AdminMiddleware.php',
            'class_name' => 'AdminMiddleware'
        ];

        $rlMiddleware = [
            'file_path'  => __DIR__ . '/../classes/Middleware/RequestLimiter.php',
            'class_name' => 'RequestLimiter'
        ];

        $publicRoutes = [
            '/pleaseLogin'   => __DIR__ . '/../pages/pleaseLogin.php',
            '/login'         => __DIR__ . '/../oauth/login.php',
            '/logout'         => __DIR__ . '/../pages/logout.php',
            '/info' =>  __DIR__ . '/../pages/info.php',
            '/about' =>  __DIR__ . '/../pages/about.php',
            '/terms' =>  __DIR__ . '/../pages/privacy.php',
            '/privacy-policy' =>  __DIR__ . '/../pages/privacy.php',
            '/invite' => __DIR__ . '/../pages/invite.php',
            '/callback'      => __DIR__ . '/../oauth/callback.php',
            '/verification'  => __DIR__ . '/../pages/verification.php',
            '/api/sendVerification'  => __DIR__ . '/../api/sendVerification.php',
            '/api/finishSignUpVerification'  => __DIR__ . '/../api/finishSignUpVerification.php',
            '/studentdemo' => __DIR__ . '/../pages/studentdemo.php',
            '/secretdemo102928' => __DIR__ . '/../pages/secretdemo.php',
            '/help' => __DIR__ . '/../pages/help.php'
        ];

        $halfProtectedRoutes = [
            '/api/fetch_all_messages'  => __DIR__ . '/../api/fetch_all_messages.php',
            '/api/fetch_root_messages' => __DIR__ . '/../api/fetch_root_messages.php',
            '/api/fetch_lobby_data'       => __DIR__ . '/../api/lobby/fetch_lobby_data.php'
        ];

        $protectedRoutes = [
            '/'              =>   __DIR__ . '/../pages/home.php',
            '/home'                    => __DIR__ . '/../pages/home.php',
            '/convo'                   => __DIR__ . '/../pages/convo.php',
            '/classes'                 => __DIR__ . '/../pages/classes.php',
            '/welcome'                 => __DIR__ . '/../pages/welcome.php',

            '/api/save_message'        => __DIR__ . '/../api/save_message.php',
            
            '/api/process_join'        => __DIR__ . '/../api/process_join.php',

            '/api/fetch_lobby_data'       => __DIR__ . '/../api/lobby/fetch_lobby_data.php',
            '/api/send_lobby_data'       => __DIR__ . '/../api/lobby/send_lobby_data.php',
            
            '/api/fetch_user_classes_data'       => __DIR__ . '/../api/fetch_user_classes_data.php',
            '/api/fetch_all_classes'       => __DIR__ . '/../api/fetch_all_classes.php',
            '/api/join_class'       => __DIR__ . '/../api/join_class.php',
            '/api/leave_class'       => __DIR__ . '/../api/leave_class.php',
            '/api/request_class'       => __DIR__ . '/../api/request_class.php',
            '/api/fetch_new_friend_data'       => __DIR__ . '/../api/fetch_new_friend_data.php',
            '/api/get_user_hash'       => __DIR__ . '/../api/get_user_hash.php',
            '/api/fetch_friends'       => __DIR__ . '/../api/fetch_friends.php',
            '/api/add_friend'       => __DIR__ . '/../api/add_friend.php',
            '/api/remove_friend'       => __DIR__ . '/../api/remove_friend.php'
        ];

        $adminRoutes = [
            '/admin' => __DIR__ . '/../pages/admin.php'
        ];

        foreach ($publicRoutes as $path => $callback) {
            $stat = $this->router->add($path, [], $callback);
            if (!$stat) {
                error_log("Critical Router Error " . $callback);
                return false;
            }
        }

        $userGroupMiddleware = [$authMiddleware, $rlMiddleware]; 
        foreach ($protectedRoutes as $path => $callback) {
            $stat = $this->router->add($path, $userGroupMiddleware, $callback);
            if (!$stat) {
                error_log("Critical Router Error". $callback);
                return false;
            }
        }
        $halfUserGroupMiddleware = [$authLaxMiddleware, $rlMiddleware]; 
        foreach ($halfProtectedRoutes as $path => $callback) {
            $stat = $this->router->add($path, $halfUserGroupMiddleware, $callback);
            if (!$stat) {
                error_log("Critical Router Error". $callback);
                return false;
            }
        }

        $adminGroupMiddleware = [$authMiddleware, $rlMiddleware, $adminMiddleware];
        foreach ($adminRoutes as $path => $callback) {
            $stat = $this->router->add($path, $adminGroupMiddleware, $callback);
            if (!$stat) {
                error_log("Critical Router Error". $callback);
                return false;
            }
        }
        return true;
    }

    public function handleRequest(RequestContext $request) {
        $uri = parse_url($request->server['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/');


        // remove project from the url, if its there (optional basically)
        if (str_starts_with($uri, '/project')) {
            $uri = substr($uri, strlen('/project'));
        }

        $uri = $uri ?: '/';

        $isApiRequest = str_starts_with($uri, '/api');

        // special cases
        if (str_starts_with($uri, '/invite')) {
            $uri = "/invite";
        }
        if (str_starts_with($uri, '/error')) {
            echo "Error: ".substr($uri, strlen('/error'));
            return;
        }

        // Debugging- output block
        // if (!$isApiRequest) {
        //     echo "URI $uri <br>";
        //     echo " Session Stats: " . json_encode($request->session, JSON_PRETTY_PRINT) . "<br>";
        //     echo " POST: " . json_encode($request->post, JSON_PRETTY_PRINT) . "<br>";
        //     error_log("Project routing: " . $uri);
            
        //     if (isset($request->session['ui_notify'])) {
        //         echo htmlspecialchars($request->session['ui_notify']);
        //         unset($request->session['ui_notify']);
        //     }

        //     if ($uri === '/public/index.php') {
        //         echo "You made it to index.php!!";
        //         return;
        //     }
        // }

        $statusOfPDO = $this->grantDatabaseConnection($request);
        if ($statusOfPDO === false) {
            usleep(10000); // check back in 10 ms
            $statusOfPDO2 = $this->grantDatabaseConnection($request);
            if ($statusOfPDO2 === false) {
                error_log("NO AVAILABLE PDO after waiting period. Abandoning request.");
            } else {
                error_log("Server Load: High- User retrieved PDO after waiting period. See App.php for details.");
            }
        }

        $response = $this->router->dispatch($uri, $request);

        $response = ($response === false) ? "R-Error" : $response;
        

        if (file_exists($response)) {
            try {
                
                require $response;
                
            } catch(ExitException $e) { // we do echo errors from the exit exception as a final send to the client
                echo $e->getMessage();
            } catch(Throwable $t) {
                error_log($t->getMessage());
            } finally {
                $this->releaseDatabaseConnection($request); // must run every time at the end of a request
                if ($this->serverType === "Apache") {

                    if (!isset($request->redirect['location'])) {
                        http_response_code($request->status);
                    } else {
                        error_log("redirecting to ". $request->redirect['location']);
                    }
                }
                return;
            }
            
        } else {
            // Special callback responses
            $this->releaseDatabaseConnection($request);
            echo $isApiRequest ? json_encode(['error' => $response]) : $response;
        }
    }

    public function grantDatabaseConnection(RequestContext $request): bool {
        if (isset($request->dbconn)) {
            return true;
        }

        $success = false;
        $size = count($this->DBConnPool);

        $key = null;
        for ($i = 0; $i < $size; $i++) {
            $key = ($i + $this->nextConnPoolIndex) % $size;
            $conn = $this->DBConnPool[$key];
            $success = $conn->acquire(); // uses it, makes sure
            if ($success) {
                $index = $key;
                break;
            } else {
                error_log("CRITICAL: HEAVY PDO USAGE - Failed to assign Database Connection. index: $key");
            }
        }
        if ($success !== true) {
            
            return false;
        }
        
        $request->dbconn = $conn;

        //error_log("$request->origin PDO acquired: ". $conn->id);

        $this->connInUse++;
        $this->nextConnPoolIndex = ($index + 1) % $size;
        
        // may block here
        $conn->validate();
        return true;
        
    }

    public function releaseDatabaseConnection(RequestContext $request) {
        if (!isset($request->dbconn)) {
            return;
        }
        $Tid = $request->dbconn->id;
        $request->dbconn->returnToPool();
        $this->connInUse--;
        
        
       //error_log("PDO returned: ". $Tid);
        unset($request->dbconn);

    }

    public function dumpAppData() {
        echo "DB Conn data ->";
        echo print_r($this->DBConnPool, true);
        echo " <- DB Conn data\n\n";

        echo "CurrentNextIndex: " . $this->nextConnPoolIndex;
        echo "\n\nCONNECTIONS IN USE: " . $this->connInUse;

        echo "\nServer name: ". $this->serverName .  "\nServer type: ". $this->serverType;
    }

}

class ExitException extends Exception {}