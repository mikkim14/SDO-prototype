<?php
require_once 'includes/config.php';

$auth = new Auth($database);
$auth->logout();

header("Location: login_new.php?msg=logged_out");
exit();
?>
