<?php
    $serverName = "localhost";
    $hostName = "root";
    $passWord = "";
    $dataBaseName = "event_management";

    $conn = mysqli_connect($serverName, $hostName, $passWord, $dataBaseName);

    if ($conn){
        if (isset($_POST['hostDetails'])) {
            $sql = "INSERT INTO host_details (host_id, organization_name, host_name, phone_number, host_email) 
                    VALUES (
                        '{$_POST['hostID']}',
                        '{$_POST['organizationName']}',
                        '{$_POST['hostName']}',
                        '{$_POST['hostPhoneContact']}',
                        '{$_POST['hostEmail']}',
                    )";
            $sqlEvent = "INSERT INTO event_details (event_id, event_title, organization_name, event_type,event_address, data_time, event_status) 
                    VALUES (
                        '{$_POST['eventID']}',
                        '{$_POST['eventTitle']}',
                        '{$_POST['organizationName']}',
                        '{$_POST['eventType']}',
                        '{$_POST['eventAddress']}',
                        '{$_POST['eventDateTime']}',
                        '{$_POST['eventStatus']}'
                    )";
            mysqli_query($conn, $sqlEvent);
        } 
        elseif (isset($_POST['guestDetails'])) {
            $sql = "INSERT INTO guest_details(guest_ID, guest_name, guest_address, phone_number, guest_email, organization_name, host_name, data_time, attend_status)
                    VALUES (
                        '{$_POST['guestID']}',
                        '{$_POST['guestName']}',
                        '{$_POST['address']}',
                        '{$_POST['guestPhoneContact']}',
                        '{$_POST['guestEmail']}',
                        '{$_POST['organizationName']}',
                        '{$_POST['hostName']}',
                        '{$_POST['eventDateTime']}',
                        '{$_POST['attendStatus']}'
                    )";
        }
        elseif(isset($_POST['paymentDetails'])){
            $sql = "INSERT INTO payment_details(Payment_ID, User_ID, Event_ID, Payable_Amount, Payment_method, Date_Time, Payment_Status, Transaction_ID)
                    VALUES(
                        '{$_POST['paymentID']}',
                        '{$_POST['userID']}',
                        '{$_POST['eventID']}',
                        '{$_POST['payAmount']}',
                        '{$_POST['paymentMethod']}',
                        '{$_POST['dataTime']}',
                        '{$_POST['paymentStatus']}',
                        '{$_POST['transactionID']}'

                    )";
            echo "<p>Your payment is completed</p>";
        }
        if(mysqli_query($conn, $sql)){
            echo "<p>values are inserted</p>";
        }
        else{
            echo "<p>values are not inserted</p>";
        }
    }
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0;
        }
        div{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 50vh;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        div h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        button{
            padding: 15px 30px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }
        a{
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php
        if (isset($_POST['guestDetails'])){
            echo "
                <div class='payment-div'>
                    <h1>Click here to pay the due</h1>
                    <button name='paymentProcess'><a href='payment.html'>Payment</a></button>
                </div>
            ";
        }
        elseif(isset($_POST['hostDetails'])){
            echo "<p>thanks for registration an event</p>";
        }
    ?>
</body>