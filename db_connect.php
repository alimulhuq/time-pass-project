<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "event_management";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log("Connection failed in db_connect.php: " . $e->getMessage());
        die("Connection failed: " . $e->getMessage());
    }
?>