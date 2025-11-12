<?php
session_start();
session_unset();
session_destroy();
header('Location: login.html?status=logged_out');
exit();
?>