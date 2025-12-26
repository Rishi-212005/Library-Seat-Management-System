<?php
// Database connection (manually)


$host = "sql200.infinityfree.com";
$user = "if0_39418937";
$password = "library2025";
$dbname = "if0_39418937_library_management"; // <-- change this to your database name

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$allSeatIds = [];

// Add seats 1 to 80
for ($i = 1; $i <= 80; $i++) {
    $allSeatIds[] = (string)$i;
}

// Add computer seats c1 to c20
for ($i = 1; $i <= 20; $i++) {
    $allSeatIds[] = "c$i";
}


// Initialize all as available
$seats = [];
foreach ($allSeatIds as $seatId) {
    $seats[$seatId] = 'available';
}

// Fetch today’s bookings
$today = date('Y-m-d');
$sql = "SELECT seat_id, status FROM seat_bookings WHERE booking_date = '$today'";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $seatId = $row['seat_id'];
        $status = $row['status']; // 'booked', 'attended', 'cancelled'

        // Override only if seat exists in our layout
        if (isset($seats[$seatId])) {
            if ($status === 'booked') $seats[$seatId] = 'booked';
            elseif ($status === 'attended') $seats[$seatId] = 'attended';
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Librarian Seat View</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }
        .seat-grid {
            display: flex;
            flex-wrap: wrap;
            max-width: 400px;
        }
        .seat {
            width: 50px;
            height: 50px;
            margin: 6px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 10px;
            font-weight: bold;
            font-size: 16px;
            cursor: default;
            position: relative;
        }
        .available {
            border: 2px solid green;
            color: green;
        }
        .booked {
            border: 2px solid orange;
            color: orange;
        }
        .attended {
            border: 2px solid red;
            color: red;
        }
        .attended::after {
            content: "✓";
            position: absolute;
            top: -6px;
            right: -6px;
            background: red;
            color: white;
            border-radius: 50%;
            padding: 2px 5px;
            font-size: 12px;
        }
        .legend {
            margin-bottom: 20px;
        }
        .legend-item {
            margin-bottom: 10px;
        }
        .legend .seat {
            margin-right: 10px;
        }
    </style>
</head>
<body>

<h2>Seat Status Legend</h2>
<div class="legend">
    <div class="legend-item"><div class="seat available">A</div> Available</div>
    <div class="legend-item"><div class="seat booked">B</div> Booked (waiting)</div>
    <div class="legend-item"><div class="seat attended">A</div> Attended (✓)</div>
</div>

<h2>Today's Seat Status</h2>
<div class="seat-grid">
    <?php
    foreach ($seats as $seatId => $status) {
        echo "<div class='seat $status'>" . htmlspecialchars($seatId) . "</div>";
    }
    ?>
</div>

</body>
</html>
