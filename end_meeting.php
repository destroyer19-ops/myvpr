<?php
ini_set("display_errors", 1);
require_once 'includes/session.php';
session_init();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Get database connection
require_once 'includes/db.php';

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid session. Please refresh and try again.']);
    exit;
}

// Get request parameters
$meeting_id = isset($_POST['meeting_id']) ? trim($_POST['meeting_id']) : '';
$user_id = $_SESSION['user_id'];

// Validate meeting ID
if (empty($meeting_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Meeting ID is required']);
    exit;
}

// Find the meeting ID from the meeting code
$stmt = $conn->prepare("SELECT id FROM praise_meetings WHERE meeting_code = ?");
$stmt->bind_param("s", $meeting_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid meeting ID']);
    exit;
}

$meeting = $result->fetch_assoc();
$meeting_db_id = $meeting['id'];

// Update the latest meeting session for this meeting and user
$stmt = $conn->prepare("
    UPDATE praise_meeting_sessions 
    SET end_time = NOW() 
    WHERE meeting_id = ? AND user_id = ? AND end_time IS NULL 
    ORDER BY start_time DESC 
    LIMIT 1
");
$stmt->bind_param("ii", $meeting_db_id, $user_id);

if ($stmt->execute()) {
    $response = [
        'success' => true,
        'message' => 'Meeting session ended successfully'
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Failed to end meeting session: ' . $conn->error
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
