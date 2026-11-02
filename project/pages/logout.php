<?php
    
    if ($request->fromProxy) {
        unset($request->session);
        $request->session['destroy_session'] = true;
    } else {
        unset($request->session);
        session_unset();
        session_destroy();
    }
    $request->redirect['location'] = '/home';
    header("Location: /home");
    throw new ExitException();
?>
