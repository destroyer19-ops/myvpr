<?php
ini_set("display_errors", 1);
require_once 'includes/session.php';
session_init();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For non-authenticated users, redirect to login page.
    // Do not output JSON here, as this is now a page request.
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

// Check user's subscription status
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT end_date FROM subscriptions WHERE user_id = ? ORDER BY end_date DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$subscription = $result->fetch_assoc();
$stmt->close();

$is_subscribed = false;
if ($subscription) {
    $end_date = new DateTime($subscription['end_date']);
    $now = new DateTime();
    if ($end_date > $now) {
        $is_subscribed = true;
    }
}

// Update the user's subscription_status in praise_users table for consistency
$new_status = $is_subscribed ? 'active' : 'inactive';
$stmt_update = $conn->prepare("UPDATE praise_users SET subscription_status = ? WHERE id = ?");
$stmt_update->bind_param("si", $new_status, $user_id);
$stmt_update->execute();
$stmt_update->close();


if (!$is_subscribed) {
    header('Location: subscribe.php?error=not_subscribed');
    exit;
}

$user_id = $_SESSION['user_id'];
$meeting_code = ''; // Initialize
$title = ''; // Initialize

// Handle POST request for meeting creation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid session. Please refresh and try again.';
        header('Location: create_meeting.php');
        exit;
    }
    $title = isset($_POST['title']) ? trim($_POST['title']) : 'Untitled Meeting';
    // $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30; // Not used in current DB schema

    // Generate a unique meeting code (8 characters)
    function generateMeetingCode($length = 8) {
        return substr(bin2hex(random_bytes(ceil($length / 2))), 0, $length);
    }

    $meeting_code = generateMeetingCode();
    $max_attempts = 10;
    $attempt = 0;

    $stmt_check_code = $conn->prepare("SELECT id FROM praise_meetings WHERE meeting_code = ?");

    while ($attempt < $max_attempts) {
        $stmt_check_code->bind_param("s", $meeting_code);
        $stmt_check_code->execute();
        $result_check_code = $stmt_check_code->get_result();

        if ($result_check_code->num_rows == 0) {
            break; // Code is unique
        }

        $meeting_code = generateMeetingCode();
        $attempt++;
    }
    $stmt_check_code->close();

    if ($attempt >= $max_attempts) {
        // Fallback if unique code generation fails
        $_SESSION['error_message'] = 'Failed to generate a unique meeting code. Please try again.';
        header('Location: create_meeting.php'); // Redirect back to form with error
        exit;
    }

    // Insert meeting into database
    $stmt_insert = $conn->prepare("INSERT INTO praise_meetings (user_id, title, meeting_code, start_time, status, created_at) VALUES (?, ?, ?, NOW(), 'in-progress', NOW())");
    $stmt_insert->bind_param("iss", $user_id, $title, $meeting_code);

    if ($stmt_insert->execute()) {
        $meeting_id = $stmt_insert->insert_id;
        $_SESSION['success_message'] = 'Meeting created successfully!';
        $_SESSION['current_meeting_code'] = $meeting_code; // Store for immediate access
        header('Location: meeting-room.php?code=' . $meeting_code); // Redirect to meeting room
        exit;
    } else {
        $_SESSION['error_message'] = 'Failed to create meeting: ' . $conn->error;
        header('Location: create_meeting.php'); // Redirect back to form with error
        exit;
    }
    $stmt_insert->close();
}

$conn->close();

// Display the form for GET requests
?>
<?php include 'includes/header.php'; ?>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">Create New Meeting</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="create_meeting.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">Meeting Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                            </div>
                            <!-- Add other meeting settings here if needed -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Create Meeting</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/bottom_navbar.php'; ?>
<?php include 'includes/footer.php'; ?>
