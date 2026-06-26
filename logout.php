<?php
include 'config.php';
session_destroy(); // Saara session data clear kar do
header("Location: login.php"); // Wapas login screen par bhej do
exit();
?>