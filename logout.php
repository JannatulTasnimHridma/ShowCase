<?php
require_once "helpers.php";
$_SESSION = [];
session_destroy();
header("Location: index.php");
exit;
?>