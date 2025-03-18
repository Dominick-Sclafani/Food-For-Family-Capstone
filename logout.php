<?php
session_start();
session_destroy(); //destroys session to logout user and redirect to the homepage
header("Location: index.php");
exit;
?>