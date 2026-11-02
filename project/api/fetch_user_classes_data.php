<?php

header('Content-Type: application/json');

$user_id = $request->session['user_id'];
$only_classes = isset($request->get['onlyclasses']) && $request->get['onlyclasses'] === "true";
 

try {

    if ($only_classes === true) {
        $classes = UserDataRepository::getUserClasses($user_id, $request->dbconn);
        echo json_encode([
            "classes" => $classes,
            "name" => $request->session['name'] ?? "ERROR!"
        ]);

    } else {
        $data = UserDataRepository::getUserClassesData(
            $user_id,
            $request->dbconn
        );


        echo json_encode([
            "classes" => $data[0],
            "students" => $data[1]
        ]);
    }
    

} catch (Exception $e) {
    error_log("we caught: ". $e);

    $request->status = 500;

    echo json_encode([
        "error" => "Internal Server Error"
    ]);
}