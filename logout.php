<?php
session_start();

session_destroy();

if (isset($_COOKIE['user_ingat'])) {
    setcookie("user_ingat", "", time() - 3600, "/");
}

header("Location: login.php");
exit();
