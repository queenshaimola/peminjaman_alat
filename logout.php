<?php
session_start();
session_unset();
session_destroy();

// Redirect ke halaman login dengan pencegahan cache browser
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: login.php");
exit();
?>