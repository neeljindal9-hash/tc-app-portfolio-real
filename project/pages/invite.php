<?php
// this is a route, not a page

try {
$uri = $request->server['REQUEST_URI'] ?? "invalid/invalid-invalid";
if (str_starts_with($uri, "/")) {
    $uri = substr($uri, 1);
}
error_log("NOTICE: User clicked invite link.");


$array = explode("/",$uri);
$name = $array[1] ?? null;
$id = $array[2] ?? null;

$name = ucwords(str_replace("-", " ", $name));
$fname = strtok($name, " ") ?? $name;
$exclusive = false;

if ($id === "exc") {
    $exclusive = true;
    $request->session['exc'] = true;
    $request->session['name'] = $name;
}
    

} catch(Throwable $t) {
    $name = "About Blank";
    $fname = "About Blank";
}

?>

<?php if($exclusive === true): 
    error_log("NOTICE: Demo Redeemed");
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($name) ?>'s Free Demo Subscription </title>
    <link rel="icon" type="image/png" href="/favicon-32x32.png">

    
    <meta property="og:title" content="<?= htmlspecialchars($name) ?> - Unlock Student Demo">
    <meta property="og:description" content="The Carve has invited you to demo the app for students.">
    <meta property="og:image" content="/public/assets/images/invite-panel-2.png">

    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta property="og:site_name" content="The Carve">

   <script>
        // Use json_encode safely injects the PHP variable into JavaScript
        window.location.replace("/studentdemo");
    </script>

    <noscript>
        <!-- Use htmlspecialchars here since it's an HTML attribute -->
        <meta http-equiv="refresh" content="0; url=/studentdemo) ?>">
    </noscript>
</head>
<body>
    
</body>
</html>

<?php else: ?>

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($name) ?>'s Invite </title>
    <link rel="icon" type="image/png" href="/favicon-32x32.png">

    
    <meta property="og:title" content="<?= htmlspecialchars($fname) ?> invited you compare schedules!">
    <meta property="og:description" content="<?= htmlspecialchars($name) ?> invited you to The Carve to view their schedule, share your classes, ask questions, chat with classmates, and more.">
    <meta property="og:image" content="/public/assets/images/invite-panel-2.png">

    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">


    <meta property="og:site_name" content="The Carve">


   <script>
        // Use json_encode safely injects the PHP variable into JavaScript
        window.location.replace("/classes?tab=friends&people=" + encodeURIComponent(<?= json_encode($id) ?>));
    </script>

    <noscript>
        <!-- Use htmlspecialchars here since it's an HTML attribute -->
        <meta http-equiv="refresh" content="0; url=/classes?tab=friends&people=<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
    </noscript>
</head>
<body>
    
</body>
</html>


<?php endif;?>

