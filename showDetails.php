<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
        }
        table th, table td {
            padding: 15px 20px;
            text-align: left;
            color: #4a5568;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        table tr:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        #searchInput {
            width: 100%;
            max-width: 400px;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 25px;
            border: none;
            outline: none;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            font-size: 16px;
        }
        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
        .top-links {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>Event Management System</h1>
        <div class="nav-menu">
            <a class="nav-btn" href="index.html">Home</a>
            <a class="nav-btn" href="guest.html">Add Guest</a>
            <a class="nav-btn" href="host.html">Add Host</a>
            <a class="nav-btn" href="payment.html">Add Payment</a>
            <div class="dropdown">
                <button class="nav-btn dropdown-btn">View Data ▼</button>
                <div class="dropdown-content">
                    <form action="showDetails.php" method="POST">
                        <button name="showHostDetails">Host Detail</button>
                        <button name="showGuestDetails">Guest Detail</button>
                        <button name="showPaymentDetails">Payment Detail</button>
                        <button name="showEventDetails">Event Detail</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <?php
            
            if(isset($_POST["showGuestDetails"]) || (isset($_GET['role']) && $_GET['role'] === "guest")){
                echo "<h2>Guest Records</h2>";
            }
            elseif (isset($_POST["showHostDetails"]) || (isset($_GET['role']) && $_GET['role'] === "host")) {
                echo "<h2>Host Records</h2>";
            }
            elseif(isset($_POST["showEventDetails"]) || (isset($_GET['role']) && $_GET['role'] === "event")){
                echo "<h2>Event Records</h2>";
            }
            elseif (isset($_POST["showPaymentDetails"]) || (isset($_GET['role']) && $_GET['role'] === "payment")) {
                echo "<h2>Payment Records</h2>";
            }
        
            $conn = new mysqli("localhost", "root", "", "event_management");
            if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

            echo '<input type="text" id="searchInput" placeholder="Search records...">';
            echo "<table>";
            echo "<tr>";

            // Table headers
            if (isset($_POST["showGuestDetails"]) || (isset($_GET['role']) && $_GET['role'] === "guest")) {
                echo "<th>Guest ID</th><th>Name</th><th>Address</th><th>Phone</th><th>Email</th><th>Organization</th><th>Event ID</th><th>Event Title</th><th>Action</th><th>Download Receipt</th>";
            } 
            elseif (isset($_POST["showHostDetails"]) || (isset($_GET['role']) && $_GET['role'] === "host")) {
                echo "<th>Host ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Organization</th><th>Action</th><th>Download Receipt</th>";
            } 
            elseif (isset($_POST["showEventDetails"]) || (isset($_GET['role']) && $_GET['role'] === "event")) {
                echo "<th>Event ID</th><th>Event Title</th><th>Event Type</th><th>Host ID</th><th>Host Name</th><th>Address</th><th>Date</th><th>Status</th><th>Action</th>";
            } 
            elseif (isset($_POST["showPaymentDetails"]) || (isset($_GET['role']) && $_GET['role'] === "payment")) {
                echo "<th>Payment ID</th><th>Amount</th><th>Date</th><th>Host ID</th><th>Host Name</th><th>Action</th>";
            }

            echo "</tr>";

            // Fetch Guests
            if (isset($_POST["showGuestDetails"]) || (isset($_GET['role']) && $_GET['role'] === "guest")) {
                $sql = "SELECT g.*, e.event_id, e.event_title 
                        FROM guest_details g
                        LEFT JOIN event_guest eg ON g.guest_id = eg.guest_id
                        LEFT JOIN event_details e ON eg.event_id = e.event_id";

                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['guest_id']}</td>
                            <td>{$row['guest_name']}</td>
                            <td>{$row['guest_address']}</td>
                            <td>{$row['phone_number']}</td>
                            <td>{$row['guest_email']}</td>
                            <td>{$row['organization_name']}</td>
                            <td>{$row['event_id']}</td>
                            <td>{$row['event_title']}</td>
                            <td><a href='update.php?role=guest&id={$row['guest_id']}'>Update</a> | 
                                <a href='delete.php?role=guest&id={$row['guest_id']}'>Delete</a></td>
                            <td><a href='receipt.php?role=guest&id={$row['guest_id']}'>Download</a></td>
                          </tr>";
                }
            }


            // Fetch Hosts
            elseif (isset($_POST["showHostDetails"]) || (isset($_GET['role']) && $_GET['role'] === "host")) {
                $sql = "SELECT * FROM host_details";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['host_id']}</td>
                            <td>{$row['host_name']}</td>
                            <td>{$row['phone_number']}</td>
                            <td>{$row['host_email']}</td>
                            <td>{$row['organization_name']}</td>
                            <td><a href='update.php?role=host&id={$row['host_id']}'>Update</a> | 
                                <a href='delete.php?role=host&id={$row['host_id']}'>Delete</a></td>
                            <td><a href='receipt.php?role=host&id={$row['host_id']}'>Download</a></td>
                          </tr>";
                }
            }

            // Fetch Events
            elseif (isset($_POST["showEventDetails"]) || (isset($_GET['role']) && $_GET['role'] === "event")) {
                $sql = "SELECT e.*, h.host_id, h.host_name 
                        FROM event_details e 
                        LEFT JOIN host_details h ON e.host_id = h.host_id";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['event_id']}</td>
                            <td>{$row['event_title']}</td>
                            <td>{$row['event_type']}</td>
                            <td>{$row['host_id']}</td>
                            <td>{$row['host_name']}</td>
                            <td>{$row['event_address']}</td>
                            <td>{$row['event_date']}</td>
                            <td>{$row['event_status']}</td>
                            <td><a href='update.php?role=event&id={$row['event_id']}'>Update</a> | 
                                <a href='delete.php?role=event&id={$row['event_id']}'>Delete</a></td>
                          </tr>";
                }
            }

            // Fetch Payments
            elseif (isset($_POST["showPaymentDetails"]) || (isset($_GET['role']) && $_GET['role'] === "payment")) {
                $sql = "SELECT p.*, h.host_id, h.host_name 
                        FROM payment_details p 
                        LEFT JOIN host_details h ON p.host_id = h.host_id";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['payment_id']}</td>
                            <td>{$row['payable_amount']}</td>
                            <td>{$row['payment_date']}</td>
                            <td>{$row['host_id']}</td>
                            <td>{$row['host_name']}</td>
                            <td><a href='update.php?role=payment&id={$row['payment_id']}'>Update</a> | 
                                <a href='delete.php?role=payment&id={$row['payment_id']}'>Delete</a></td>
                          </tr>";
                }
            }

            echo "</table>";
            $conn->close();
        ?>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <p>Event Management System</p>
            <p>&copy; <?php echo date('Y'); ?> All Rights Reserved</p>
        </div>
    </div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function(){
        const filter = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('table tr:not(:first-child)');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
    document.addEventListener("DOMContentLoaded", () => {
            const dropdownBtn = document.querySelector(".dropdown-btn");
            const dropdownContent = document.querySelector(".dropdown-content");

            dropdownBtn.addEventListener("click", () => {
                dropdownContent.classList.toggle("show");
            });

            // Optional: close dropdown if clicked outside
            window.addEventListener("click", (e) => {
                if (!e.target.matches(".dropdown-btn")) {
                    if (dropdownContent.classList.contains("show")) {
                        dropdownContent.classList.remove("show");
                    }
                }
            });
        });
</script>
</body>
</html>