<?php
ini_set("display_errors", 1);
if (session_status() == PHP_SESSION_NONE) {
    require_once __DIR__ . '/../includes/session.php';
    session_init();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$title = $video_url = $age_category = $type_category = "";
$title_err = $video_url_err = $age_category_err = $type_category_err = "";

$age_categories = ["Children", "Teens", "Youth", "Adults"];
$type_categories = ["Movies", "Animation", "Ministration", "Languages"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $title_err = "Invalid session. Please refresh and try again.";
    }

    if (empty($title_err) && empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } elseif (empty($title_err)) {
        $title = trim($_POST["title"]);
    }

    if (empty($title_err) && empty(trim($_POST["video_url"]))) {
        $video_url_err = "Please enter a video URL.";
    } elseif (empty($title_err)) {
        $video_url = trim($_POST["video_url"]);
    }

    if (empty($title_err) && empty(trim($_POST["age_category"]))) {
        $age_category_err = "Please select an age category.";
    } elseif (empty($title_err)) {
        $age_category = trim($_POST["age_category"]);
    }

    if (empty($title_err) && empty(trim($_POST["type_category"]))) {
        $type_category_err = "Please select a type category.";
    } elseif (empty($title_err)) {
        $type_category = trim($_POST["type_category"]);
    }

    if (empty($title_err) && empty($video_url_err) && empty($age_category_err) && empty($type_category_err)) {
        $sql = "INSERT INTO praise_videos (title, video_url, age_category, type_category, created_at) VALUES (?, ?, ?, ?, NOW())";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $title, $video_url, $age_category, $type_category);

            if ($stmt->execute()) {
                $_SESSION['admin_message'] = "Video added successfully!";
                $_SESSION['admin_message_type'] = "success";
                header("location: manage_videos.php");
                exit;
            } else {
                $_SESSION['admin_message'] = "Error adding video: " . $conn->error;
                $_SESSION['admin_message_type'] = "danger";
                header("location: add_video.php"); // Redirect back to add_video with error
                exit;
            }
            $stmt->close();
        } else {
            $_SESSION['admin_message'] = "Error preparing statement: " . $conn->error;
            $_SESSION['admin_message_type'] = "danger";
            header("location: add_video.php");
            exit;
        }
    }
    // $conn->close(); // Moved to end of script
}

$conn->close(); // Connection closed here

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Video</title>
    <link rel="icon" href="../logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="admin-container container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center">Add New Video</h2>
                        <?php
                        if (isset($_SESSION['admin_message'])) {
                            echo '<div class="alert alert-' . $_SESSION['admin_message_type'] . ' text-center">' . $_SESSION['admin_message'] . '</div>';
                            unset($_SESSION['admin_message']);
                            unset($_SESSION['admin_message_type']);
                        }
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <div class="mb-3">
                                <label for="video_title" class="form-label">Title</label>
                                <input type="text" name="title" id="video_title" class="form-control form-control-dark <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                                <div class="invalid-feedback"><?php echo $title_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="video_url" class="form-label">Video URL</label>
                                <input type="text" name="video_url" id="video_url" class="form-control form-control-dark <?php echo (!empty($video_url_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $video_url; ?>">
                                <div class="invalid-feedback"><?php echo $video_url_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="video_age_category" class="form-label">Age Category</label>
                                <select name="age_category" id="video_age_category" class="form-select form-control-dark <?php echo (!empty($age_category_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Age Category</option>
                                    <?php foreach ($age_categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo ($age_category == $category) ? 'selected' : ''; ?>><?php echo $category; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $age_category_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="video_type_category" class="form-label">Type Category</label>
                                <select name="type_category" id="video_type_category" class="form-select form-control-dark <?php echo (!empty($type_category_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Type Category</option>
                                    <?php foreach ($type_categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo ($type_category == $category) ? 'selected' : ''; ?>><?php echo $category; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $type_category_err; ?></div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary-custom">Add Video</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
