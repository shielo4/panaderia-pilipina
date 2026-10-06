<?php
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: customer%20dashboard/admin%20dashboard/admin_dashboard.php');
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    header('Location: customer%20dashboard/dashboard%20admin/customer%20dashboard/customer_dashboard.php');
    exit();
}

header('Location: customer%20dashboard/dashboard%20admin/login%20form/index.php');
exit();
