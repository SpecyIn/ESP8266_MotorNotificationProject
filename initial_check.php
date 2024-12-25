<?php

$host = 'localhost';
$user = '';
$password = '';
$db = '';
$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT time FROM table_name ORDER BY time DESC LIMIT 1";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $lastUpdate = $row['time'];

    $status = 'ON';
} else {

    $lastUpdate = 'No history available';
    $status = 'OFF';
}

if ($lastUpdate != 'No history available') {
    $lastUpdate = date('Y-m-d H:i:s', strtotime($lastUpdate));  
}

$sqlHistory = "SELECT time FROM table_name ORDER BY time DESC LIMIT 100"; 
$historyResult = mysqli_query($conn, $sqlHistory);
$history = [];

while ($historyRow = mysqli_fetch_assoc($historyResult)) {
    $history[] = [
        'timestamp' => date('Y-m-d H:i:s', strtotime($historyRow['time'])), 
        'status' => 'ON'  
    ];
}

if (empty($history)) {
    $history[] = [
        'timestamp' => 'N/A',  
        'status' => 'OFF'
    ];
}

$response = [
    'status' => $status,
    'last_update' => $lastUpdate, 
    'history' => $history
];

echo json_encode($response);

mysqli_close($conn);
?>