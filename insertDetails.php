<?php
    $servername = "localhost";
    $username   = "root";  // Change if needed
    $password   = "";      // Change if needed
    $dbname     = "event_management";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // ---------------------- GUEST SUBMISSION ----------------------
    if (isset($_POST["guestSubmitButton"])) {
        $guest_name        = $_POST['guestName'];
        $guest_address     = $_POST['guestAddress'];
        $phone_number      = $_POST['guestPhoneContact'];
        $guest_email       = $_POST['guestEmail'];
        $organization_name = $_POST['organizationName'];
        $host_id           = $_POST['hostID'];       // from form dropdown
        $event_date        = $_POST['eventDateTime'];

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Check if guest email already exists
        $stmt_check_email = $conn->prepare("SELECT guest_id FROM guest_details WHERE guest_email = ?");
        $stmt_check_email->bind_param("s", $guest_email);
        $stmt_check_email->execute();
        $result_email = $stmt_check_email->get_result();

        if ($result_email->num_rows > 0) {
            // Guest already exists
            $row = $result_email->fetch_assoc();
            $guest_id = $row['guest_id'];
        } else {
            // Insert new guest
            $stmt = $conn->prepare("INSERT INTO guest_details (guest_name, guest_address, phone_number, guest_email, organization_name) 
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $guest_name, $guest_address, $phone_number, $guest_email, $organization_name);

            if (!$stmt->execute()) {
                die("Error inserting guest: " . $stmt->error);
            }
            $guest_id = $conn->insert_id;
            $stmt->close();
        }
        $stmt_check_email->close();

        if (!empty($host_id)) {
            // Check if an event exists for this host on the given date
            $stmt_check_event = $conn->prepare("
                SELECT e.event_id, e.event_title, h.host_name 
                FROM event_details e
                JOIN host_details h ON e.host_id = h.host_id
                WHERE e.host_id = ? AND e.event_date = ?
            ");
            $stmt_check_event->bind_param("is", $host_id, $event_date);
            $stmt_check_event->execute();
            $result_event = $stmt_check_event->get_result();

            if ($result_event->num_rows > 0) {
                // Use existing event
                $row = $result_event->fetch_assoc();
                $event_id    = $row['event_id'];
                $event_title = $row['event_title'];
                $host_name   = $row['host_name'];
            } 
            else {
                echo "<p>There is no event here on this ID</p>";
                $stmt_create_event->close();
            }
            $stmt_check_event->close();

            // Link guest with event if exists
            if ($event_id !== null) {
                $stmt_link = $conn->prepare("INSERT IGNORE INTO event_guest (event_id, guest_id) VALUES (?, ?)");
                $stmt_link->bind_param("ii", $event_id, $guest_id);
                if (!$stmt_link->execute()) {
                    die("Error linking guest to event: " . $stmt_link->error);
                }
                $stmt_link->close();
            }
        }

        // Redirect to receipt page
        header("Location: receipt.php?role=guest&id=$guest_id");
        exit;
    }

    // ---------------------- HOST SUBMISSION ----------------------
    elseif (isset($_POST["hostSubmitButton"])) {
        $host_name        = $_POST['hostName'];
        $phone_number     = $_POST['hostPhoneContact'];
        $host_email       = $_POST['hostEmail'];
        $organization_name= $_POST['organizationName'];
        $event_title      = $_POST['eventTitle'];
        $event_type       = $_POST['eventType'];
        $event_address    = $_POST['eventAddress'];
        $event_date       = $_POST['eventDateTime'];
        $event_status     = $_POST['eventStatus'];

        // Insert host
        $stmt = $conn->prepare("INSERT INTO host_details (host_name, phone_number, host_email, organization_name) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $host_name, $phone_number, $host_email, $organization_name);

        if ($stmt->execute()) {
            $host_id = $conn->insert_id; // auto-generated host_id

            $stmt2 = $conn->prepare("INSERT INTO event_details (event_title, host_id, organization_name, event_type, event_address, event_date, event_status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("sisssss", $event_title, $host_id, $organization_name, $event_type, $event_address, $event_date, $event_status);
            $stmt2->execute();
            $stmt2->close();

            echo "
                <script>
                    let payNow = confirm('Host added successfully! Do you want to proceed to payment now?');
                    if(payNow){
                        // Redirect to payment page with host_id and event_id
                        window.location.href = 'payment.html?host_id=$host_id&event_id=$event_id';
                    } 
                    else {
                        // Go back to home page
                        window.location.href = 'index.html';
                    }
                </script>
            ";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // ---------------------- PAYMENT SUBMISSION ----------------------
    elseif (isset($_POST["paymentButton"])) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);


        $host_id        = $_POST['hostID'];
        $event_id       = $_POST['eventID'];
        $amount         = $_POST['payAmount'];
        $method         = $_POST['paymentMethod'];
        $date           = $_POST['dataTime'];   // fixed
        $status         = $_POST['paymentStatus'];
        $transaction_id = $_POST['transactionID'];

        $stmt = $conn->prepare("INSERT INTO payment_details (host_id, event_id, payable_amount, payment_method, payment_date, payment_status, transaction_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidssss", $host_id, $event_id, $amount, $method, $date, $status, $transaction_id);

        if ($stmt->execute()) {
            $payment_id = $conn->insert_id; // auto-generated payment_id
            header("Location: receipt.php?role=host&id=$host_id"); // fixed
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();
?>
