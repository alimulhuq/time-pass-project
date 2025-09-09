<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

try {
    // Get search term safely
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
    $error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

    // Fetch events and calculate payment status
    $sql = "SELECT b.*, COALESCE(SUM(p.payment_amount), 0) as total_paid
            FROM booking_details b
            LEFT JOIN payment_details p ON b.event_id = p.event_id
            WHERE b.user_name = :user";
    $params = ['user' => $_SESSION['user_name']];

    if (!empty($search)) {
        $sql .= " AND (b.event_type LIKE :search OR b.event_address LIKE :search)";
        $params['search'] = "%$search%";
    }

    $sql .= " GROUP BY b.event_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine payment status and due amount
    foreach ($events as &$event) {
        $event['due_amount'] = (float)$event['total_cost'] - (float)$event['total_paid'];
        $event['payment_status'] = ($event['due_amount'] <= 0) ? 'Paid' : 'Not Fully Paid ($' . number_format($event['due_amount'], 2) . ')';
    }
    unset($event); // Unset reference
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Database error in view_events.php: " . $e->getMessage());
    $events = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Booked Events - EventPro</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=stylesheet">
    <style>
        body {
            background-image: url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: rgba(0, 0, 0, 0.1);
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            z-index: -1;
        }
        .events-container {
            max-width: 1600px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .events-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #1e293b;
            text-align: center;
            margin-bottom: 2rem;
        }
        .search-bar {
            margin-bottom: 2rem;
            text-align: center;
        }
        .search-bar input {
            padding: 10px;
            width: 300px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .events-table {
            width: 100%;
            border-collapse: collapse;
        }
        .events-table th, .events-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .events-table th {
            background: #f8fafc;
            font-weight: 600;
        }
        .action-btn {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .action-btn.delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        .action-btn.delete:hover {
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: #3b82f6;
            text-decoration: none;
        }
        .success {
            color: green;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .error {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
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
        <div class="events-container">
            <h2 class="events-title">Booked Events</h2>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <div class="search-bar">
                <form method="GET">
                    <input type="text" name="search" placeholder="Search by event type or address" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="action-btn">Search</button>
                </form>
            </div>
            <table class="events-table" id="eventsTable">
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Event Address</th>
                        <th>Event Date</th>
                        <th>Guests</th>
                        <th>Total Cost</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_address']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                <td><?php echo htmlspecialchars($event['guest_number']); ?></td>
                                <td>$<?php echo number_format($event['total_cost'], 2); ?></td>
                                <td><?php echo htmlspecialchars($event['payment_status']); ?></td>
                                <td>
                                    <a href="update_event.php?event_id=<?php echo $event['event_id']; ?>" class="action-btn">Update</a>
                                    <a href="delete_event.php?event_id=<?php echo $event['event_id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                                    <?php if ($event['due_amount'] <= 0): ?>
                                        <a href="receipt.php?event_id=<?php echo $event['event_id']; ?>" class="action-btn">Get Receipt</a>
                                    <?php else: ?>
                                        <a href="payment.php?event_id=<?php echo $event['event_id']; ?>&amount=<?php echo number_format($event['due_amount'], 2); ?>" class="action-btn">Confirm Payment</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="index.php" class="back-btn">Back to Home</a>
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

        document.querySelector('input[name="search"]')?.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#eventsTable tbody tr');
            rows.forEach(row => {
                if (row.cells.length === 1) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>