<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

try {
    $error = '';
    $cost_difference = 0;
    $show_payment_popup = false;
    $show_refunded_popup = false;
    $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

    if ($event_id <= 0) {
        header("Location: view_events.php?error=Invalid event ID");
        exit();
    }

    // Fetch event details
    $stmt = $pdo->prepare("SELECT * FROM booking_details WHERE event_id = ? AND user_name = ?");
    $stmt->execute([$event_id, $_SESSION['user_name']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        header("Location: view_events.php?error=Event not found or you do not have permission to update it");
        exit();
    }

    // Fetch booked dates (excluding current event)
    $stmt = $pdo->prepare("SELECT event_date FROM booking_details WHERE event_id != ?");
    $stmt->execute([$event_id]);
    $booked_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $booked_dates = array_map(function($d) {
        return date("Y-m-d H:i", strtotime($d));
    }, $booked_dates);
    $booked_dates_json = json_encode($booked_dates);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $event_type = isset($_POST['event_type']) ? htmlspecialchars($_POST['event_type']) : $event['event_type'];
        $event_address = isset($_POST['event_address']) ? htmlspecialchars($_POST['event_address']) : '';
        $event_date = isset($_POST['event_date']) ? $_POST['event_date'] : '';
        $guest_number = isset($_POST['guest_number']) ? (int)$_POST['guest_number'] : 0;
        $food_type = isset($_POST['food_type']) ? htmlspecialchars($_POST['food_type']) : '';
        $food_description = isset($_POST['food_description']) ? htmlspecialchars($_POST['food_description']) : '';
        $sound_system = isset($_POST['sound_system']) ? htmlspecialchars($_POST['sound_system']) : 'None';
        $decoration_description = isset($_POST['decoration_description']) ? htmlspecialchars($_POST['decoration_description']) : '';
        $current_date = date('Y-m-d H:i:s');

        // Validate inputs
        if (empty($event_address) || empty($event_date) || $guest_number <= 0 || empty($food_type)) {
            $error = "All required fields must be filled.";
        } else {
            // Check if the date is already booked (excluding current event)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_details WHERE event_date = ? AND event_id != ?");
            $stmt->execute([$event_date, $event_id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "This date and time is already booked. Please select another date.";
            } elseif (strtotime($event_date) < strtotime($current_date)) {
                $error = "Cannot book a past date.";
            } else {
                // Calculate total cost
                $base_per_guest = 5;
                $food_prices = [
                    'Vegetarian' => 10,
                    'Non-Vegetarian' => 15,
                    'Vegan' => 12
                ];
                $sound_prices = [
                    'None' => 0,
                    'Basic' => 500,
                    'Premium' => 1000
                ];
                $decoration_cost = !empty($decoration_description) ? 300 : 0;

                $food_cost_per_guest = isset($food_prices[$food_type]) ? $food_prices[$food_type] : 0;
                $sound_cost = isset($sound_prices[$sound_system]) ? $sound_prices[$sound_system] : 0;

                $new_total_cost = $guest_number * ($base_per_guest + $food_cost_per_guest) + $sound_cost + $decoration_cost;

                // Begin transaction for update and possible refund
                $pdo->beginTransaction();
                try {
                    // Update event details except payment_status
                    $stmt = $pdo->prepare("UPDATE booking_details SET event_address = ?, event_date = ?, guest_number = ?, food_type = ?, food_description = ?, sound_system = ?, decoration_description = ?, total_cost = ? WHERE event_id = ? AND user_name = ?");
                    $update_result = $stmt->execute([
                        $event_address,
                        $event_date,
                        $guest_number,
                        $food_type,
                        $food_description,
                        $sound_system,
                        $decoration_description,
                        $new_total_cost,
                        $event_id,
                        $_SESSION['user_name']
                    ]);

                    if (!$update_result) {
                        throw new PDOException("Failed to update event details.");
                    }

                    // Fetch current total paid (before any refund)
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(payment_amount), 0) as total_paid FROM payment_details WHERE event_id = ?");
                    $stmt->execute([$event_id]);
                    $current_total_paid = (float)$stmt->fetchColumn();

                    $due = $new_total_cost - $current_total_paid;
                    $new_payment_status = ($due <= 0) ? 'Paid' : 'Not Paid';
                    $refund_inserted = false;

                    if ($due < 0) {
                        // Process refund
                        $refund_amount = $due; // negative
                        $refund_transaction_id = uniqid('REF_');
                        $stmt = $pdo->prepare("INSERT INTO payment_details (user_name, event_id, payment_method, payment_amount, account_number, transaction_id) VALUES (?, ?, 'Refund', ?, 'Refund Processed', ?)");
                        $refund_result = $stmt->execute([$_SESSION['user_name'], $event_id, $refund_amount, $refund_transaction_id]);
                        if (!$refund_result) {
                            throw new PDOException("Failed to process refund.");
                        }
                        $refund_inserted = true;
                        // Now due effectively 0, status 'Paid'
                        $new_payment_status = 'Paid';
                    }

                    // Update payment_status
                    $stmt = $pdo->prepare("UPDATE booking_details SET payment_status = ? WHERE event_id = ? AND user_name = ?");
                    $status_result = $stmt->execute([$new_payment_status, $event_id, $_SESSION['user_name']]);
                    if (!$status_result) {
                        throw new PDOException("Failed to update payment status.");
                    }

                    $pdo->commit();

                    // Set flags for popups
                    if ($due > 0) {
                        $show_payment_popup = true;
                        $cost_difference = $due;
                    } elseif ($due < 0 && $refund_inserted) {
                        $show_refunded_popup = true;
                        $cost_difference = abs($due);
                    } else {
                        // No popup needed
                        header("Location: view_events.php?success=Event updated successfully");
                        exit();
                    }
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Error updating event: " . $e->getMessage();
                    error_log("Update error for event_id $event_id: " . $e->getMessage());
                }
            }
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Database error in update_event.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Event - EventPro</title>
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
        #paymentPopup .popup-content, #refundedPopup .popup-content, #errorPopup .popup-content {
            background: #e0f7fa;
            color: #1e293b;
            font-weight: 500;
        }
        #total_cost {
            text-align: center;
            font-weight: bold;
            margin-bottom: 1rem;
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
            <h2 class="form-title">Update Event</h2>
            <form method="POST" id="updateForm">
                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <input type="text" id="event_type" value="<?php echo htmlspecialchars($event['event_type']); ?>" disabled>
                    <input type="hidden" name="event_type" value="<?php echo htmlspecialchars($event['event_type']); ?>">
                </div>
                <div class="form-group">
                    <label for="event_address">Event Address</label>
                    <textarea id="event_address" name="event_address" required><?php echo isset($_POST['event_address']) ? htmlspecialchars($_POST['event_address']) : htmlspecialchars($event['event_address']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="event_date">Event Date and Time</label>
                    <input type="text" id="event_date" name="event_date" required value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : date('Y-m-d H:i', strtotime($event['event_date'])); ?>">
                </div>
                <div class="form-group">
                    <label for="guest_number">Number of Guests</label>
                    <input type="number" id="guest_number" name="guest_number" min="1" required value="<?php echo isset($_POST['guest_number']) ? htmlspecialchars($_POST['guest_number']) : htmlspecialchars($event['guest_number']); ?>">
                </div>
                <div class="form-group">
                    <label for="food_type">Food Type</label>
                    <select id="food_type" name="food_type" required>
                        <option value="Vegetarian" <?php echo (isset($_POST['food_type']) && $_POST['food_type'] == 'Vegetarian') || $event['food_type'] == 'Vegetarian' ? 'selected' : ''; ?>>Vegetarian</option>
                        <option value="Non-Vegetarian" <?php echo (isset($_POST['food_type']) && $_POST['food_type'] == 'Non-Vegetarian') || $event['food_type'] == 'Non-Vegetarian' ? 'selected' : ''; ?>>Non-Vegetarian</option>
                        <option value="Vegan" <?php echo (isset($_POST['food_type']) && $_POST['food_type'] == 'Vegan') || $event['food_type'] == 'Vegan' ? 'selected' : ''; ?>>Vegan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="food_description">Food Description</label>
                    <textarea id="food_description" name="food_description"><?php echo isset($_POST['food_description']) ? htmlspecialchars($_POST['food_description']) : htmlspecialchars($event['food_description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="sound_system">Sound System</label>
                    <select id="sound_system" name="sound_system">
                        <option value="None" <?php echo (isset($_POST['sound_system']) && $_POST['sound_system'] == 'None') || $event['sound_system'] == 'None' ? 'selected' : ''; ?>>No</option>
                        <option value="Basic" <?php echo (isset($_POST['sound_system']) && $_POST['sound_system'] == 'Basic') || $event['sound_system'] == 'Basic' ? 'selected' : ''; ?>>Yes (Basic)</option>
                        <option value="Premium" <?php echo (isset($_POST['sound_system']) && $_POST['sound_system'] == 'Premium') || $event['sound_system'] == 'Premium' ? 'selected' : ''; ?>>Yes (Premium)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="decoration_description">Decoration Description</label>
                    <textarea id="decoration_description" name="decoration_description"><?php echo isset($_POST['decoration_description']) ? htmlspecialchars($_POST['decoration_description']) : htmlspecialchars($event['decoration_description']); ?></textarea>
                </div>
                <p id="total_cost">Estimated Total Cost: $<?php echo number_format($event['total_cost'], 2); ?></p>
                <?php if ($error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <button type="submit" class="form-btn" id="submitBtn">Update Event</button>
            </form>
            <a href="view_events.php" class="back-btn">Back to Events</a>
        </div>
    </div>

    <!-- Popups -->
    <div class="popup" id="paymentPopup" style="display: none;">
        <div class="popup-content">
            <h3>Additional Payment Required</h3>
            <p id="paymentMessage"></p>
            <button class="popup-btn pay-now" onclick="payNow()">Pay Now</button>
            <button class="popup-btn cancel" onclick="payLater()">Pay Later</button>
        </div>
    </div>
    <div class="popup" id="refundedPopup" style="display: none;">
        <div class="popup-content">
            <p id="refundedMessage"></p>
            <button class="popup-btn" onclick="closeRefundedPopup()">OK</button>
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
            defaultDate: "<?php echo date('Y-m-d H:i', strtotime($event['event_date'])); ?>",
            onChange: function(selectedDates, dateStr, instance) {
                if (bookedDates.includes(dateStr)) {
                    showErrorPopup(`This date and time (${dateStr}) is already booked. Please select another date.`);
                    instance.clear();
                }
            }
        });

        // Popup elements
        const paymentPopup = document.getElementById("paymentPopup");
        const refundedPopup = document.getElementById("refundedPopup");
        const errorPopup = document.getElementById("errorPopup");
        const paymentMessage = document.getElementById("paymentMessage");
        const refundedMessage = document.getElementById("refundedMessage");
        const errorMessage = document.getElementById("errorMessage");

        // Show payment popup
        function showPaymentPopup(amount, eventId) {
            paymentMessage.textContent = `The updated event cost requires an additional payment of $${amount.toFixed(2)}. Please pay the due amount.`;
            paymentPopup.style.display = 'flex';
            window.currentEventId = eventId;
            document.body.style.overflow = 'hidden';
        }

        // Show refunded popup
        function showRefundedPopup(amount) {
            refundedMessage.textContent = `The updated event cost is $${amount.toFixed(2)} less. A refund of $${amount.toFixed(2)} has been processed.`;
            refundedPopup.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Pay Now → redirect to payment page with event_id and amount
        function payNow() {
            if (!window.currentEventId) {
                showErrorPopup("No event ID available for payment.");
                return;
            }
            paymentPopup.style.display = 'none';
            document.body.style.overflow = 'auto';
            window.location.href = 'payment.php?event_id=' + window.currentEventId + '&amount=' + <?php echo json_encode($cost_difference); ?>;
        }

        // Pay Later → redirect to view events
        function payLater() {
            paymentPopup.style.display = 'none';
            document.body.style.overflow = 'auto';
            window.location.href = 'view_events.php?success=Event updated successfully. Please pay the due amount later.';
        }

        // Close refunded popup
        function closeRefundedPopup() {
            refundedPopup.style.display = 'none';
            document.body.style.overflow = 'auto';
            window.location.href = 'view_events.php?success=Event updated successfully with refund processed.';
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

        // Cost calculation logic
        function calculateTotal() {
            const guestNumber = parseInt(document.getElementById('guest_number').value) || 0;
            const foodType = document.getElementById('food_type').value;
            const soundSystem = document.getElementById('sound_system').value;
            const decorationDesc = document.getElementById('decoration_description').value.trim();

            const basePerGuest = 5;
            const foodPrices = {
                'Vegetarian': 10,
                'Non-Vegetarian': 15,
                'Vegan': 12
            };
            const soundPrices = {
                'None': 0,
                'Basic': 500,
                'Premium': 1000
            };
            const decorationCost = decorationDesc.length > 0 ? 300 : 0;

            const foodCostPerGuest = foodPrices[foodType] || 0;
            const soundCost = soundPrices[soundSystem] || 0;

            const total = guestNumber * (basePerGuest + foodCostPerGuest) + soundCost + decorationCost;

            document.getElementById('total_cost').textContent = `Estimated Total Cost: $${total.toFixed(2)}`;
        }

        // Attach event listeners for cost updates
        document.addEventListener('DOMContentLoaded', function() {
            const fields = ['guest_number', 'food_type', 'sound_system', 'decoration_description'];
            fields.forEach(id => {
                const elem = document.getElementById(id);
                if (elem) {
                    elem.addEventListener('input', calculateTotal);
                    elem.addEventListener('change', calculateTotal);
                }
            });
            calculateTotal(); // Initial calculation

            // Show payment or refunded popup after successful update
            <?php if ($show_payment_popup): ?>
                showPaymentPopup(<?php echo $cost_difference; ?>, <?php echo $event_id; ?>);
            <?php elseif ($show_refunded_popup): ?>
                showRefundedPopup(<?php echo $cost_difference; ?>);
            <?php elseif (!empty($error)): ?>
                showErrorPopup("<?php echo addslashes($error); ?>");
            <?php endif; ?>
        });
    </script>
</body>
</html>