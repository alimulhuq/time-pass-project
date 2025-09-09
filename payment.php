<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

try {
    $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    $prefill_amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

    if ($event_id <= 0) {
        header("Location: view_events.php?error=Invalid event ID");
        exit();
    }

    // Verify event belongs to the user and get total_cost
    $stmt = $pdo->prepare("SELECT total_cost FROM booking_details WHERE event_id = ? AND user_name = ?");
    $stmt->execute([$event_id, $_SESSION['user_name']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        header("Location: view_events.php?error=Event not found or you do not have permission");
        exit();
    }

    // Calculate current total paid to determine due amount (for display)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(payment_amount), 0) as total_paid 
        FROM payment_details WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $total_paid = (float)$stmt->fetchColumn();
    $due_amount = (float)$event['total_cost'] - $total_paid;

    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payment_method = isset($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method']) : '';
        $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;
        $account_number = isset($_POST['account_number']) ? htmlspecialchars($_POST['account_number']) : '';
        $transaction_id = uniqid('TXN_');

        // Validate inputs
        if (empty($payment_method) || $payment_amount <= 0 || empty($account_number)) {
            $error = "All payment fields are required, and amount must be greater than zero.";
        } else {
            // Begin transaction
            $pdo->beginTransaction();
            try {
                // Always insert new payment record
                $stmt = $pdo->prepare("INSERT INTO payment_details 
                    (user_name, event_id, payment_method, payment_amount, account_number, transaction_id) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $_SESSION['user_name'],
                    $event_id,
                    $payment_method,
                    $payment_amount,
                    $account_number,
                    $transaction_id
                ]);
                if (!$result) {
                    throw new PDOException("Failed to insert payment into payment_details.");
                }

                // Refetch total_cost to ensure it's current
                $stmt = $pdo->prepare("SELECT total_cost FROM booking_details WHERE event_id = ? AND user_name = ?");
                $stmt->execute([$event_id, $_SESSION['user_name']]);
                $current_total_cost = (float)$stmt->fetchColumn();

                // Recalculate total paid amount
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(payment_amount), 0) as total_paid 
                    FROM payment_details WHERE event_id = ?");
                $stmt->execute([$event_id]);
                $total_paid = (float)$stmt->fetchColumn();

                // Update payment_status in booking_details
                $new_payment_status = ($total_paid >= $current_total_cost - 0.01) ? 'Paid' : 'Not Paid';
                $stmt = $pdo->prepare("UPDATE booking_details 
                    SET payment_status = ? 
                    WHERE event_id = ? AND user_name = ?");
                $result = $stmt->execute([$new_payment_status, $event_id, $_SESSION['user_name']]);

                if (!$result) {
                    throw new PDOException("Failed to update payment_status in booking_details.");
                }

                $pdo->commit();
                $success = "Payment of $" . number_format($payment_amount, 2) . " successful! Transaction ID: $transaction_id";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error processing payment: " . $e->getMessage();
                error_log("Payment error for event_id $event_id: " . $e->getMessage());
            }
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Database error in payment.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - EventPro</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=stylesheet">
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .form-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #1e293b;
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-btn {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        .error {
            color: red;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            text-align: center;
        }
        .success {
            color: green;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            text-align: center;
        }
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .popup-btn {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            margin-top: 10px;
        }
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <span class="logo-icon">📅</span>
                <span class="logo-text">EventPro</span>
            </a>
            <div class="mobile-menu-toggle">
                <span class="hamburger"></span>
                <span class="hamburger"></span>
                <span class="hamburger"></span>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="#about" class="nav-link">About Us</a>
                <a href="#contact" class="nav-link">Contact</a>
                <?php
                if (isset($_SESSION['user_name'])) {
                    echo '<div class="profile-section">';
                    echo '<div class="profile-dropdown">';
                    echo '<button class="profile-btn">';
                    echo '<span class="profile-avatar">👤</span>';
                    echo '<span class="profile-name">' . htmlspecialchars($_SESSION['user_name']) . '</span>';
                    echo '<span class="dropdown-arrow">▼</span>';
                    echo '</button>';
                    echo '<div class="dropdown-menu">';
                    echo '<a href="view_profile.php" class="dropdown-item"><span class="dropdown-icon">👤</span>View My Profile</a>';
                    echo '<a href="update_profile.php" class="dropdown-item"><span class="dropdown-icon">⚙️</span>Update My Profile</a>';
                    echo '<a href="view_events.php" class="dropdown-item"><span class="dropdown-icon">👁️</span>View Booked Event Details</a>';
                    echo '<hr class="dropdown-divider">';
                    echo '<a href="logout.php" class="dropdown-item"><span class="dropdown-icon">🚪</span>Logout</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="auth-section">';
                    echo '<a href="login.php" class="login-btn">Login</a>';
                    echo '<a href="register.php" class="register-btn">Register</a>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Payment for Event ID: <?php echo $event_id; ?></h2>
            <form method="POST" id="paymentForm">
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_amount">Amount ($)</label>
                    <input type="number" id="payment_amount" name="payment_amount" min="0" step="0.01" value="<?php echo $prefill_amount > 0 ? number_format($prefill_amount, 2) : number_format($due_amount, 2); ?>" required>
                </div>
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input type="text" id="account_number" name="account_number" required>
                </div>
                <?php if ($error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p class="success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>
                <button type="submit" class="form-btn">Make Payment</button>
            </form>
            <a href="view_events.php" class="back-btn">Back to Events</a>
        </div>
    </div>
    <div class="popup" id="successPopup" style="display: <?php echo $success ? 'flex' : 'none'; ?>;">
        <div class="popup-content">
            <p><?php echo htmlspecialchars($success); ?></p>
            <button class="popup-btn" onclick="closePopup()">OK</button>
        </div>
    </div>
    <footer class="footer" id="contact">
        <div class="footer-background"></div>
        <div class="footer-content">
            <div class="footer-brand">
                <a href="index.php" class="footer-logo">
                    <span class="logo-icon">📅</span>
                    <span class="logo-text">EventPro</span>
                </a>
                <p class="footer-description">Professional event management services creating memorable experiences for every occasion.</p>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <a href="#about">About Us</a>
                    <a href="#services">Services</a>
                    <a href="#portfolio">Portfolio</a>
                    <a href="#contact">Contact</a>
                </div>
                <div class="footer-column">
                    <h4>Contact Info</h4>
                    <p>📧 info@eventpro.com</p>
                    <p>📞 +1 (555) 123-4567</p>
                    <p>📍 123 Event Street, City</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="copyright">© 2025 EventPro. All rights reserved.</p>
            <div class="student-info">
                <p><strong>Name:</strong> MD. Alimul Huq</p>
                <p><strong>ID:</strong> 23303249</p>
                <p><strong>Programme:</strong> BCSE</p>
                <p><strong>Course Code:</strong> 434 | <strong>Course Name:</strong> Database Management System</p>
            </div>
        </div>
    </footer>
    <script>
        function bookEvent(eventType) {
            <?php if (!isset($_SESSION['user_name'])) { ?>
                alert('Please log in to book an event.');
                window.location.href = 'login.php';
            <?php } else { ?>
                window.location.href = 'bookingEvent.php?event_type=' + encodeURIComponent(eventType);
            <?php } ?>
        }

        document.querySelector('.mobile-menu-toggle')?.addEventListener('click', () => {
            document.querySelector('.nav-menu').classList.toggle('active');
            document.querySelector('.mobile-menu-toggle').classList.toggle('active');
        });

        document.querySelector('.profile-btn')?.addEventListener('click', () => {
            document.querySelector('.dropdown-menu').classList.toggle('active');
            document.querySelector('.profile-btn').classList.toggle('active');
        });

        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        function closePopup() {
            document.getElementById('successPopup').style.display = 'none';
            window.location.href = 'view_events.php?success=Payment successful';
        }

        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('popup')) {
                e.target.style.display = 'none';
                window.location.href = 'view_events.php?success=Payment successful';
            }
        });
    </script>
</body>
</html>