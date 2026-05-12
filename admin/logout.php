<?php
require_once __DIR__ . '/../config/database.php';
startSecureSession();
session_destroy();
header('Location: login.php');
exit;
