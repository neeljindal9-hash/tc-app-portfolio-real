<?php

header('Content-Type: application/json');

if (empty($request->session['secure_verification_code']) || ($request->session['secure'] ?? 0) !== 2) {
    $request->status = 403;
    throw new ExitException(json_encode(['error' => "Forbidden: Missing Proper Permissions."]));
}

if (($request->session['verify-attempts'] ??= 3) > 2) {
    $request->status = 403;
    throw new ExitException(json_encode(['error' => "Forbidden: Out of Attempts"]));
} 

$body = $request->rawContent();
if (!is_string($body) || ($length ??= strlen($body)) > 200) {
    $request->status = (400);
    throw new ExitException(json_encode(['error' => "Invalid data - strlen $length"."B"]));
}
$data = json_decode($body, true);
if (!is_array($data)) {
    $request->status = (400);
    echo json_encode(['error' => 'Invalid JSON']);
    throw new ExitException();
}

if (($data['code'] ?? null) === null || (!isset($request->session['verifying_id'])) || ($request->session['verifying_id'] == null)) {
    $request->status = 400;
    echo json_encode(['error' => 'Invalid API request - addUser.']);
    throw new ExitException();
}

// Execute
try {
    if (hash_equals($request->session['secure_verification_code'], $data['code'])) {
        unset($request->session['secure_verification_code']); // done or not done - no middle ground

        // try sign up
        $receipt = UserLoginRepository::addNewUser($request->session['name'], $request->session['email'], $request->session['verifying_id'], $request->dbconn);
        if ($receipt['status'] !== "success") {
            // VERY SUSPICIOUS 
            $request->status = 403;
            echo json_encode(['error' => 'Illegal request - addUser.']);
            unset($request->session['secure']);
            throw new ExitException();
            
        } else {
            // SUCCESS!!
            $request->session['user_id'] = $receipt['id']; // essentially has access perms now
            $request->session['fresh_user'] = true;
            unset($request->session['verify-attempts']);
            unset($request->session['verifying_id']);
            unset($request->session['secure']);
            unset($request->session['stage']);
            echo json_encode(['success' => true]);
        }
        
    } else {
        $request->status = 401;
        $request->session['verify-attempts']++;
        $attempts = $request->session['verify-attempts'];
       
        if ($attempts > 2) {
            echo json_encode(['success' => false, 'error' => "out_of_attempts", 'attempts' => $attempts]);
            error_log('user ' . $request->session['email'] . " failed to complete student verification....");
            unset($request->session['secure_verification_code']);
            unset($request->session['stage']);
        } else {
            echo json_encode(['success' => false, 'error' => "wrong_code", 'attempts' => $attempts]);
            
        }
        
        
        
    }
} catch (ExitException $ee) {
    throw $ee;
} catch (Throwable $t){
    error_log($t->getMessage());
    unset($request->session['secure_verification_code']);
    $request->status = 500;
    echo json_encode(['error' => "Internal Server Error"]);
}
