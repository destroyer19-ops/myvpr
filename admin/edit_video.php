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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$title = $video_url = $age_category = $type_category = "";
$title_err = $video_url_err = $age_category_err = $type_category_err = "";

$age_categories = ["Children", "Teens", "Youth", "Adults"];
$type_categories = ["Movies", "Animation", "Ministration", "Languages"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $title_err = "Invalid session. Please refresh and try again.";
    }
    $id = intval($_POST['id']);

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
        $sql = "UPDATE praise_videos SET title = ?, video_url = ?, age_category = ?, type_category = ? WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssi", $title, $video_url, $age_category, $type_category, $id);

            if ($stmt->execute()) {
                header("location: manage_videos.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
} else {
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM praise_videos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $video = $result->fetch_assoc();
            $title = $video['title'];
            $video_url = $video['video_url'];
            $age_category = $video['age_category'];
            $type_category = $video['type_category'];
        } else {
            echo "Video not found.";
            exit;
        }
        $stmt->close();
    } else {
        echo "Invalid video ID.";
        exit;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Video</title>
    <link rel="icon" href="../logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #1de9b6;
            --dark-color: #263238;
            --light-color: #f4f7f6;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-color);
        }

        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-container {
            padding: 80px 0;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-video me-2"></i>Admin Panel
            </a>
        </div>
    </nav>

    <div class="admin-container container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center">Edit Video</h2>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                                <div class="invalid-feedback"><?php echo $title_err; ?></div>
                            </div>
                                <div class="mb-3">
                                    <label for="video_url" class="form-label">Video URL</label>
                                    <input type="text" name="video_url" class="form-control <?php echo (!empty($video_url_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $video_url; ?>">
                                    <div class="invalid-feedback"><?php echo $video_url_err; ?></div>
                                </div>
                            <div class="mb-3">
                                <label for="age_category" class="form-label">Age Category</label>
                                <select name="age_category" class="form-select <?php echo (!empty($age_category_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Age Category</option>
                                    <?php foreach ($age_categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo ($age_category == $category) ? 'selected' : ''; ?>><?php echo $category; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $age_category_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="type_category" class="form-label">Type Category</label>
                                <select name="type_category" class="form-select <?php echo (!empty($type_category_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select Type Category</option>
                                    <?php foreach ($type_categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo ($type_category == $category) ? 'selected' : ''; ?>><?php echo $category; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $type_category_err; ?></div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Update Video</button>
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
