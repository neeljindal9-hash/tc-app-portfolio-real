<?php

$oauthconfig = require __DIR__ . '/../config/oauthconfig.php';
if (!isset($request->session) || !isset($request->post['index_verified']) || $request->post['index_verified'] !== true) {
    error_log("non index verified request reached callback");
    throw new ExitException("Login failed: Session timed out.");
}

if (!isset($request->get['state']) || !isset($request->session['oauth_state']) || !hash_equals($request->session['oauth_state'], $request->get['state'])) {
    $request->status = (400);
    throw new ExitException("Invalid OAuth state. Warning: Attackers may be trying to steal your identity.");
}
unset($request->session['oauth_state']);

/**
 * ERROR HANDLING
 */
if (isset($request->get['error'])) {
    if ($request->fromProxy) {
        unset($request->session);
        $request->session['destroy_session'] = true;
    } else {
        unset($request->session);
        session_unset();
        session_destroy();
    }
    throw new ExitException("Login failed: " . htmlspecialchars($request->get['error']));
}

/**
 * CHECK AUTH CODE
 */
if (!isset($request->get['code'])) {
    throw new ExitException("No auth code received");
}

$code = $request->get['code'];
$redirect_uri = $request->server['base_path'] . $oauthconfig['redirect_path'];

/**
 * 1. Exchange code for access token
 */
$tokenRequest = [
    'code' => $code,
    'client_id' => $oauthconfig['id'],
    'client_secret' => $oauthconfig['secret'],
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init("https://oauth2.googleapis.com/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenRequest));

$response = curl_exec($ch);
if ($response === false) {
    throw new ExitException("Token request failed: " . curl_error($ch));
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode !== 200) {
    throw new ExitException("Token request failed HTTP $httpCode: $response");
}

$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    throw new ExitException("Error fetching access token: " . $response);
}

$accessToken = $token['access_token'];

$ch = curl_init("https://www.googleapis.com/oauth2/v2/userinfo");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $accessToken"
]);

$userInfo = curl_exec($ch);
if ($userInfo === false) {
    throw new ExitException("UserInfo request failed: " . curl_error($ch));
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode !== 200) {
    throw new ExitException("UserInfo request failed HTTP $httpCode: $userInfo");
}

$data = json_decode($userInfo, true);

if (!isset($data['email'])) {
    throw new ExitException("Failed to fetch user email...");
}

// start session anew
$request->session = null;
if ($request->fromProxy) {
    $request->session['generate_id'] = true;
} else {
    session_regenerate_id(true);
}


$name = explode(' (', $data['name'] ?? '')[0];
$request->session['name'] = $name;


$request->session['email'] = $data['email'];

$status = UserLoginRepository::accountExistsAndWelcomeStatus($request->session['email'], $request->session['name'], $request->dbconn);

$msg= "Routing to dashboard...";
$title = "Authenticated";

switch ($status[0]) {
    case 'no_account':
        $msg= "Securing session...";
        $title = "Identity Verified";
        $request->session['stage'] = "Stage 2: School Verification";
        $request->session['allow_mail'] = true;
        $request->session['secure'] = 1;
        $next_location = "/verification";
        break;

    case 'all_set':
        $next_location = "/home";
        $request->session['user_id'] = $status[1];
        break;

    case 'welcome_required':
        $next_location = "/welcome";
        $request->session['user_id'] = $status[1];
        break;

    default:
        unset($request->session);
        echo "ERROR in callback.php at addUser() invalid status";
        echo '<br><a href="/home">Home</a>';
        throw new ExitException();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authenticating...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #fafafa;
            color: #111;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container { text-align: center; }
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(0,0,0,0.1);
            border-top-color: #111;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 15px auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h3 { margin: 0 0 5px 0; font-size: 16px; font-weight: 600; }
        p { margin: 0; font-size: 13px; color: #666; }
    </style>
</head>
<body>

<div class="container">
    <div class="spinner"></div>
    <h3><?=$title?></h3>
    <p><?=$msg?></p>
</div>

<script>
setTimeout(() => {
    window.location.href = "<?= $next_location?>";
}, 900); // Shorter delay feels snappier
</script>

</body>
</html>