<?php
session_start();

$serverName = "Elijah\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "LUMIERE",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) { 
    die(print_r(sqlsrv_errors(), true));
}

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') { 
    header('Location: login.php'); 
    exit; 
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    $sql = "DELETE FROM LUMIEREMENU WHERE PRODUCTID = '$id'";
    $stmt = sqlsrv_query($conn, $sql);
    if($stmt === false) {
        die("Failed to delete product: " . print_r(sqlsrv_errors(), true));
    }
}
header('Location: admin_dashboard.php'); 
exit;