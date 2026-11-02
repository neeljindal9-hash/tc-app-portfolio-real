<?php

header('Content-Type: application/json');

$code = $request->post['joinCode'];
$cID = ConversationRepository::getConvoIDGivenCode($code);

if (!ConversationRepository::isValidConversation($cID)) {
    $request->status = (400);
    throw new ExitException(json_encode(['error' => 'Missing or invalid data']));
}

if (ConversationRepository::verifyAccess($request->session['user_id'], $cID, $request->dbconn)) {
    $request->status = (200);
    throw new ExitException(json_encode(['error' => 'Already in conversation']));
}


try {
    $success = ConversationRepository::addUserToConversation($request->session['user_id'], $cID, $request->dbconn);
} catch(Exception $e) {
    $request->status = (500);
    error_log($e->getMessage());
    throw new ExitException(json_encode(['error' => 'Internal Server Error']));
}

if (!$success) {
    $request->status = (403);
    throw new ExitException(json_encode(['error' => 'Something went wrong']));
}

$data['convo_id'] = $cID;
$data['convo_name'] = ConversationRepository::getConvoInfo($cID, $request->dbconn)['name'];

$request->status = (200);
echo json_encode(['success' => true, 'data' => $data]);
throw new ExitException();