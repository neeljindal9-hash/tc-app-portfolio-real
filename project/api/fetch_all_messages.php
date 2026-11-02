<?php
// fetch_all_messages.php


header('Content-Type: application/json');
$msgId = filter_var($request->get['id'] ?? null, FILTER_VALIDATE_INT);
$convoId = filter_var($request->get['cid'] ?? null, FILTER_VALIDATE_INT);
$userId = filter_var($request->session['user_id'] ?? null, FILTER_VALIDATE_INT);
$loaded_state = filter_var($request->get['loaded_state'] ?? null, FILTER_VALIDATE_INT);

$db_state = ConversationRepository::messagesUpdateState($msgId, $request->dbconn);

if ($msgId === false || $convoId === false || $userId === false) {
    $request->status = (400);
    echo json_encode(['error' => 'Invalid or missing data.']);
    throw new ExitException();
}

if (($loaded_state !== false) && ($db_state === (int)$loaded_state)) {
    $request->status = (204);
    echo json_encode(['status' => 'up_to_date']);
    throw new ExitException();
}

// Security: Verify access before returning data
if (ConversationRepository::verifyAccess($userId, $convoId, $request->dbconn)) {
    $messages = ConversationRepository::fetchAllMessages($msgId, $convoId, $request->dbconn);
    echo json_encode(['data' => $messages, 'state' => $db_state, 'status' => 'requires_update']);
} else {
    $request->status = (403);
    echo json_encode(['error' => 'Unauthorized or invalid ID']);
}