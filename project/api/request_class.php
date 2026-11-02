<?php

header('Content-Type: application/json');

$data = json_decode($request->rawContent(), true);

$subject = $data['subject'];
$teacher = $data['teacher'];
$room = $data['room'];

try {
    UserDataRepository::requestClass($subject,$teacher, $room, $request->dbconn);
} catch(Exception $e) {
    $request->status = (500);
    error_log($e->getMessage());
    throw new ExitException(json_encode(['error' => 'Internal Server Error']));
}
$request->status = (200);
echo json_encode(['success' => true]);
throw new ExitException();
