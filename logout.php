<?php
session_start();
session_destroy();
setcookie("user_email", "", time() - 3600, "/"); // Delete the remember me cookie
header("Location: login.php");
exit();
?>
