<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - EventPro</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        .form-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
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
        }
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        .popup-content {
            text-align: center;
        }
        .popup-btn {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php session_start(); ?>
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
    <?php
        include 'db_connect.php';

        if (!isset($_SESSION['user_name'])) {
            header("Location: login.php");
            exit();
        }

        // Ensure event_id is set and is numeric
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

        if ($event_id <= 0) {
            die("Invalid event ID.");
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payment_method = $_POST['payment_method'] ?? '';
            $payment_amount = $_POST['payment_amount'] ?? 0;
            $account_number = $_POST['account_number'] ?? '';
            $transaction_id = uniqid('TXN_');

            // Validate inputs
            if (empty($payment_method) || empty($payment_amount) || empty($account_number)) {
                $error = "All payment fields are required.";
            } else {
                try {
                    // Insert into payment_details
                    $stmt = $pdo->prepare("INSERT INTO payment_details 
                        (user_name, event_id, payment_method, payment_amount, account_number, transaction_id) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_SESSION['user_name'], 
                        $event_id, 
                        $payment_method, 
                        $payment_amount, 
                        $account_number, 
                        $transaction_id
                    ]);

                    // Update booking_details payment status
                    $stmt = $pdo->prepare("UPDATE booking_details 
                        SET payment_status = 'Paid' 
                        WHERE event_id = ? AND user_name = ?");
                    $stmt->execute([$event_id, $_SESSION['user_name']]);

                    echo "<script>alert('Payment successful!'); window.location.href='view_events.php';</script>";
                    exit();
                } catch (PDOException $e) {
                    $error = "Error processing payment: " . $e->getMessage();
                }
            }
        }
        ?>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Payment</h2>
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
                    <label for="payment_amount">Amount</label>
                    <input type="number" id="payment_amount" name="payment_amount" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input type="text" id="account_number" name="account_number" required>
                </div>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit" class="form-btn" onclick="redirectToViewEvents()">Make Payment</button>
            </form>
            <a href="view_events.php" class="back-btn" style="display: block; text-align: center; margin-top: 1rem;">Back to Events</a>
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

        document.querySelector('.mobile-menu-toggle').addEventListener('click', () => {
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
        function showPopup(eventId) {
            document.getElementById('paymentPopup').style.display = 'block';
            window.currentEventId = eventId;
        }
        function getReceipt() {
            window.location.href = 'receipt.php?event_id=' + window.currentEventId;
        }
        function redirectToViewEvents() {
            window.location.href = '/CSC-434/view_events.php';
        }
    </script>
</body>
</html>