<?php
// will get lobby data AND user contributions
header('Content-Type: application/json');

$convoID = (int)($request->get['convo_id']); // must be a conversation ID
$genLobbyState = filter_var($request->get['lobby_state'] ?? null, FILTER_VALIDATE_INT);
$userState = filter_var($request->get['local_state'] ?? null, FILTER_VALIDATE_INT);

if (!ConversationRepository::isValidConversation($convoID)) {
    $request->status = (400);
    echo json_encode(['error' => 'Invalid or missing data.']);
    throw new ExitException();
}

if (ConversationRepository::verifyAccess($request->session['user_id'] ?? null, $convoID, $request->dbconn)) {
    $newUserState = UserLobbyRepository::getUserLobbyState($convoID, $request->session['user_id'], $request->dbconn);
    $newLobbyState = UserLobbyRepository::getLobbyState($convoID, $request->dbconn);
    $status = "up_to_date";

    if ($userState !== $newUserState) {
        $user_contributions = UserLobbyRepository::getUserLobbyContributions($request->session['user_id'], $request->dbconn);
        $status = "requires_update";
    } else {
        $user_contributions = null;
    }
    
    if ($genLobbyState !== $newLobbyState) {
        $lobby = UserLobbyRepository::getLobby($convoID, $request->session['user_id'], $request->dbconn);
        if (count($lobby) !== 3) {
            error_log("500 Internal Error in fetch_lobby_data.");
            $request->status = (500);
            echo json_encode(['error' => 'Internal Server Error']);
            throw new ExitException();
        }
        $status = "requires_update";
    } else {
        $lobby = [null, null, null];
    }
    
    if ($status ==="up_to_date") {
        $request->status = (204);
        echo json_encode(['status' => 'up_to_date']);
        throw new ExitException();
    }

    echo json_encode(['general_data' => $lobby[0], 'components_data' => $lobby[1], 'other_info' => $lobby[2], 'user_contributions' => $user_contributions, 'local_state' => $newUserState, 'lobby_state' => $newLobbyState, 'status' => $status]);
} else {
    $request->status = (403);
    echo json_encode(['error' => 'Unauthorized']);
}