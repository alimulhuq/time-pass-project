<?php
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', '/opt/lampp/logs/php_error_log');
    error_reporting(E_ALL);

    session_start();

    // Include Composer autoload for Dompdf
    require 'vendor/autoload.php';
    use Dompdf\Dompdf;
    use Dompdf\Options;

    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_name'])) {
            error_log("Session user_name not set. Redirecting to login.php");
            header("Location: login.php");
            exit();
        }

        // Verify Dompdf installation
        if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
            error_log("Dompdf autoload.php not found");
            throw new Exception("Dompdf library not found. Run 'composer require dompdf/dompdf'");
        }

        // Database connection
        require 'db_connect.php'; // Make sure $pdo is correctly defined

        // Validate event_id from GET
        $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
        if ($event_id <= 0) {
            error_log("Invalid event_id: " . ($_GET['event_id'] ?? 'null'));
            throw new Exception("Invalid event ID");
        }

        // Fetch booking and payment data
        $stmt = $pdo->prepare("
            SELECT u.user_name, u.user_gmail, u.user_phone_number, u.user_address,
                b.event_type, b.event_address, b.total_cost, b.payment_status,
                p.payment_method, p.transaction_id
            FROM users u
            JOIN booking_details b ON u.user_name = b.user_name
            LEFT JOIN payment_details p ON b.event_id = p.event_id AND b.user_name = p.user_name
            WHERE b.event_id = ? AND u.user_name = ?
        ");
        $stmt->execute([$event_id, $_SESSION['user_name']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            error_log("No booking found for event_id: $event_id, user_name: " . $_SESSION['user_name']);
            throw new Exception("No booking found for this event!");
        }

        // Build HTML content for PDF
        $html = '<!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; color: #333; }
            .header { text-align:center; border-bottom:1px solid #000; padding:10px; margin-bottom: 20px;}
            .header h1 { font-size: 24px; margin: 0; font-weight: 700; }
            .header h2 { font-size: 16px; margin: 5px 0 0; font-weight: 400; }
            .details { max-width: 700px; margin: 0 auto; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
            .details table { width: 100%; border-collapse: collapse; margin-top: 10px;}
            .details th, .details td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
            .details th { background-color: #f4f6f8; font-weight: 600; width: 30%; }
            .details tr:nth-child(even) { background-color: #fafafa; }
            .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; color: #555; font-style: italic; font-size: 11px; }
        </style>
        </head>
        <body>';

            // ✅ Header
        $html .= '<div class="header">';
        $html .= '<span style="font-family: DejaVu Sans;">&#128197;</span>'; // calendar icon
        $html .= '<h2>EventPro</h2>';
        $html .= '<h1>Event Booking Receipt</h1>';
        $html .= '</div>';

        // Booking Details
        $html .= '<div class="details"><table>';
        $html .= '<tr><th>Username</th><td>' . htmlspecialchars($data['user_name']) . '</td></tr>';
        $html .= '<tr><th>Phone Number</th><td>' . htmlspecialchars($data['user_phone_number']) . '</td></tr>';
        $html .= '<tr><th>Email</th><td>' . htmlspecialchars($data['user_gmail']) . '</td></tr>';
        $html .= '<tr><th>Address</th><td>' . htmlspecialchars($data['user_address']) . '</td></tr>';
        $html .= '<tr><th>Event Type</th><td>' . htmlspecialchars($data['event_type']) . '</td></tr>';
        $html .= '<tr><th>Event Address</th><td>' . htmlspecialchars($data['event_address']) . '</td></tr>';
        $html .= '<tr><th>Total Cost</th><td>$' . htmlspecialchars(number_format($data['total_cost'], 2)) . '</td></tr>';
        $html .= '<tr><th>Payment Status</th><td>' . htmlspecialchars($data['payment_status']) . '</td></tr>';

        if (!empty($data['payment_method'])) {
            $html .= '<tr><th>Payment Method</th><td>' . htmlspecialchars($data['payment_method']) . '</td></tr>';
            $html .= '<tr><th>Payment Amount</th><td>$' . htmlspecialchars(number_format($data['total_cost'], 2)) . '</td></tr>';
            $html .= '<tr><th>Transaction ID</th><td>' . htmlspecialchars($data['transaction_id']) . '</td></tr>';
        } else {
            $html .= '<tr><th>Payment Method</th><td>Not Paid</td></tr>';
            $html .= '<tr><th>Payment Amount</th><td>$' . htmlspecialchars(number_format($data['total_cost'], 2)) . '</td></tr>';
            $html .= '<tr><th>Transaction ID</th><td>N/A</td></tr>';
        }
        $html .= '</table></div>';

        // Footer
        $html .= '<div class="footer">';
        $html .= '<p>Thank you for choosing EventPro for your event management needs!</p>';
        $html .= '<p>Contact us at support@eventpro.com | +1-800-123-4567</p>';
        $html .= '</div>';

        $html .= '</body></html>';

        // Configure and render PDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', __DIR__);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('dpi', 150);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output PDF
        $filename = "EventPro_Receipt_" . $event_id . ".pdf";
        $dompdf->stream($filename, ["Attachment" => true]);

    } catch (Exception $e) {
        error_log("Error in receipt.php: " . $e->getMessage());
        http_response_code(500);
        echo "Internal Server Error: " . $e->getMessage();
    }
?>