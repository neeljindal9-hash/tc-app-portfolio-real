<?php

$userId = $request->session['user_id'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/favicon-32x32.png">
    
    <link rel="stylesheet" href="../public/css/style.css">
    <title>Classes</title>
</head>

<body>

   

    <div id="react-root">
        <!-- React loads ClassesPage here -->
    </div>

</body>

</html>

<script type="module" src="/project/public/js/class.js?v=<?= filemtime(__DIR__ . '/../public/js/class.js') ?>"></script>