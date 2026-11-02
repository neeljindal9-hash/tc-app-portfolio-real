<?php

header('Content-Type: application/json');

$data = json_decode($request->rawContent(), true);

$hash = $data['hash'];
$id = ShareHash::decode($hash);


try {
    $success = UserDataRepository::removeFriend($request->session['user_id'], $id, $request->dbconn);
    if ($success !== true) {
        $request->status = (400);
        throw new ExitException( json_encode(['error' => "Could not remove friend."]) );
    }
} catch (ExitException $ee) {
    throw $ee;
} catch(Exception $e) {
    error_log($e->getMessage());

    $request->status = (500);
    throw new ExitException(json_encode([
        'error' => $e->getMessage()
    ]));
}
$request->status = (200);
echo json_encode(['success' => true]);
throw new ExitException();
