<?php
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "event_management";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check input
    if (!isset($_GET['role'], $_GET['id'])) {
        die("Invalid request");
    }

    $role = $_GET['role'];
    $id   = $_GET['id'];

    if ($role == 'guest') {
        // Delete from event_guest first (foreign key)
        $stmt = $conn->prepare("DELETE FROM event_guest WHERE guest_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Delete guest
        $stmt = $conn->prepare("DELETE FROM guest_details WHERE guest_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: showDetails.php?role=guest");
        exit;
    }

    if ($role == 'host') {
        // Delete dependent records first
        // 1. Payments
        $stmt = $conn->prepare("DELETE FROM payment_details WHERE host_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 2. Events
        $stmt = $conn->prepare("DELETE FROM event_details WHERE host_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // 3. Host
        $stmt = $conn->prepare("DELETE FROM host_details WHERE host_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: showDetails.php?role=host");
        exit;
    }

    // Delete Event
    if ($role == 'event') {
        // Delete from event_guest first (if any guests registered)
        $stmt = $conn->prepare("DELETE FROM event_guest WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Delete payments related to the event
        $stmt = $conn->prepare("DELETE FROM payment_details WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Delete the event itself
        $stmt = $conn->prepare("DELETE FROM event_details WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: showDetails.php?role=event");
        exit;
    }

    // Delete Payment
    if ($role == 'payment') {
        $stmt = $conn->prepare("DELETE FROM payment_details WHERE payment_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: showDetails.php?role=payment");
        exit;
    }

    $conn->close();
?>
