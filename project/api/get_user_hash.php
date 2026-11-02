<?php

header("Content-Type: application/json");

$userId = $request->session['user_id'];

$hash = ShareHash::encode($userId);
$name = $request->session['name'] ?? "Unknown";
$nameFormatted = str_replace(" ", "-", strtolower($name));

echo json_encode([
    "hash" => $hash,
    "url" => "/invite/$nameFormatted/" . $hash
]);