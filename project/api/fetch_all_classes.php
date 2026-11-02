<?php
header('Content-Type: application/json');



$classes = UserDataRepository::getAllClasses($request->dbconn);
// global classes information, ID, subject, teacher

echo json_encode($classes);