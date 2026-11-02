<?php

header('Content-Type: application/json');
error_log("check1");

$data = json_decode($request->rawContent(), true);

$classID = $data['class_id'] ?? null;
$period = $data['period'];


try {
    $classSuccess = UserDataRepository::addUserToClass($request->session['user_id'], $classID, $period, $request->dbconn);
    if ($classSuccess !== true) {
        $request->status = (400);
        throw new ExitException(json_encode(['error' => "Could not join class."]) );
    }
} catch (ExitException $ee) {
    throw $ee;
} catch(Exception $e) {
    error_log($e->getMessage());

    $request->status = (500);
    throw new ExitException(json_encode([
        'error' => "Internal Server Error"
    ]));
}

try {
    // make sure classID is not a free period and that conversation id is meant to exist
    $cID = null;
    if ($classID !== null) {
        $cID = UserDataRepository::getConvoIDGivenClassID($classID, $request->dbconn); // would be null if there isn't a convo_id
        error_log("check2");
    }
    if ($cID !== null) {
        error_log("check3");
        $convoSuccess = ConversationRepository::addUserToConversation($request->session['user_id'], $cID, $request->dbconn);
    }
    
} catch(Exception $e) {
    error_log("Caught API error: ".$e->getMessage());

    $request->status = (500);
    throw new ExitException(json_encode([
        'error' => "Internal Server Error"
    ]));
}

// if 200 -> success
$request->status = (200);
echo json_encode(['success' => true]);
throw new ExitException();




