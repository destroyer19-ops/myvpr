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

$title = $description = $stream_url = "";
$title_err = $description_err = $stream_url_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        $title_err = "Invalid session. Please refresh and try again.";
    }
    if (empty($title_err) && empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } elseif (empty($title_err)) {
        $title = trim($_POST["title"]);
    }

    if (empty($title_err) && empty(trim($_POST["description"]))) {
        $description_err = "Please enter a description.";
    } elseif (empty($title_err)) {
        $description = trim($_POST["description"]);
    }

    if (empty($title_err) && empty(trim($_POST["stream_url"]))) {
        $stream_url_err = "Please enter a stream URL.";
    } elseif (empty($title_err)) {
        $stream_url = trim($_POST["stream_url"]);
    }

    $is_live = isset($_POST['is_live']) ? 1 : 0;

    if (empty($title_err) && empty($description_err) && empty($stream_url_err)) {
        $sql = "INSERT INTO praise_live_tv (title, description, stream_url, is_live, created_at) VALUES (?, ?, ?, ?, NOW())";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $title, $description, $stream_url, $is_live);

            if ($stmt->execute()) {
                header("location: index.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Live Stream</title>
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
                        <h2 class="card-title text-center">Add New Live Stream</h2>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>">
                                <div class="invalid-feedback"><?php echo $title_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo $description; ?></textarea>
                                <div class="invalid-feedback"><?php echo $description_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="stream_url" class="form-label">Stream URL</label>
                                <input type="text" name="stream_url" class="form-control <?php echo (!empty($stream_url_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $stream_url; ?>">
                                <div class="invalid-feedback"><?php echo $stream_url_err; ?></div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="is_live" class="form-check-input" id="is_live">
                                <label class="form-check-label" for="is_live">Go Live Now</label>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Add Stream</button>
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
