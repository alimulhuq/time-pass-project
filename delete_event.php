<?php
    session_start();
    include 'db_connect.php';
    if (!isset($_SESSION['user_name'])) {
        header("Location: login.php");
        exit();
    }
    $event_id = $_GET['event_id'];
    $stmt = $pdo->prepare("DELETE FROM booking_details WHERE event_id = ? AND user_name = ?");
    $stmt->execute([$event_id, $_SESSION['user_name']]);
    header("Location: view_events.php");
    exit();
?>