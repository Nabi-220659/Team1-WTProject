<?php
session_start();
session_destroy();
header("Location: ../Frontend/login.html");
exit;
?>
