<?php
    $serverName = "localhost";
    $hostName = "root";
    $password = "";
    $dataBaseName = "event_management"; // your database name in localhost

    $conn = mysqli_connect($serverName, $hostName, $password, $dataBaseName);

    if($conn -> connect_error){
        die("connection failed : ". $conn -> connect_error);
    }
?>
<head>
    <style>
        table {
            width: 90%;
            margin: 30px auto;
            border-collapse: collapse;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        table th {
            background-color: #4CAF50;
            color: white;
            text-align: left;
            padding: 12px 15px;
        }
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .header {
            background-color: #444;
            color: #ccc;
            text-align: center;
            padding: 10px 20px;
            font-size: 20px;
            margin-bottom: 30px;
        }

        .action-button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            margin: 0 3px;
        }

        .delete-button {
            background-color: red;
        }

        .update-button {
            background-color: #007bff;
            text-decoration: none;
        }

        footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: gray;
        }
    </style>
</head>
<body>
    <?php
        if(isset($_POST['hostDetail'])){
            $sql = "SELECT host_id, host_name, phone_number, host_email FROM host_details";
            $result = $conn -> query($sql);
            if ($result -> num_rows > 0){
                echo "<table>
                    <tr>
                        <th>Host ID</th>
                        <th>Host Name</th>
                        <th>Host Email</th>
                        <th>Phone Number</th>
                        <th>Action</th>
                    </tr>";

                while($row = $result -> fetch_assoc()) {
                    echo "<tr>
                        <td>" . $row["host_id"] . "</td>
                        <td>" . $row["organization_name"] . "</td>
                        <td>" . $row["host_name"] . "</td>
                        <td>" . $row["host_email"] . "</td>
                        <td>" . $row["phone_number"] . "</td>
                        <td>
                            <!-- Update Button -->
                            <a href='update_data.php?id={$row['ID']}' name='hostDetailUpdate' class='action-button update-button'>Update</a>

                            <!-- Delete Button -->
                            <form method='POST' action='delete.php' style='display:inline;'>
                                <input type='hidden' name='id' value='{$row['ID']}'>
                                <button type='submit' name='hostDetailDelete' class='action-button delete-button'>Delete</button>
                            </form>
                        </td>
                    </tr>";
                }
                echo "</table>";
            } 
            else {
                echo "<p>0 results</p>";
            }
        }
        elseif(isset($_POST['guestDetail'])){
            $sql = "SELECT guest_id, guest_name, guest_address, phone_number, guest_email, attend_status FROM guest_details";
            $result = $conn -> query($sql);
            if ($result -> num_rows > 0){
                echo "<table>
                    <tr>
                        <th>Guest ID</th>
                        <th>Guest Name</th>
                        <th>Guest Address</th>
                        <th>Phone Number</th>
                        <th>Guest Email</th>
                        <th>Attend Status</th>
                        <th>Action</th>
                    </tr>";

                while($row = $result -> fetch_assoc()) {
                    echo "<tr>
                        <td>" . $row["guest_ID"] . "</td>
                        <td>" . $row["guest_name"] . "</td>
                        <td>" . $row["guest_address"] . "</td>
                        <td>" . $row["phone_number"] . "</td>
                        <td>" . $row["guest_email"] . "</td>
                        <td>" . $row["attend_status"] . "</td>
                        <td>
                            <!-- Update Button -->
                            <a href='update_data.php?id={$row['ID']}' name='guestDetailUpdate' class='action-button update-button'>Update</a>

                            <!-- Delete Button -->
                            <form method='POST' action='delete.php' style='display:inline;'>
                                <input type='hidden' name='id' value='{$row['ID']}'>
                                <button type='submit' name='guestDetailDelete' class='action-button delete-button'>Delete</button>
                            </form>
                        </td>
                    </tr>";
                }
                echo "</table>";
            } 
            else {
                echo "<p>0 results</p>";
            }
        }
        elseif(isset($_POST['paymentDetail'])){
            $sql = "SELECT Payment_ID, User_ID, Event_ID, Payable_Amount, Payment_method, Date_Time, Payment_Status, Transaction_ID FROM payment_details";
            $result = $conn -> query($sql);
            if ($result -> num_rows > 0){
                echo "<table>
                    <tr>
                        <th>Payment ID</th>
                        <th>User ID</th>
                        <th>Event ID</th>
                        <th>Payable Amount</th>
                        <th>Payment Method</th>
                        <th>Date Time</th>
                        <th>Transaction ID</th>
                        <th>Action</th>
                    </tr>";

                while($row = $result -> fetch_assoc()) {
                    echo "<tr>
                        <td>" . $row["Payment_ID"] . "</td>
                        <td>" . $row["User_ID"] . "</td>
                        <td>" . $row["Event_ID"] . "</td>
                        <td>" . $row["Payable_Amount"] . "</td>
                        <td>" . $row["Payment_method"] . "</td>
                        <td>" . $row["Date_Time"] . "</td>
                        <td>" . $row["Transaction_ID"] . "</td>
                        <td>
                            <!-- Update Button -->
                            <a href='update_data.php?id={$row['ID']}' class='action-button update-button'>Update</a>

                            <!-- Delete Button -->
                            <form method='POST' action='delete.php' style='display:inline;'>
                                <input type='hidden' name='id' value='{$row['ID']}'>
                                <button type='submit' class='action-button delete-button'>Delete</button>
                            </form>
                        </td>
                    </tr>";
                }
                echo "</table>";
            } 
            else {
                echo "<p>0 results</p>";
            }
        }
        elseif(isset($_POST['eventDetail'])){
            $sql = "SELECT event_id, event_title, organization_name, event_type, event_address, data_time, event_status FROM host_details";
            $result = $conn -> query($sql);
            if ($result -> num_rows > 0){
                echo "<table>
                    <tr>
                        <th>Event ID</th>
                        <th>Event Title</th>
                        <th>Organization Name</th>
                        <th>Event Type</th>
                        <th>Event Address</th>
                        <th>Data Time</th>
                        <th>Event Status</th>
                        <th>Action</th>
                    </tr>";

                while($row = $result -> fetch_assoc()) {
                    echo "<tr>
                        <td>" . $row["event_id"] . "</td>
                        <td>" . $row["event_title"] . "</td>
                        <td>" . $row["organization_name"] . "</td>
                        <td>" . $row["event_type"] . "</td>
                        <td>" . $row["event_address"] . "</td>
                        <td>" . $row["data_time"] . "</td>
                        <td>" . $row["event_status"] . "</td>
                        <td>
                            <!-- Update Button -->
                            <a href='update_data.php?id={$row['ID']}' class='action-button update-button'>Update</a>

                            <!-- Delete Button -->
                            <form method='POST' action='delete.php' style='display:inline;'>
                                <input type='hidden' name='id' value='{$row['ID']}'>
                                <button type='submit' class='action-button delete-button'>Delete</button>
                            </form>
                        </td>
                    </tr>";
                }
                echo "</table>";
            } 
            else {
                echo "<p>0 results</p>";
            }
        }
    ?>
</body>