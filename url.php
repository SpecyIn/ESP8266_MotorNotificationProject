<?php
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

date_default_timezone_set("Asia/Kolkata");

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

$expectedAuthKey = 'auth_key';
if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches) || $matches[1] !== $expectedAuthKey) {
    http_response_code(403);
    die("Forbidden: Invalid Authorization Key");
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['type'])) {
    $type = $_GET['type'];

    if ($type !== 'api_type') {
        http_response_code(400);
        die("Invalid API Type.");
    }

    $currentTime = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO table_name (Time) VALUES (?)");
    $stmt->bind_param("s", $currentTime);
    if ($stmt->execute()) {
        echo "Time data stored successfully.";

        $stmt = $conn->prepare("SELECT Time FROM table_name ORDER BY Time DESC LIMIT 1,1");
        $stmt->execute();
        $result = $stmt->get_result();
        $lastLog = $result->fetch_assoc();

        if ($lastLog) {
            $lastLogTime = new DateTime($lastLog['Time']);
            $currentTimeObj = new DateTime($currentTime);
            $interval = $lastLogTime->diff($currentTimeObj);

            if ($interval->i < 30 && $interval->d == 0) {
                echo "A notification was sent within the last 30 minutes. Skipping message.";
            } else {
                sendTelegramNotification($currentTime);
            }
        } else {
            sendTelegramNotification($currentTime);
        }
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    http_response_code(400);
    die("Invalid request.");
}

$conn->close();

function sendTelegramNotification($message) {
    $botToken = 'bot-tokenkey';
    $chatId = 'chat-id';

    $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage";
    $text = "Motor Started at time : $message\nTrack at: https://domain.com/status.php";
	
    $data = [
        'chat_id' => $chatId,
        'text' => $text
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Error sending message: " . curl_error($ch);
    } else {
        echo "Message sent to Telegram successfully!";
    }

    curl_close($ch);
}
?>