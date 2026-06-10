<?php
// includes/functions.php

function esc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function save_image_upload($file, $subdir = 'assets/uploads/posters', $maxSize = 5 * 1024 * 1024) {
    if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large (max ' . ($maxSize/1024/1024) . 'MB)'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/tiff' => 'tif'
    ];
    if (!isset($allowed[$mime])) {
        return ['success' => false, 'error' => 'Unsupported file type. Use JPG/PNG/WEBP/TIFF'];
    }
    $ext = $allowed[$mime];
    $subdirNormalized = trim($subdir, '/');
    $uploadDir = __DIR__ . '/../' . $subdirNormalized;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    try {
        $filename = 'img_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    } catch (Exception $e) {
        $filename = 'img_' . time() . '_' . uniqid() . '.' . $ext;
    }
    $dest = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file'];
    }
    @chmod($dest, 0644);
    return ['success' => true, 'filename' => $filename, 'path' => $subdirNormalized . '/' . $filename];
}

/**
 * Return a full URL for a stored media value.
 * Accepts either a bare filename (legacy) or a stored relative path like "assets/uploads/posters/xxx.jpg".
 */
function get_media_url($val) {
    if (empty($val)) return '';
    $v = trim($val);
    if (strpos($v, '/') === false) {
        return (defined('BASE_URL') ? BASE_URL : '') . '/assets/uploads/posters/' . rawurlencode($v);
    }
    return (defined('BASE_URL') ? BASE_URL : '') . '/' . str_replace('%2F','/',rawurlencode($v));
}

/**
 * Resolve stored media (filename or relative path) to an absolute filesystem path.
 */
function get_media_filesystem_path($val) {
    if (empty($val)) return '';
    $v = trim($val);
    if (strpos($v, '/') === false) {
        return __DIR__ . '/../assets/uploads/posters/' . $v;
    }
    return __DIR__ . '/../' . ltrim($v, '/');
}

/**
 * Extract IMDB id (tt1234567) from a URL or id string.
 */
function get_imdb_id(string $val) {
    if (empty($val)) return null;
    if (preg_match('/(tt\d{6,8})/i', $val, $m)) return $m[1];
    if (preg_match('/^tt\d{6,8}$/i', $val)) return $val;
    return null;
}

/**
 * Get IMDb rating for a given imdb url or id. Uses OMDb API if `OMDB_API_KEY` is defined.
 * Caches results in `assets/cache/` for 24 hours.
 * Returns string rating (e.g. "7.5") or null if unavailable.
 */
function get_imdb_rating(string $val, $ttl = 86400) {
    $id = get_imdb_id($val);
    if (!$id) return null;
    $cacheDir = __DIR__ . '/../assets/cache';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cacheFile = $cacheDir . '/imdb_' . $id . '.json';
    if (is_file($cacheFile)) {
        $data = @json_decode(@file_get_contents($cacheFile), true);
        if (is_array($data) && isset($data['rating']) && isset($data['ts']) && (time() - $data['ts'] < $ttl)) {
            return $data['rating'];
        }
    }

    $rating = null;
    // Try OMDb API when key available
    if (defined('OMDB_API_KEY') && OMDB_API_KEY) {
        $url = 'http://www.omdbapi.com/?i=' . rawurlencode($id) . '&apikey=' . rawurlencode(OMDB_API_KEY);
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp) {
            $json = @json_decode($resp, true);
            if (is_array($json) && !empty($json['imdbRating']) && $json['imdbRating'] !== 'N/A') {
                $rating = $json['imdbRating'];
            }
        }
    }

    // Fallback: try scraping IMDb page for rating (best-effort)
    if ($rating === null) {
        $imdbUrl = 'https://www.imdb.com/title/' . $id . '/';
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: Mozilla/5.0 (compatible; EthioEventsBot/1.0)"]]);
        $html = @file_get_contents($imdbUrl, false, $ctx);
        if ($html) {
            // Try JSON-LD block first
            if (preg_match('/"ratingValue"\s*:\s*"([0-9\.]+)"/i', $html, $m)) {
                $rating = $m[1];
            } elseif (preg_match('/itemprop="ratingValue">\s*([0-9\.]+)\s*<\/span>/i', $html, $m2)) {
                $rating = $m2[1];
            }
        }
    }

    // Cache result (including null to avoid frequent scraping)
    $cacheData = ['ts' => time(), 'rating' => $rating];
    @file_put_contents($cacheFile, json_encode($cacheData));
    return $rating;
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_role($role) {
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}

function flash_set($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}

function flash_get($key) {
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

function get_user_by_email($email) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function get_user_by_id($id) {
    $pdo = get_db();
    // Return full user row so consuming code can access extended profile fields when present
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function register_user($name, $email, $password, $role = 'user', $phone = null, $city = null, $organization_name = null, $organization_license = null, $cinema_name = null, $cinema_license = null) {
    $allowed_roles = ['admin', 'organizer', 'cinema_manager', 'user'];
    if (!in_array($role, $allowed_roles)) {
        return ['success' => false, 'message' => 'Invalid role selected.'];
    }

    $pdo = get_db();
    if (get_user_by_email($email)) {
        return ['success' => false, 'message' => 'Email already registered'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        // Try saving with extended columns (migration expected). If DB doesn't have these columns,
        // fall back to the original simpler insert.
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, phone, city, organization_name, organization_license, cinema_name, cinema_license, license_approved, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $role, $phone, $city, $organization_name, $organization_license, $cinema_name, $cinema_license, 0, 0]);
        return ['success' => true, 'user_id' => $pdo->lastInsertId()];
    } catch (Exception $e) {
        // If the DB schema does not include the new columns, fall back to the original insert.
        $msg = $e->getMessage();
            if (stripos($msg, 'unknown column') !== false || stripos($msg, "Column 'organization_name'") !== false) {
            try {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, phone, city) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hash, $role, $phone, $city]);
                return ['success' => true, 'user_id' => $pdo->lastInsertId(), 'warning' => 'User created but extended profile fields were not saved (DB migration missing).'];
            } catch (Exception $e2) {
                return ['success' => false, 'message' => 'Failed to create user: ' . $e2->getMessage()];
            }
        }
        return ['success' => false, 'message' => 'Failed to create user: ' . $msg];
    }
}

function is_cinema_manager() {
    return is_logged_in() && (($_SESSION['role'] ?? '') === 'cinema_manager');
}

function require_cinema_manager() {
    if (!is_cinema_manager()) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}

function authenticate_user($email, $password) {
    $user = get_user_by_email($email);
    if (!$user) {
        _log_login_attempt($email, false, false);
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    if (!password_verify($password, $user['password'])) {
        _log_login_attempt($email, true, false);
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    // If the DB supports an approval flag, disallow login until admin approves
    if (isset($user['is_approved']) && !$user['is_approved']) {
        return ['success' => false, 'message' => 'Your account is pending admin approval'];
    }
    // set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    _log_login_attempt($email, true, true);
    return ['success' => true, 'user' => $user];
}

// Debug helper: log login attempts (non-sensitive). Records email, whether user exists and whether password matched.
function _log_login_attempt($email, $user_exists, $password_ok) {
    try {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/login_attempts.log';
        $entry = [
            'ts' => date('c'),
            'email' => $email,
            'user_exists' => $user_exists ? 1 : 0,
            'password_ok' => $password_ok ? 1 : 0,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)
        ];
        @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // ignore logging failures
    }
}

function logout_user() {
    unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['name'], $_SESSION['email'], $_SESSION['theme']);
    session_regenerate_id(true);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function is_organizer() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'organizer';
}

function require_organizer() {
    if (!is_organizer()) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}

function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function require_admin() {
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/');
        exit;
    }
}

// Simulate sending email by saving HTML to disk and logging to DB if available
function send_email_simulate($to, $subject, $htmlBody) {
    $dir = __DIR__ . '/../assets/emails';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = $dir . '/email_' . time() . '_' . bin2hex(random_bytes(4)) . '.html';
    $content = "<html><head><meta charset=\"utf-8\"><title>" . htmlspecialchars($subject) . "</title></head><body>" . $htmlBody . "</body></html>";
    file_put_contents($filename, $content);
    // try to insert into email_logs if table exists
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare('INSERT INTO email_logs (recipient, subject, body) VALUES (?, ?, ?)');
        $stmt->execute([$to, $subject, $htmlBody]);
    } catch (Exception $e) {
        // ignore if table doesn't exist
    }
    return $filename;
}

function set_user_theme($user_id, $theme) {
    $allowed = ['','light','dark','cultural'];
    if (!in_array($theme, $allowed)) $theme = '';
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare('UPDATE users SET theme_preference = ? WHERE id = ?');
        $stmt->execute([$theme, $user_id]);
        $_SESSION['theme'] = $theme;
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function get_user_theme($user_id) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT theme_preference FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetchColumn();
    return $row ?: '';
}

/**
 * Auto-delete past events once per day.
 * This removes events whose `event_date` is earlier than today and
 * attempts to cleanup uploaded poster files. To avoid running on every
 * request we write a small flag file under `tmp/` recording the last run date.
 */
function auto_delete_past_events_daily() {
    $flagDir = __DIR__ . '/../tmp';
    if (!is_dir($flagDir)) @mkdir($flagDir, 0755, true);
    $flagFile = $flagDir . '/events_cleanup_last_run.txt';
    $today = date('Y-m-d');
    $last = false;
    if (is_file($flagFile)) {
        $last = trim(@file_get_contents($flagFile));
    }
    if ($last === $today) return;

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id, poster FROM events WHERE event_date < CURDATE()");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if (!empty($rows)) {
            $del = $pdo->prepare('DELETE FROM events WHERE id = ?');
            foreach ($rows as $r) {
                $poster = $r['poster'] ?? '';
                if ($poster) {
                    $fn = basename($poster);
                    $candidates = [
                        __DIR__ . '/../assets/uploads/' . $fn,
                        __DIR__ . '/../assets/uploads/posters/' . $fn,
                        __DIR__ . '/../assets/uploads/licenses/' . $fn,
                    ];
                    foreach ($candidates as $p) { if (file_exists($p)) @unlink($p); }
                }
                $del->execute([$r['id']]);
            }
        }
    } catch (Exception $e) {
        @file_put_contents(__DIR__ . '/../logs/cleanup_errors.log', date('c') . ' ' . $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    @file_put_contents($flagFile, $today, LOCK_EX);
}
