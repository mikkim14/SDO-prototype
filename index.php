<?php
require_once 'includes/config.php';

// If user is logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
    header("Location: pages/" . Auth::getOfficeRedirect($user['office']));
    exit();
}

// Otherwise, redirect to login
header("Location: login_new.php");
exit();
?>
