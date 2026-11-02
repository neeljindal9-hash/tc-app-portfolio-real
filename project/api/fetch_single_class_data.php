<?php

header('Content-Type: application/json');

$data = json_decode($request->rawContent(), true);
$class_id = $data['id'];
$user_id = $request->session['user_id'];
try {
    $ret_data = UserDataRepository::getClassData($class_id,$user_id, $request->dbconn);
} catch(Exception $e) {
    $request->status = 500;
    error_log($e->getMessage());
    throw new ExitException(json_encode(['error' => 'Internal Server Error']));
}

echo json_encode(['data' => $ret_data]);
throw new ExitException();
