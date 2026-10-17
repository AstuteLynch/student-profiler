<?php

session_start();

/* CLEAR SESSION DATA */

$_SESSION = [];


/* REMOVE SESSION COOKIE */

if (ini_get("session.use_cookies")) {

    $params =
        session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


/* DESTROY SESSION */

session_destroy();


/* RETURN TO LOGIN */

header(
    "Location: login.php"
);

exit;