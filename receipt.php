<?php
    require 'vendor/autoload.php';
    use Dompdf\Dompdf;

    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "event_management";

    // Connect to DB
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    if (!isset($_GET['role'], $_GET['id'])) die("Invalid request");
    $role = $_GET['role'];
    $id   = $_GET['id'];

    // Base HTML with CSS
    $html = '<!DOCTYPE html>
    <html>
    <head>
    <style>
    body { font-family: Arial, sans-serif; font-size: 12px; margin:0; padding:0; }
    .header { background-color: #4CAF50; color: white; text-align: center; padding: 10px 0; }
    .header img { height: 50px; vertical-align: middle; margin-right: 10px; }
    h1, h2 { text-align: center; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { border: 1px solid #000; padding: 6px; }
    th { background-color: #f2f2f2; }
    .section-title { background-color: #d9d9d9; font-weight: bold; text-align: center; }
    </style>
    </head>
    <body>';

    // ---------------- HEADER ----------------
    $html .= '<div class="header">';
    $html .= '<img src="logo.png" alt="Logo"> Event Management System';
    $html .= '</div>';

    // ---------------- GUEST ----------------
    if ($role == 'guest') {
        // Fetch guest info and all linked events
        $stmt = $conn->prepare("
            SELECT g.guest_id, g.guest_name, g.phone_number, g.guest_email, g.guest_address, g.organization_name AS guest_org,
                   e.event_id, e.event_title, e.organization_name AS event_org, e.event_type, e.event_address, e.event_date, e.event_status,
                   h.host_name
            FROM guest_details g
            LEFT JOIN event_guest eg ON g.guest_id = eg.guest_id
            LEFT JOIN event_details e ON eg.event_id = e.event_id
            LEFT JOIN host_details h ON e.host_id = h.host_id
            WHERE g.guest_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch guest info from first row (same for all linked events)
        $first_row = $result->fetch_assoc();
        if (!$first_row) {
            echo "<p>No guest found with this ID.</p>";
            exit;
        }

        $html .= '<h1>Guest Event Registration Receipt</h1>';

        // Guest details table
        $html .= '<table>';
        $html .= '<tr class="section-title"><td colspan="2">Guest Details</td></tr>';
        $html .= "<tr><td>Guest ID</td><td>" . htmlspecialchars($first_row['guest_id']) . "</td></tr>";
        $html .= "<tr><td>Name</td><td>" . htmlspecialchars($first_row['guest_name']) . "</td></tr>";
        $html .= "<tr><td>Phone</td><td>" . htmlspecialchars($first_row['phone_number']) . "</td></tr>";
        $html .= "<tr><td>Email</td><td>" . htmlspecialchars($first_row['guest_email']) . "</td></tr>";
        $html .= "<tr><td>Address</td><td>" . htmlspecialchars($first_row['guest_address']) . "</td></tr>";
        $html .= "<tr><td>Organization</td><td>" . htmlspecialchars($first_row['guest_org']) . "</td></tr>";
        $html .= '</table>';

        // Event details table
        $html .= '<table>';
        $html .= '<tr class="section-title"><td colspan="2">Event Details</td></tr>';

        // Reset result pointer to fetch all events
        $result->data_seek(0);
        $has_event = false;
        while ($row = $result->fetch_assoc()) {
            if ($row['event_id']) {
                $has_event = true;
                $html .= "<tr><td>Event ID</td><td>" . htmlspecialchars($row['event_id']) . "</td></tr>";
                $html .= "<tr><td>Event Title</td><td>" . htmlspecialchars($row['event_title']) . "</td></tr>";
                $html .= "<tr><td>Host Name</td><td>" . htmlspecialchars($row['host_name']) . "</td></tr>";
                $html .= "<tr><td>Event Organization</td><td>" . htmlspecialchars($row['event_org']) . "</td></tr>";
                $html .= "<tr><td>Event Type</td><td>" . htmlspecialchars($row['event_type']) . "</td></tr>";
                $html .= "<tr><td>Event Address</td><td>" . htmlspecialchars($row['event_address']) . "</td></tr>";
                $html .= "<tr><td>Event Date/Time</td><td>" . htmlspecialchars($row['event_date']) . "</td></tr>";
                $html .= "<tr><td>Status</td><td>" . htmlspecialchars($row['event_status']) . "</td></tr>";
                $html .= "<tr><td colspan='2'><hr></td></tr>"; // separator for multiple events
            }
        }

        if (!$has_event) {
            $html .= "<tr><td colspan='2'>No events registered for this guest.</td></tr>";
        }

        $html .= '</table>';

        $stmt->close();
    }

    // ---------------- HOST ----------------
    if ($role == 'host') {
        $stmt = $conn->prepare("
            SELECT h.*, e.event_id, e.event_title, e.organization_name AS event_org,
                   e.event_type, e.event_address, e.event_date, e.event_status,
                   p.payment_id, p.payable_amount, p.payment_method, p.payment_status, p.transaction_id
            FROM host_details h
            LEFT JOIN event_details e ON h.host_id = e.host_id
            LEFT JOIN payment_details p ON h.host_id = p.host_id AND p.event_id = e.event_id
            WHERE h.host_id = ?
        ");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $html .= '<h1>Host Event Registration Receipt</h1>';

        // Host Details
        $html .= '<table>';
        $html .= '<tr class="section-title"><td colspan="2">Host Details</td></tr>';
        $html .= "<tr><td>Host ID</td><td>{$data['host_id']}</td></tr>";
        $html .= "<tr><td>Name</td><td>{$data['host_name']}</td></tr>";
        $html .= "<tr><td>Organization</td><td>{$data['organization_name']}</td></tr>";
        $html .= "<tr><td>Phone</td><td>{$data['phone_number']}</td></tr>";
        $html .= "<tr><td>Email</td><td>{$data['host_email']}</td></tr>";
        $html .= '</table><br>';

        // Payment Details
        $html .= '<table>';
        $html .= '<tr class="section-title"><td colspan="2">Payment Receipt</td></tr>';
        $html .= "<tr><td>Payment ID</td><td>{$data['payment_id']}</td></tr>";
        $html .= "<tr><td>Amount</td><td>{$data['payable_amount']}</td></tr>";
        $html .= "<tr><td>Method</td><td>{$data['payment_method']}</td></tr>";
        $html .= "<tr><td>Status</td><td>{$data['payment_status']}</td></tr>";
        $html .= "<tr><td>Transaction ID</td><td>{$data['transaction_id']}</td></tr>";
        $html .= '</table><br>';

        // Event Details
        $html .= '<table>';
        $html .= '<tr class="section-title"><td colspan="2">Event Details</td></tr>';
        $html .= "<tr><td>Event ID</td><td>{$data['event_id']}</td></tr>";
        $html .= "<tr><td>Event Title</td><td>{$data['event_title']}</td></tr>";
        $html .= "<tr><td>Organization</td><td>{$data['event_org']}</td></tr>";
        $html .= "<tr><td>Event Type</td><td>{$data['event_type']}</td></tr>";
        $html .= "<tr><td>Address</td><td>{$data['event_address']}</td></tr>";
        $html .= "<tr><td>Date/Time</td><td>{$data['event_date']}</td></tr>";
        $html .= "<tr><td>Status</td><td>{$data['event_status']}</td></tr>";
        $html .= '</table>';
    }

    $html .= '</body></html>';

    // Generate PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();

    $filename = ucfirst($role)."_Receipt_".$id.".pdf";
    $dompdf->stream($filename, ["Attachment" => true]);

    $conn->close();
?>