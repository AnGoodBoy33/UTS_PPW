<?php
session_start();
session_destroy();

if (isset($_COOKIE['user_ingat'])) {
    setcookie("user_ingat", "", time() - 3600, "/");
}
if (isset($_COOKIE['email_ingat'])) {
    setcookie("email_ingat", "", time() - 3600, "/");
}

header("Location: login.php");
exit();
?>