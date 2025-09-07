<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// Fetch booked dates from DB
$stmt = $pdo->query("SELECT event_date FROM booking_details");
$booked_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Convert DB format to Flatpickr format (Y-m-d H:i)
$booked_dates = array_map(function($d) {
    return date("Y-m-d H:i", strtotime($d));
}, $booked_dates);

$booked_dates_json = json_encode($booked_dates);

$event_type = isset($_GET['event_type']) ? $_GET['event_type'] : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_type = $_POST['event_type'];
    $event_address = $_POST['event_address'];
    $event_date = $_POST['event_date'];
    $guest_number = $_POST['guest_number'];
    $food_type = $_POST['food_type'];
    $food_description = $_POST['food_description'];
    $sound_system = $_POST['sound_system'];
    $decoration_description = $_POST['decoration_description'];
    $current_date = date('Y-m-d H:i:s');

    // Check if the date is already booked
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_details WHERE event_date = ?");
    $stmt->execute([$event_date]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $error = "This date and time is already booked. Please select another date.";
    } elseif (strtotime($event_date) < strtotime($current_date)) {
        $error = "Cannot book a past date.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO booking_details (user_name, event_address, event_date, event_type, guest_number, food_type, food_description, sound_system, decoration_description, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Not Paid')");
        $result = $stmt->execute([$_SESSION['user_name'], $event_address, $event_date, $event_type, $guest_number, $food_type, $food_description, $sound_system, $decoration_description]);
        $event_id = $pdo->lastInsertId();
        echo "<script>showPopup($event_id);</script>";
    }
    if ($result) {
        $bookingSuccess = true; // Flag success
    } else {
        $error = "Failed to book event. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Event - EventPro</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group textarea {
            resize: vertical;
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
            margin: 10px;
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            font-weight: 500;
        }
        .popup-btn.pay-now {
            background: #3b82f6;
            color: white;
        }
        .popup-btn.cancel {
            background: #ccc;
        }
        #messagePopup .popup-content, #errorPopup .popup-content {
            background: #e0f7fa;
            color: #1e293b;
            font-weight: 500;
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
            <h2 class="form-title">Book Event</h2>
            <form method="POST" id="bookingForm">
                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <input type="text" id="event_type" value="<?php echo htmlspecialchars($event_type); ?>" disabled>
                    <input type="hidden" name="event_type" value="<?php echo htmlspecialchars($event_type); ?>">
                </div>
                <div class="form-group">
                    <label for="event_address">Event Address</label>
                    <textarea id="event_address" name="event_address" required></textarea>
                </div>
                <div class="form-group">
                    <label for="event_date">Event Date and Time</label>
                    <input type="text" id="event_date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label for="guest_number">Number of Guests</label>
                    <input type="number" id="guest_number" name="guest_number" min="1" required>
                </div>
                <div class="form-group">
                    <label for="food_type">Food Type</label>
                    <select id="food_type" name="food_type" required>
                        <option value="Vegetarian">Vegetarian</option>
                        <option value="Non-Vegetarian">Non-Vegetarian</option>
                        <option value="Vegan">Vegan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="food_description">Food Description</label>
                    <textarea id="food_description" name="food_description"></textarea>
                </div>
                <div class="form-group">
                    <label for="sound_system">Sound System</label>
                    <select id="sound_system" name="sound_system">
                        <option value="None">No</option>
                        <option value="Basic">Yes (Basic)</option>
                        <option value="Premium">Yes (Premium)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="decoration_description">Decoration Description</label>
                    <textarea id="decoration_description" name="decoration_description"></textarea>
                </div>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <button type="submit" class="form-btn" id="submitBtn">Submit Booking</button>
            </form>
        </div>
    </div>

    <!-- Popups -->
    <div class="popup" id="paymentPopup">
        <div class="popup-content">
            <h3>Do you want to pay now or later?</h3>
            <button class="popup-btn pay-now" onclick="payNow()">Pay Now</button>
            <button class="popup-btn cancel" onclick="payLater()">Pay Later</button>
        </div>
    </div>
    <div class="popup" id="messagePopup" style="display: none;">
        <div class="popup-content">
            <p>You will get booking confirmation receipt after confirming payment. You can confirm your payment from view event details</p>
            <button class="popup-btn" onclick="closeMessagePopup()">OK</button>
        </div>
    </div>
    <div class="popup" id="errorPopup" style="display: none;">
        <div class="popup-content">
            <p id="errorMessage"></p>
            <button class="popup-btn" onclick="closeErrorPopup()">OK</button>
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

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Redirect to booking page
        function bookEvent(eventType) {
            <?php if (!isset($_SESSION['user_name'])) { ?>
                alert('Please log in to book an event.');
                window.location.href = 'login.php';
            <?php } else { ?>
                window.location.href = 'bookingEvent.php?event_type=' + encodeURIComponent(eventType);
            <?php } ?>
        }

        // Mobile menu toggle
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                document.querySelector('.nav-menu').classList.toggle('active');
                mobileToggle.classList.toggle('active');
            });
        }

        // Profile dropdown toggle
        const profileBtn = document.querySelector('.profile-btn');
        if (profileBtn) {
            profileBtn.addEventListener('click', () => {
                document.querySelector('.dropdown-menu').classList.toggle('active');
                profileBtn.classList.toggle('active');
            });
        }

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Booked dates from PHP
        const bookedDates = <?php echo $booked_dates_json; ?>;

        // Flatpickr initialization
        flatpickr("#event_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            disable: bookedDates,
            onChange: function(selectedDates, dateStr, instance) {
                if (bookedDates.includes(dateStr)) {
                    showErrorPopup(`This date and time (${dateStr}) is already booked. Please select another date.`);
                    instance.clear();
                }
            }
        });

        // Popup elements
        const paymentPopup = document.getElementById("paymentPopup");
        const messagePopup = document.getElementById("messagePopup");
        const errorPopup = document.getElementById("errorPopup");
        const errorMessage = document.getElementById("errorMessage");

        // Show payment popup
        function showPopup(eventId) {
            if (!eventId) return;
            paymentPopup.style.display = 'flex';
            window.currentEventId = eventId;
            document.body.style.overflow = 'hidden';
        }

        // Pay Now → redirect to payment page with event_id
        function payNow() {
            if (!window.currentEventId) return;
            paymentPopup.style.display = 'none';
            document.body.style.overflow = 'auto';
            window.location.href = 'payment.php?event_id=' + window.currentEventId;
        }

        // Pay Later → show info message
        function payLater() {
            paymentPopup.style.display = 'none';
            messagePopup.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Close message popup
        function closeMessagePopup() {
            messagePopup.style.display = 'none';
            document.body.style.overflow = 'auto';
            window.location.href = 'index.php';
        }

        // Show error popup
        function showErrorPopup(msg) {
            errorMessage.textContent = msg;
            errorPopup.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Close error popup
        function closeErrorPopup() {
            errorPopup.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close popup when clicking outside
        window.addEventListener("click", function(e) {
            if (e.target.classList.contains("popup")) {
                e.target.style.display = "none";
                document.body.style.overflow = 'auto';
            }
        });

        // Automatically show popup after successful booking
        <?php if (isset($bookingSuccess) && $bookingSuccess && isset($event_id)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showPopup(<?= $event_id ?>);
            });
        <?php elseif (!empty($error)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showErrorPopup("<?= $error ?>");
            });
        <?php endif; ?>
    </script>
</body>
</html>
