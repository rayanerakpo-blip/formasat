<?php
// logout.php
session_start();

$redirect = isset($_SESSION['admin_id']) ? 'admin_login.php' : 'login.html';

session_unset();
session_destroy();

header('Location: ' . $redirect . '?succes=' . urlencode('Vous avez été déconnecté.'));
exit();
