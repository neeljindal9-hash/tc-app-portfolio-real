<?php
// ONLY FOR APACHE SERVERS - DISCONTINUED ENTRY POINT
// project/public/index.php
// ENTRY POINT for APACHE SERVER - See .htaccess rules
// require __DIR__ . "/../classes/App.php";

// if (!isset($request)) {
    
//     class RequestContext {
//         public $get, $post, $server, $session, $fromProxy, $redirect;
//         public int $status;
//         public DatabaseConnection $dbconn;

//         public function __construct() {
//             session_start();
//             $this->get = $_GET;
//             $this->post = $_POST;
//             $this->server = $_SERVER;
//             $this->session = &$_SESSION;
//             $this->server['base_path'] = "http://localhost";
//             $this->fromProxy = false;
//             $this->status = 200; // default
//         }

//         public function rawContent() {
//             return file_get_contents("php://input");
//         }
//     }
    


//     $request = new RequestContext();
// } else {
//     error_log("Invalid Proxy Server Request - DO NOT ENTER...");
//     throw new ExitException();
// }

// $request->post['index_verified'] = true;


// $APP = new App(DCpoolSize: 1, serverName: "localhost", serverType: "Apache");
// $routerSuccess = $APP->configureRouter();
// $dbSuccess = $APP->configureDatabase();

// if ($routerSuccess !== true) {
//     error_log("App Router configuration failed.");
//     throw new ExitException("Router configuration failed");
// }

// if ($dbSuccess !== true) {
//     error_log("App Dataase configuration failed.");
//     throw new ExitException("Database configuration failed");
// }

// $APP->handleRequest($request);


?>