<?php
    session_start();
    include 'db_connect.php';

    if (!isset($_SESSION['user_name'])) {
        header("Location: login.php");
        exit();
    }

    try {
        $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

        if ($event_id <= 0) {
            header("Location: view_events.php?error=Invalid event ID");
            exit();
        }

        // Verify event belongs to user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_details WHERE event_id = ? AND user_name = ?");
        $stmt->execute([$event_id, $_SESSION['user_name']]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            header("Location: view_events.php?error=Event not found or you do not have permission to delete it");
            exit();
        }

        // Delete event
        $stmt = $pdo->prepare("DELETE FROM booking_details WHERE event_id = ? AND user_name = ?");
        $result = $stmt->execute([$event_id, $_SESSION['user_name']]);

        if ($result) {
            header("Location: view_events.php?success=Event deleted successfully");
        } else {
            header("Location: view_events.php?error=Failed to delete event");
            error_log("Failed to delete event ID $event_id for user {$_SESSION['user_name']}: " . print_r($stmt->errorInfo(), true));
        }
    } catch (PDOException $e) {
        error_log("Database error in delete_event.php: " . $e->getMessage());
        header("Location: view_events.php?error=Database error occurred");
    }
    exit();
?>