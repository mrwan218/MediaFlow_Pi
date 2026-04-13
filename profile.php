<?php
require_once 'config.php';
if (!is_logged_in()) {
    header('location: login.php');
    exit();
}
header('location: settings.php');
exit();
