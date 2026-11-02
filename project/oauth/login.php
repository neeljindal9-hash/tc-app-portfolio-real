<?php
$oauthconfig = require __DIR__ . '/../config/oauthconfig.php';
$state = bin2hex(random_bytes(32));

$redirect_uri = $request->server['base_path'] . $oauthconfig['redirect_path'];

$request->session['oauth_state'] = $state;
$request->session['stage'] = "Stage 1: Google Account";


$login_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $oauthconfig['id'],
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
    'state' => $state
]);

$request->redirect['location'] = $login_url;
header("Location: $login_url");
throw new ExitException();

