<?php
header('Content-Type: application/json');
$userId = $request->session['user_id'];

$friends = UserDataRepository::fetchFriends(
    $userId,
    $request->dbconn
);

echo json_encode([
    "success" => true,
    "friends" => $friends
]);