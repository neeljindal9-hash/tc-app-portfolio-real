<?php // PRODUCTION SERVER
declare(strict_types=1);

$autoloadPath = realpath(__DIR__ . '/../vendor/autoload.php');
if ($autoloadPath && file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    error_log("CRITICAL ERROR: Composer autoloader not found at " . __DIR__ . '/../vendor/autoload.php');
}

use OpenSwoole\Runtime;
use OpenSwoole\Http\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\ExitException;
use OpenSwoole\Table;
use OpenSwoole\Process;
use OpenSwoole\Timer;

OpenSwoole\Runtime::enableCoroutine(OpenSwoole\Runtime::HOOK_ALL);

$server = new Server("127.0.0.1", 9502);

// global static memory and constants - forked by workers
$SESSION_DATA = [];
$SESSION_IDs_IN_USE = [];
$APP = null;             

date_default_timezone_set('America/Los_Angeles'); 

const PHP_FILES_ROOT = __DIR__ . "/..";

// get global classes and server needs
require_once __DIR__ ."/Session_data_repository.php";
require_once __DIR__ . "/Formats.php";
require_once __DIR__ . "/RequestContext.php";
require_once __DIR__ . "/../project/classes/SystemLobbyService.php";
require_once __DIR__ . "/../project/classes/TimeManager.php";



// server callback stuff
$server->set([
    'worker_num' => 1,
    'reload_async' => true,
    'max_wait_time' => 5,
    'enable_coroutine' => true,
    'package_max_length' => 0.25 * 1024 * 1024, // 0.25 MiB
]);

$server->on("Start", function(Server $server) {
    error_log("OpenSwoole http server is started at https://thecarve.app\n");
});

$server->on("WorkerStart", function($server, $workerId) use (&$APP) {
    clearstatcache();
    require_once PHP_FILES_ROOT . '/project/classes/App.php';
    $APP = new APP(DCpoolSize: 10, serverName: "thecarve.app", serverType: "Production", publicConvo: 8);
    
    $routerSuccess = $APP->configureRouter();
    $dbSuccess = $APP->configureDatabase();

    if ($routerSuccess !== true) {
        error_log("CRITICAL: App Router configuration failed.\n");
        $APP = null;
    }
    if ($dbSuccess !== true) {
        error_log("CRITICAL: App Database configuration failed.\n");
        $APP = null;
    }
    
    if (!isset($APP)) {
        error_log("MAY not start background daemon - APP not present...");
        return;
    }
    error_log("Request Worker All Set!\n");

    managePeriodicEvents($APP);
});

// MAIN REQUEST HANDLER
$server->on("Request", function(Request $swooleRequest, Response $response ) use (&$SESSION_DATA, &$APP, &$SESSION_IDs_IN_USE) {
    if (!isset($APP)) {
        error_log("ERROR: Request - too early - APP not present...");
        $response->status(503);
        $response->end("Service Temporarily Unavailable");
        return;
    }

    $uri = $swooleRequest->server['request_uri'];

    if ($uri == "/.well-known/appspecific/com.chrome.devtools.json") {
        $response->status(204);
        $response->end();
        return;
    }

    if (serveNonPHPFiles($uri, $response)) {
        return;
    }

    error_log("recieved request: ".$swooleRequest->server['request_uri']);

    $originalSessID = $swooleRequest->cookie['AuthToken16'] ?? null;
    $delete_if_unset = false;

    if ($originalSessID !== null && !isset($SESSION_DATA[$originalSessID])) {
        $originalSessID = null;
        $delete_if_unset = true;
    }

    $request = new RequestContext($swooleRequest);
    
    $request->session = ($originalSessID !== null) ? ($SESSION_DATA[$originalSessID]) : [];
    $request->post['index_verified'] = true;

    // DIRECT ADMIN INTERCEPTOR
    if ($request->server['REQUEST_URI'] === "/admindata") {
        if (($request->session['admin'] ?? false) === true) {
            $response->header('Content-Type', 'text/html; charset=UTF-8');
            $response->end(expose_server_data($SESSION_DATA, $SESSION_IDs_IN_USE, $APP));
            return;
        }
        error_log("INVALID REQUEST - requested locked data.");
        $response->status(403);
        $response->end("403: Very Forbidden");
        return;
    }

    try {
        ob_start();
        $APP->handleRequest($request);
        $output = ob_get_clean();
    } catch(Throwable $t) {
        ob_end_clean();
        error_log("CRITICAL - error thrown FROM App-handleRequest! Aborting... " . $t->getMessage());
        $response->status(500);
        $response->end("500 Internal Server Error");
        return;
    }

    try {
        manageSessionStorage($request, $response, $originalSessID, $SESSION_DATA, $SESSION_IDs_IN_USE, $delete_if_unset);
    } catch (Throwable $t) {
        error_log("CRITICAL - session management failed - see admin interface for details.");
    }

    // handle redirects after saving SESSION data
    if (!empty($request->redirect)) {
        $response->redirect($request->redirect['location'], 302);
        return;
    }

    // finish response, end coroutine
    $response->status($request->status);
    $response->end($output);
    error_log("Response sent.");
    return;
});

$server->on("Shutdown", function($server) {
    echo "Shutting down...\n";
});

function setAuthCookie(Response $response, string $token): void {
    $response->cookie('AuthToken16', $token, time() + 86400, '/', '', true, true, 'Lax');
}

function deleteAuthCookie(Response $response): void {
    $response->cookie('AuthToken16', '', time() - 3600, '/', '', true, true, 'Lax');
}

function serveNonPHPFiles(string $uri, Response $response): bool {
    if ($uri == "/favicon-32x32.png" || $uri == "/favicon-48x48.png" || $uri == "/favicon.ico") {
        $file = PHP_FILES_ROOT . "/project/public/assets/logo". $uri;
        $response->sendfile($file);
        return true;
    }
    
    $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    if ($ext !== 'php' && $ext !== "") {
        
        if (!$uri || !in_array($ext, ['js', 'css', 'png', 'ico', 'jpeg', 'jpg'], false)) {
            error_log("Invalid $uri received - aborting");
            $response->status(404);
            $response->end('Not Found');
            return true;
        }
        
        if (!str_starts_with($uri, '/project')) {
            $uri = "/project" . $uri;
        }
        
        $file = realpath(PHP_FILES_ROOT . $uri);
        $assetsRoot = realpath(PHP_FILES_ROOT . '/project');

        //error_log("file: $file");
        if ($file !== false && is_file($file) && str_starts_with($file, $assetsRoot . DIRECTORY_SEPARATOR)) {
            $response->sendfile($file);
        } else {
            $response->status(404);
            $response->end();
        }
        return true;
    }
    return false;
}

function manageSessionStorage($request, $response, $originalSessID, &$SESSION_DATA, &$SESSION_IDs_IN_USE, $delete_if_unset){
    $destroy = !empty($request->session['destroy_session']);
    $regenerate = !empty($request->session['generate_id']);
    unset($request->session['destroy_session'], $request->session['generate_id']);

    if ($destroy) {
        if ($originalSessID !== null) {
            unset($SESSION_DATA[$originalSessID]);
            SessionRepository::remove($SESSION_IDs_IN_USE, $originalSessID);
        }
        deleteAuthCookie($response);
    } else if ($regenerate) {
        if ($originalSessID === null) {
            $afterSessID = bin2hex(random_bytes(32));
            setAuthCookie($response, $afterSessID);
            $SESSION_DATA[$afterSessID] = $request->session;
            SessionRepository::add($SESSION_IDs_IN_USE, $afterSessID, $SESSION_DATA);
            return;
        }
        unset($SESSION_DATA[$originalSessID]);
        $afterSessID = bin2hex(random_bytes(32));
        while (isset($SESSION_DATA[$afterSessID])) {
            $afterSessID = bin2hex(random_bytes(32));
        }
        SessionRepository::replace($SESSION_IDs_IN_USE, $originalSessID, $afterSessID);
        setAuthCookie($response, $afterSessID);
        $SESSION_DATA[$afterSessID] = $request->session;
        SessionRepository::useID($SESSION_IDs_IN_USE, $afterSessID);
    } else if ($originalSessID === null && !empty($request->session)){
        $afterSessID = bin2hex(random_bytes(32));
        setAuthCookie($response, $afterSessID);
        $SESSION_DATA[$afterSessID] = $request->session;
        SessionRepository::add($SESSION_IDs_IN_USE, $afterSessID, $SESSION_DATA);
    } else if ($originalSessID !== null) {
        $SESSION_DATA[$originalSessID] = $request->session;
        SessionRepository::useID($SESSION_IDs_IN_USE, $originalSessID);
    } else if (($delete_if_unset ?? false) === true) {
        deleteAuthCookie($response);
    }
}

function expose_server_data(&$Sessions, &$SessionsDLL, &$APP) {
    try {
        ob_start();
        echo "SESSION DATA:";
        echo print_r($Sessions, true);
        echo PHP_EOL.PHP_EOL. "SESSION IDs:";
        echo print_r($SessionsDLL, true);
        echo PHP_EOL.PHP_EOL. "PDO STATUS:";
        $APP->dumpAppData();
    } catch (Throwable $t) {
        echo "ERROR- Couldn't finish printing: " . $t->getMessage();
    }
    return Formats::admindataRead(ob_get_clean());
}

function createLobbies(&$APP): bool {
    error_log("SERVER WIDE- CREATING LOBBIES...");

    $request = new RequestContext(null, "system_daemon");
    
    try {
        $APP->grantDatabaseConnection($request);

        return SystemLobbyService::handleDailyLobbiesCreation($request->dbconn);
    } catch(Throwable $t) {
        error_log("ERROR in createLobbies(): " . $t->getMessage());
        return false;
    } finally {
        $APP->releaseDatabaseConnection($request);
    }
    


}

function handleOtherDailyTasks(&$APP): bool {
    error_log("SERVER WIDE- Handling Daily Tasks and Resets...");

    $request = new RequestContext(null, "system_daemon");
    
    try {
        $APP->grantDatabaseConnection($request);

        return SystemLobbyService::handleOtherDailyTasks($request->dbconn);
    } catch(Throwable $t) {
        error_log("ERROR in handleOtherDailyTasks(): " . $t->getMessage());
        return false;
    } finally {
        $APP->releaseDatabaseConnection($request);
    }
    


}

function updateLobbies(&$APP, $time_counter): bool {
    error_log("SERVER WIDE- Updating LOBBIES...");

    $request = new RequestContext(null, "system_daemon");
    
    try {
        $APP->grantDatabaseConnection($request);

        return SystemLobbyService::triggerRecomputeAllLobbies($request->dbconn, $time_counter);
    } catch(Throwable $t) {
        error_log("ERROR in updateLobbies(): " . $t->getMessage());
        return false;
    } finally {
        $APP->releaseDatabaseConnection($request);
    }
    


}

function managePeriodicEvents(&$APP) {

    // daily
    OpenSwoole\Coroutine::create( function() use (&$APP) {
        try {
            ob_start();
            $running = true;
            if (createLobbies($APP) !== true) {
                error_log("CRITICAL: NEW LOBBY CREATIONS ON RESTART FAILED...");
            }

            while($running) {
                
                $now =new DateTimeImmutable("now",new DateTimeZone('America/Los_Angeles'));
            
                $wait = (($now->setTime(5,9,0)->getTimestamp()) - $now->getTimestamp() + 86400) % 86400;
                error_log("waiting $wait+5 seconds for next lobby day.");
                OpenSwoole\Coroutine::sleep($wait + 5);
                
                if (handleOtherDailyTasks($APP) !== true) {
                    error_log("CRITICAL: REGULAR DAILY UPDATES FAILED...");
                }
                if (createLobbies($APP) !== true) {
                    error_log("CRITICAL: NEW LOBBY CREATIONS FAILED...");
                }
                
                
            }

        } finally {
            error_log("CRITICAL: EXITED PERIODIC EVENTS LOOP");
            ob_end_clean();
        }
        
    });

    // frequently, every 10 seconds, with modulo_state for specific control triggers
    OpenSwoole\Coroutine::create( function() use (&$APP) {
    
        try {
            ob_start();
            $running = true;

            $modulo_state = -1;
            while($running) {
                $modulo_state = ($modulo_state+1) % 6;
                $now = new DateTimeImmutable("now",new DateTimeZone('America/Los_Angeles'));
                $wait = 10;
                //error_log("waiting $wait seconds for next lobby update.");
                OpenSwoole\Coroutine::sleep($wait);
                if (updateLobbies($APP, $modulo_state) !== true) {
                    error_log("CRITICAL: LOBBY UPDATES FAILED...");
                }
                
                
            }
        } finally {
            error_log("CRITICAL: EXITED CONSISTENT EVENTS LOOP");
            ob_end_clean();
        }
        
        
    });
    
}






/**
 * 🚀 BACKGROUND FULL DATABASE SQL BACKUP PROCESS
 */
$backupProcess = new Process(function(Process $worker) {
    
    $runBackup = function() {
        try {
        echo "[" . date('Y-m-d H:i:s') . "] Starting FULL database SQL backup...\n";
        
        // --- CONFIGURATION ---

        $dbConfig = require __DIR__ . '/dbConfig.php';
        $dbHost = $dbConfig['dbHost'];
        $dbName = $dbConfig['dbName'];
        $dbUser = $dbConfig['dbUser'];
        $dbPass = $dbConfig['dbPass'];
        $oauthConfig = require __DIR__ . '/serviceConfig.php';

        $googleFolderId = $oauthConfig['google-service-folder-id'];

        
        // ---------------------

        $sqlFile = '/tmp/full_backup_' . $dbName . '_' . time() . '.sql';

        
            // 1. Build and run the database dump command safely via single-transaction streams
            $command = sprintf(
                "mysqldump -h %s -u %s --password=%s --single-transaction %s > %s 2>&1",
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($sqlFile)
            );

            exec($command, $output, $resultCode);

            if ($resultCode !== 0) {
                throw new Exception("mysqldump utility failed. Error logs: " . implode("\n", $output));
            }


            // 2. Stream the file directly up to Google Drive via native HTTP request pipelines
            uploadToGoogleDriveNative($sqlFile, $googleFolderId, $oauthConfig, $dbName);

        } catch (Exception $e) {
            error_log( "[" . date('Y-m-d H:i:s') . "] FULL BACKUP FAILED: " . $e->getMessage() . "\n");
        } finally {
            // Clear file footprints off your server's disk space instantly
            if (file_exists($sqlFile)) {
                unlink($sqlFile);
            }
        }
    };
    

    // Wait exactly 1 second after server boot before triggering the test backup
    Timer::after(1000, function() use ($runBackup) {
        $runBackup();
    });

    // Keep the routine 24-hour cycle active
    Timer::tick(86400000, function() use ($runBackup) {
        $runBackup();
    });
}, false, 2, true);

/**
 * 🔐 LIGHTWEIGHT NATIVE GOOGLE DRIVE MULTIPART UPLOADER (OAUTH2 REFRESH TOKEN FLOW)
 */
function uploadToGoogleDriveNative(string $filePath, string $folderId, array $oauthConfig, string $dbName): void {
    
    // A. Swap the permanent Refresh Token for a temporary (1-hour) Access Token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $oauthConfig['client_id'],
        'client_secret' => $oauthConfig['client_secret'],
        'refresh_token' => $oauthConfig['refresh_token'],
        'grant_type' => 'refresh_token'
    ]));
    
    $rawAuthResponse = curl_exec($ch);
    $authResponse = json_decode($rawAuthResponse, true);

    if (!isset($authResponse['access_token'])) {
        throw new Exception("Google OAuth token refresh failed. Raw response: " . $rawAuthResponse);
    }
    $accessToken = $authResponse['access_token'];

    // B. Perform the Multipart file creation upload to Drive
    $uploadUrl = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';
    $fileName = 'backup_' . $dbName . '_' . date('Ymd_His') . '.sql';

    $metadata = json_encode([
        'name' => $fileName,
        'parents' => [$folderId]
    ]);

    $boundary = '-------' . uniqid();
    $multipartBody = "--$boundary\r\n";
    $multipartBody .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $multipartBody .= $metadata . "\r\n";
    $multipartBody .= "--$boundary\r\n";
    $multipartBody .= "Content-Type: application/sql\r\n\r\n";
    $multipartBody .= file_get_contents($filePath) . "\r\n";
    $multipartBody .= "--$boundary--\r\n";

    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $multipartBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: multipart/related; boundary=$boundary",
        "Content-Length: " . strlen($multipartBody)
    ]);

    $uploadResult = json_decode(curl_exec($ch), true);
    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpStatusCode !== 200 || !isset($uploadResult['id'])) {
        throw new Exception("Google Drive upload call rejected. Response: " . json_encode($uploadResult));
    }

    error_log( "[" . date('Y-m-d H:i:s') . "] Success! Entire database SQL backup safely sent to Google Drive. File ID: " . $uploadResult['id'] . "\n");
}

// Attach the background backup processor to the server lifecycle
$server->addProcess($backupProcess);

$server->start();