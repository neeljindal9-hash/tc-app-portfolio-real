<?php

if (($request->session['allow_mail'] ?? false) !== true) {
    $request->status = 403;
    throw new ExitException(json_encode(['error' => 'Forbidden: May not send mail.']));
}


unset($request->session['allow_mail']); // block any more requests


$body = $request->rawContent();
$data = json_decode($body, true);
if (!is_array($data)) {
    $request->status = (400);
    echo json_encode(['error' => 'Invalid JSON']);
    throw new ExitException();
}

if ($data['id'] == null) {
    $request->status = 400;
    echo json_encode(['error' => 'Invalid Mail API request.']);
    throw new ExitException();
}

$email = $data['id'] . "@mvla.net";
$request->session['secure_verification_code'] = bin2hex(random_bytes(5)); // create server stored auth code
$request->session['verifying_id'] = $data['id']; // need to store which id this is meant to verify


// EXECUTE SEND
require_once __DIR__ . "/../classes/MailService.php";

$tkns = MailService::getRemainingEmails();
$success = false;
try {
    if ($tkns >= 100) {
        error_log("SENDING EMAIL>>>> had $tkns tokens");
        $success = MailService::sendIDConfirmation($request, $data, $email);
    }

} catch(Exception $e) {
    error_log($e->getMessage());
    unset($request->session['secure_verification_code']);

    if ($success === true) {
        error_log("CRITICAL ISSUE- threw error on success. Secure code invalidated.");
        $success = false;
    }
    
} finally {
    $afterTkns = MailService::getRemainingEmails();

    if ($success === true) {
        $request->status = (200);
        $request->session['secure'] = 2;
        echo json_encode(['success' => true]);
    } else { // error handling
        unset($request->session['secure_verification_code']);
        unset($request->session['verifying_id']);
        if ($afterTkns === $tkns && $afterTkns !== null && $tkns !== null && $afterTkns > 100) {
            $request->status = (200);
            echo json_encode(['success' => 'false-but-may-redo']);
            $request->session['allow_mail'] = true;
        } else {
            $request->status = (500);
            echo json_encode(['error' => 'Internal Server Error']);
        }
    }
    
}


error_log("SendVerfication: $afterTkns token-ed message.");
