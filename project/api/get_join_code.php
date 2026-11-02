<?php
// get_join_code.php



header('Content-Type: application/json');

$convoId = (int)($request->get['id']);
$userId = $request->session['user_id'];



// Security: Verify access before returning data
if (ConversationRepository::verifyAccess($userId, $convoId, $request->dbconn)) {
    $code = ConversationRepository::getCodeGivenConvoID($convoId);
    echo json_encode($messages);
} else {
    $request->status = (403);
    echo json_encode(['error' => 'Unauthorized or invalid ID']);
}