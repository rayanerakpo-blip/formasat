<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

if (isset($_SESSION['etudiant_id'])) {
    header('Location: evaluation.php');
    exit();
}

header('Location: login.html');
exit();
