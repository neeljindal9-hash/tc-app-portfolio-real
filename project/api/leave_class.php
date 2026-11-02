<?php

header('Content-Type: application/json');

$data = json_decode($request->rawContent(), true);

$classID = $data['class_id'];
$period = $data['period'];



$classSuccess = UserDataRepository::removeUserFromClass($request->session['user_id'], $classID, $period, $request->dbconn);
if ($classSuccess == false) {
    $request->status = (500);
    throw new ExitException(json_encode([
        'error' => "Internal Server Error"
    ]));
}


// remove from conversation if applicable - checks 
try {
    $cID = null;
    if ($classID !== null) { // if not free period
        $cID = UserDataRepository::getConvoIDGivenClassID($classID, $request->dbconn);
    }

    if ($cID !== null) {  // if not free period and actually has a corresponding conversation (some classes may not have a conversation ID)
        $convoSuccess = ConversationRepository::removeUserFromConversation($request->session['user_id'], $cID, $request->dbconn);
    }
} catch(Exception $e) {
    error_log($e->getMessage());

    $request->status = (500);
    throw new ExitException(json_encode([
        'error' => $e->getMessage()
    ]));
}

$request->status = (200); // 200 ONLY ON SUCCESS
echo json_encode(['success' => true]);
throw new ExitException();




