<?php
header('Content-Type: application/json');
$raw = $request->rawContent();

if (empty($raw)) {
    echo json_encode([
        "error" => "No data received"
    ]);
    exit;
}

$data = json_decode($raw, true);

if ($data === null) {
    echo json_encode([
        "error" => "Invalid JSON"
    ]);
    exit;
}

$friendHash = $data["friendHash"] ?? null;

if ($friendHash === null) {
    echo json_encode([
        "error" => "Missing friendHash"
    ]);
    exit;
}

$friendId = ShareHash::decode($friendHash);

$nameAndSchedule = UserDataRepository::fetchNameAndSchedule(
    $friendId,
    $request->dbconn
);

echo json_encode([
    "success" => true,
    "data" => $nameAndSchedule
]);