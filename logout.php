<?php
// Logout script
session_start();
session_unset();
session_destroy(); // ends all sessions
header('Location: index.php'); // when logging out you get redirrected to the login page
exit;
?>