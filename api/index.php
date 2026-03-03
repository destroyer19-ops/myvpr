<?php
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = get_request_path();
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

if ($path === '/' && $method === 'GET') {
    json_response(['success' => true, 'message' => 'API online']);
}

if ($method === 'GET' && $path === '/csrf') {
    json_response(['success' => true, 'csrf_token' => csrf_token()]);
}

if ($method === 'POST' && $path === '/auth/login') {
    $data = get_request_data();
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if ($username === '' || $password === '') {
        json_response(['success' => false, 'message' => 'Username and password are required.'], 422);
    }

    $client_ip = get_client_ip();
    $rate_key = 'api_login:' . $client_ip . ':' . strtolower($username);
    if (!rate_limit_allow($conn, $rate_key, 5, 900)) {
        json_response(['success' => false, 'message' => 'Too many login attempts. Please try again later.'], 429);
    }

    $sql = "SELECT id, username, password, account_type FROM praise_users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        json_response(['success' => false, 'message' => 'Database error.'], 500);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows !== 1) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
    }

    $stmt->bind_result($id, $uname, $hashed_password, $account_type);
    $stmt->fetch();
    if (!password_verify($password, $hashed_password)) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
    }
    $stmt->close();
    rate_limit_reset($conn, $rate_key);

    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $uname;
    $_SESSION['account_type'] = $account_type;

    json_response([
        'success' => true,
        'user' => [
            'id' => $id,
            'username' => $uname,
            'account_type' => $account_type,
        ],
        'csrf_token' => csrf_token(),
    ]);
}

if ($method === 'POST' && $path === '/auth/register') {
    $data = get_request_data();
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');
    $account_type = trim($data['account_type'] ?? 'individual');
    $kc_handle = trim($data['kc_handle'] ?? '');
    $country = trim($data['country'] ?? '');
    $city = trim($data['city'] ?? '');
    $region = trim($data['region'] ?? '');
    $satellite_campus = trim($data['satellite_campus'] ?? '');
    $church = trim($data['church'] ?? '');

    if ($username === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        json_response(['success' => false, 'message' => 'Invalid username.'], 422);
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email.'], 422);
    }
    if ($password === '' || strlen($password) < 6) {
        json_response(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
    }

    $stmt = $conn->prepare("SELECT id FROM praise_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Username already taken.'], 409);
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM praise_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Email already registered.'], 409);
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO praise_users (username, email, password, account_type, kc_handle, country, city, region, satellite_campus, church, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        json_response(['success' => false, 'message' => 'Database error.'], 500);
    }
    $stmt->bind_param("ssssssssss", $username, $email, $hashed_password, $account_type, $kc_handle, $country, $city, $region, $satellite_campus, $church);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to register.'], 500);
    }
    $user_id = $stmt->insert_id;
    $stmt->close();

    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['account_type'] = $account_type;

    json_response([
        'success' => true,
        'user' => [
            'id' => $user_id,
            'username' => $username,
            'account_type' => $account_type,
        ],
        'csrf_token' => csrf_token(),
    ], 201);
}

if ($method === 'POST' && $path === '/auth/forgot') {
    $data = get_request_data();
    $email = trim($data['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email.'], 422);
    }

    $client_ip = get_client_ip();
    $rate_key = 'api_forgot:' . $client_ip;
    if (!rate_limit_allow($conn, $rate_key, 5, 900)) {
        json_response(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
    }

    $sql = "SELECT id FROM praise_users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id);
                $stmt->fetch();
                $reset_token = bin2hex(random_bytes(32));
                $hashed_reset_token = password_hash($reset_token, PASSWORD_DEFAULT);
                $expiry_time = date("Y-m-d H:i:s", strtotime('+1 hour'));

                $update_sql = "UPDATE praise_users SET reset_token = ?, reset_expiry = ? WHERE id = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("ssi", $hashed_reset_token, $expiry_time, $user_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                $reset_link = base_url() . "/reset_password.php?token=" . $reset_token . "&email=" . urlencode($email);
                error_log("Password reset link for $email: $reset_link");
            }
        }
        $stmt->close();
    }

    json_response(['success' => true, 'message' => 'If an account exists, a reset link has been sent.']);
}

if ($method === 'POST' && preg_match('#^/crusades/([A-Za-z0-9]+)/end$#', $path, $matches)) {
    require_auth();
    require_csrf();
    $code = $matches[1];

    $stmt = $conn->prepare("SELECT id, user_id FROM praise_crusades WHERE crusade_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $crusade = $result->fetch_assoc();
    $stmt->close();

    if (!$crusade) {
        json_response(['success' => false, 'message' => 'Invalid crusade code.'], 404);
    }
    if ((int)$crusade['user_id'] !== (int)$_SESSION['user_id']) {
        json_response(['success' => false, 'message' => 'Not authorized.'], 403);
    }

    $stmt = $conn->prepare("UPDATE praise_crusades SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $crusade['id']);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to end crusade.'], 500);
    }
    $stmt->close();
    json_response(['success' => true]);
}

if ($method === 'POST' && $path === '/auth/logout') {
    require_auth();
    require_csrf();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    json_response(['success' => true]);
}

if ($method === 'GET' && $path === '/comments') {
    $property = trim($_GET['property'] ?? '');
    if ($property === '') {
        json_response(['success' => false, 'message' => 'Property is required.'], 422);
    }
    $limit = (int)($_GET['limit'] ?? 30);
    if ($limit < 1 || $limit > 100) {
        $limit = 30;
    }

    $stmt = $conn->prepare("SELECT id, date, time, property, name, xid, comment FROM comments WHERE property = ? ORDER BY id DESC LIMIT ?");
    $stmt->bind_param("si", $property, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();

    $comments = array_reverse($comments);
    json_response(['success' => true, 'comments' => $comments]);
}

if ($method === 'GET' && $path === '/comments/updates') {
    $property = trim($_GET['property'] ?? '');
    $since_id = (int)($_GET['since_id'] ?? 0);
    $xid = trim($_GET['xid'] ?? '');
    if ($property === '') {
        json_response(['success' => false, 'message' => 'Property is required.'], 422);
    }

    $stmt = $conn->prepare("SELECT id, date, time, property, name, xid, comment FROM comments WHERE property = ? AND id > ? AND xid != ? ORDER BY id ASC");
    $stmt->bind_param("sis", $property, $since_id, $xid);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();

    json_response(['success' => true, 'comments' => $comments]);
}

if ($method === 'POST' && $path === '/comments') {
    $data = get_request_data();
    $comment = trim($data['comment'] ?? '');
    $name = trim($data['name'] ?? '');
    $property = trim($data['property'] ?? '');
    $xid = trim($data['xid'] ?? '');

    if ($comment === '' || $name === '' || $property === '' || $xid === '') {
        json_response(['success' => false, 'message' => 'comment, name, property, and xid are required.'], 422);
    }

    $client_ip = get_client_ip();
    $rate_key = 'comments:' . $client_ip . ':' . strtolower($property);
    if (!rate_limit_allow($conn, $rate_key, 20, 300)) {
        json_response(['success' => false, 'message' => 'Too many comments. Please slow down.'], 429);
    }

    $profane = get_profane_words();
    foreach ($profane as $bad) {
        if (strcasecmp($bad, $comment) === 0) {
            json_response(['success' => false, 'message' => 'This comment contains censored words.'], 422);
        }
    }

    $date = strtotime(date('d M Y'));
    $time = date('h:i:a');
    $clean_comment = strip_tags($comment);
    $clean_name = strip_tags($name);
    $clean_property = strip_tags($property);
    $clean_xid = strip_tags($xid);

    $stmt = $conn->prepare("INSERT INTO comments (date, time, property, name, xid, comment) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $date, $time, $clean_property, $clean_name, $clean_xid, $clean_comment);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to add comment.'], 500);
    }
    $id = $stmt->insert_id;
    $stmt->close();

    json_response([
        'success' => true,
        'comment' => [
            'id' => $id,
            'date' => $date,
            'time' => $time,
            'property' => $clean_property,
            'name' => $clean_name,
            'xid' => $clean_xid,
            'comment' => $clean_comment,
        ],
    ], 201);
}

if ($method === 'GET' && preg_match('#^/me$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Not authenticated.'], 401);
    }
    json_response([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'account_type' => $_SESSION['account_type'] ?? '',
        ],
    ]);
}

if ($method === 'GET' && preg_match('#^/crusades/([A-Za-z0-9]+)$#', $path, $matches)) {
    $code = $matches[1];
    $stmt = $conn->prepare("SELECT id, user_id, title, description, crusade_code, video_url, is_active, start_time, end_time, created_at FROM praise_crusades WHERE crusade_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $crusade = $result->fetch_assoc();
    $stmt->close();

    if (!$crusade) {
        json_response(['success' => false, 'message' => 'Crusade not found.'], 404);
    }

    json_response([
        'success' => true,
        'crusade' => $crusade,
    ]);
}

if ($method === 'GET' && preg_match('#^/crusades/([A-Za-z0-9]+)/stats$#', $path, $matches)) {
    $code = $matches[1];
    $stmt = $conn->prepare("SELECT id FROM praise_crusades WHERE crusade_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $crusade = $result->fetch_assoc();
    $stmt->close();

    if (!$crusade) {
        json_response(['success' => false, 'message' => 'Crusade not found.'], 404);
    }

    $crusade_id = (int)$crusade['id'];

    $stmt_views = $conn->prepare("SELECT COUNT(*) AS total_views, COUNT(DISTINCT ip_address) AS unique_views, SUM(CASE WHEN stayed_for_a_minute = 1 THEN 1 ELSE 0 END) AS engaged_views FROM praise_crusade_stats WHERE crusade_id = ?");
    $stmt_views->bind_param("i", $crusade_id);
    $stmt_views->execute();
    $views = $stmt_views->get_result()->fetch_assoc();
    $stmt_views->close();

    $stmt_salvation = $conn->prepare("SELECT COUNT(*) AS total_salvation_clicks FROM praise_salvation_clicks WHERE crusade_id = ?");
    $stmt_salvation->bind_param("i", $crusade_id);
    $stmt_salvation->execute();
    $salvation = $stmt_salvation->get_result()->fetch_assoc();
    $stmt_salvation->close();

    json_response([
        'success' => true,
        'stats' => [
            'total_views' => (int)($views['total_views'] ?? 0),
            'unique_views' => (int)($views['unique_views'] ?? 0),
            'engaged_views' => (int)($views['engaged_views'] ?? 0),
            'salvation_clicks' => (int)($salvation['total_salvation_clicks'] ?? 0),
        ],
    ]);
}

if ($method === 'GET' && $path === '/videos') {
    $language = trim($_GET['language'] ?? '');
    $children_outreach = !empty($_GET['children_outreach']);
    $movie_outreach = !empty($_GET['movie_outreach']);
    $ministrations = !empty($_GET['ministrations']);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = (int)($_GET['per_page'] ?? 20);
    if ($per_page < 1 || $per_page > 100) {
        $per_page = 20;
    }
    $offset = ($page - 1) * $per_page;

    $sql = "SELECT id, title, video_url, age_category, type_category, created_at, language, category_children_outreach, category_movie_outreach, category_ministrations FROM praise_videos WHERE 1=1";
    $params = [];
    $types = '';

    if ($language !== '') {
        $sql .= " AND language = ?";
        $params[] = $language;
        $types .= 's';
    }
    if ($children_outreach) {
        $sql .= " AND category_children_outreach = 1";
    }
    if ($movie_outreach) {
        $sql .= " AND category_movie_outreach = 1";
    }
    if ($ministrations) {
        $sql .= " AND category_ministrations = 1";
    }
    $sql .= " ORDER BY title LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        json_response(['success' => false, 'message' => 'Failed to prepare query.'], 500);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
    $stmt->close();

    $count_sql = "SELECT COUNT(*) AS total FROM praise_videos WHERE 1=1";
    $count_params = [];
    $count_types = '';
    if ($language !== '') {
        $count_sql .= " AND language = ?";
        $count_params[] = $language;
        $count_types .= 's';
    }
    if ($children_outreach) {
        $count_sql .= " AND category_children_outreach = 1";
    }
    if ($movie_outreach) {
        $count_sql .= " AND category_movie_outreach = 1";
    }
    if ($ministrations) {
        $count_sql .= " AND category_ministrations = 1";
    }
    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt && !empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $count_stmt->close();
    } else {
        $total = 0;
    }

    json_response([
        'success' => true,
        'videos' => $videos,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
        ],
    ]);
}

if ($method === 'GET' && $path === '/videos/languages') {
    $result = $conn->query("SELECT DISTINCT language FROM praise_videos WHERE language IS NOT NULL AND language != '' ORDER BY language");
    if (!$result) {
        json_response(['success' => false, 'message' => 'Failed to load languages.'], 500);
    }
    $languages = [];
    while ($row = $result->fetch_assoc()) {
        $languages[] = $row['language'];
    }
    json_response(['success' => true, 'languages' => $languages]);
}

if ($method === 'GET' && $path === '/crusades') {
    if (!empty($_GET['mine'])) {
        require_auth();
        $user_id = $_SESSION['user_id'];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per_page = (int)($_GET['per_page'] ?? 20);
        if ($per_page < 1 || $per_page > 100) {
            $per_page = 20;
        }
        $offset = ($page - 1) * $per_page;

        $stmt = $conn->prepare("SELECT id, title, description, crusade_code, video_url, is_active, start_time, end_time, created_at FROM praise_crusades WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $user_id, $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $crusades = [];
        while ($row = $result->fetch_assoc()) {
            $crusades[] = $row;
        }
        $stmt->close();

        $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM praise_crusades WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $total = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $count_stmt->close();

        json_response([
            'success' => true,
            'crusades' => $crusades,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
            ],
        ]);
    }
    json_response(['success' => false, 'message' => 'Invalid query.'], 400);
}

if ($method === 'POST' && $path === '/crusades') {
    require_auth();
    require_csrf();
    $data = get_request_data();

    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $video_url = trim($data['video_url'] ?? '');
    $live_stream_id = trim($data['live_stream_id'] ?? '');
    $schedule = !empty($data['schedule_crusade']);
    $start_time = $schedule ? trim($data['start_time'] ?? '') : null;
    $end_time = $schedule ? trim($data['end_time'] ?? '') : null;

    if ($title === '') {
        json_response(['success' => false, 'message' => 'Title is required.'], 422);
    }
    if ($description === '') {
        json_response(['success' => false, 'message' => 'Description is required.'], 422);
    }

    if ($live_stream_id !== '' && strpos($live_stream_id, 'official_stream_') === 0) {
        $stream_id = (int)str_replace('official_stream_', '', $live_stream_id);
        if ($stream_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid live stream selection.'], 422);
        }
        $video_url = 'LIVE_STREAM_OFFICIAL_' . $stream_id;
    } elseif ($video_url === '') {
        json_response(['success' => false, 'message' => 'Video URL is required.'], 422);
    } elseif (!filter_var($video_url, FILTER_VALIDATE_URL)) {
        json_response(['success' => false, 'message' => 'Video URL is invalid.'], 422);
    }

    if ($schedule) {
        if ($start_time === '') {
            json_response(['success' => false, 'message' => 'Start time is required for scheduled crusades.'], 422);
        }
        if (!empty($end_time) && strtotime($end_time) <= strtotime($start_time)) {
            json_response(['success' => false, 'message' => 'End time must be after start time.'], 422);
        }
    }

    $user_id = $_SESSION['user_id'];
    require_active_subscription($conn, $user_id);

    $crusade_code = bin2hex(random_bytes(8));
    $is_active = $schedule ? 0 : 1;

    $sql = "INSERT INTO praise_crusades (user_id, title, description, crusade_code, video_url, created_at, is_active, start_time, end_time) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        json_response(['success' => false, 'message' => 'Database error.'], 500);
    }
    $stmt->bind_param("issssiss", $user_id, $title, $description, $crusade_code, $video_url, $is_active, $start_time, $end_time);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to create crusade.'], 500);
    }
    $stmt->close();
    json_response([
        'success' => true,
        'crusade_code' => $crusade_code,
        'is_active' => (bool)$is_active,
    ], 201);
}

if ($method === 'POST' && preg_match('#^/crusades/([A-Za-z0-9]+)/share$#', $path, $matches)) {
    require_auth();
    require_csrf();

    $code = $matches[1];
    $user_id = $_SESSION['user_id'];
    require_active_subscription($conn, $user_id);

    $stmt = $conn->prepare("SELECT id, is_active FROM praise_crusades WHERE crusade_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $crusade = $result->fetch_assoc();
    $stmt->close();

    if (!$crusade) {
        json_response(['success' => false, 'message' => 'Invalid crusade code.'], 404);
    }
    if ((int)$crusade['is_active'] !== 1) {
        json_response(['success' => false, 'message' => 'Crusade is not active.'], 422);
    }

    $stmt_existing = $conn->prepare("SELECT stream_key FROM praise_user_shared_streams WHERE user_id = ? AND crusade_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
    $stmt_existing->bind_param("ii", $user_id, $crusade['id']);
    $stmt_existing->execute();
    $result_existing = $stmt_existing->get_result();
    $existing = $result_existing->fetch_assoc();
    $stmt_existing->close();

    if ($existing && !empty($existing['stream_key'])) {
        $link = base_url() . '/watch.php?stream_key=' . $existing['stream_key'];
        json_response(['success' => true, 'stream_key' => $existing['stream_key'], 'share_url' => $link, 'reused' => true]);
    }

    do {
        $stream_key = bin2hex(random_bytes(16));
        $stmt_check = $conn->prepare("SELECT id FROM praise_user_shared_streams WHERE stream_key = ?");
        $stmt_check->bind_param("s", $stream_key);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
    } while ($result_check->num_rows > 0);
    $stmt_check->close();

    $stmt_insert = $conn->prepare("INSERT INTO praise_user_shared_streams (user_id, crusade_id, stream_key) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("iis", $user_id, $crusade['id'], $stream_key);
    if (!$stmt_insert->execute()) {
        $stmt_insert->close();
        json_response(['success' => false, 'message' => 'Failed to create share link.'], 500);
    }
    $stmt_insert->close();

    $link = base_url() . '/watch.php?stream_key=' . $stream_key;
    json_response(['success' => true, 'stream_key' => $stream_key, 'share_url' => $link, 'reused' => false]);
}

if ($method === 'POST' && $path === '/shared/end') {
    require_auth();
    require_csrf();
    $data = get_request_data();
    $stream_key = trim($data['stream_key'] ?? '');
    if ($stream_key === '') {
        json_response(['success' => false, 'message' => 'Stream key is required.'], 422);
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE praise_user_shared_streams SET is_active = 0 WHERE stream_key = ? AND user_id = ?");
    $stmt->bind_param("si", $stream_key, $user_id);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to end shared stream.'], 500);
    }
    $stmt->close();
    json_response(['success' => true]);
}

if ($method === 'POST' && $path === '/meetings') {
    require_auth();
    require_csrf();
    $data = get_request_data();

    $title = trim($data['title'] ?? 'Untitled Meeting');
    $user_id = $_SESSION['user_id'];
    require_active_subscription($conn, $user_id);

    $meeting_code = substr(bin2hex(random_bytes(8)), 0, 8);
    $max_attempts = 10;
    $attempt = 0;

    $stmt_check = $conn->prepare("SELECT id FROM praise_meetings WHERE meeting_code = ?");
    while ($attempt < $max_attempts) {
        $stmt_check->bind_param("s", $meeting_code);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 0) {
            break;
        }
        $meeting_code = substr(bin2hex(random_bytes(8)), 0, 8);
        $attempt++;
    }
    $stmt_check->close();

    if ($attempt >= $max_attempts) {
        json_response(['success' => false, 'message' => 'Failed to generate a unique meeting code.'], 500);
    }

    $stmt = $conn->prepare("INSERT INTO praise_meetings (user_id, title, meeting_code, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $title, $meeting_code);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to create meeting.'], 500);
    }
    $meeting_id = $stmt->insert_id;
    $stmt->close();

    json_response([
        'success' => true,
        'meeting_id' => $meeting_id,
        'meeting_code' => $meeting_code,
    ], 201);
}

if ($method === 'POST' && $path === '/meetings/start') {
    require_auth();
    require_csrf();
    $data = get_request_data();
    $meeting_id = (int)($data['meeting_id'] ?? 0);
    $duration = (int)($data['duration'] ?? 30);
    $user_id = $_SESSION['user_id'];

    if ($meeting_id <= 0) {
        json_response(['success' => false, 'message' => 'Meeting ID is required.'], 422);
    }

    $stmt = $conn->prepare("SELECT id FROM praise_meetings WHERE id = ?");
    $stmt->bind_param("i", $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Invalid meeting ID.'], 404);
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO praise_meeting_sessions (meeting_id, user_id, start_time, duration) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("iii", $meeting_id, $user_id, $duration);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to start meeting session.'], 500);
    }
    $session_id = $stmt->insert_id;
    $stmt->close();

    json_response([
        'success' => true,
        'session_id' => $session_id,
        'meeting_id' => $meeting_id,
        'duration' => $duration,
    ]);
}

if ($method === 'POST' && $path === '/meetings/end') {
    require_auth();
    require_csrf();
    $data = get_request_data();
    $meeting_code = trim($data['meeting_code'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($meeting_code === '') {
        json_response(['success' => false, 'message' => 'Meeting code is required.'], 422);
    }

    $stmt = $conn->prepare("SELECT id FROM praise_meetings WHERE meeting_code = ?");
    $stmt->bind_param("s", $meeting_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Invalid meeting code.'], 404);
    }
    $meeting = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE praise_meeting_sessions SET end_time = NOW() WHERE meeting_id = ? AND user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1");
    $stmt->bind_param("ii", $meeting['id'], $user_id);
    if (!$stmt->execute()) {
        $stmt->close();
        json_response(['success' => false, 'message' => 'Failed to end meeting session.'], 500);
    }
    $stmt->close();

    json_response(['success' => true, 'message' => 'Meeting session ended successfully.']);
}

if ($method === 'GET' && $path === '/streams/live') {
    $result = $conn->query("SELECT id, title, description, stream_url, is_live, created_at FROM praise_live_tv WHERE is_live = 1 ORDER BY created_at DESC");
    if (!$result) {
        json_response(['success' => false, 'message' => 'Failed to load live streams.'], 500);
    }
    $streams = [];
    while ($row = $result->fetch_assoc()) {
        $streams[] = $row;
    }
    json_response(['success' => true, 'streams' => $streams]);
}

json_response(['success' => false, 'message' => 'Not found.'], 404);
