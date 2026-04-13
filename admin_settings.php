<?php
require_once 'config.php';
if (!is_admin()) {
    header('location: settings.php');
    exit();
}
header('location: settings.php');
exit();
