<?php
global $APP;
if ($APP->serverType === "Production") {
    return [
        "host" => "127.0.0.1",
        "db"   => "carve_db",
        "user" => "carve_user",
        'password' => 'Redacted'
    ];
} else {
    return [
        "host" => "127.0.0.1",
        "db"   => "app-db4",
        "user" => "root",
        'password' => ''
    ];
}
