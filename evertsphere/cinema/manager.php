<?php
// Cinema manager dashboard (single-file controller)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_cinema_manager();

$pdo = get_db();
$user = get_user_by_id(current_user_id());

// Detect if cinemas table has manager_id column
$has_manager_col = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM cinemas LIKE 'manager_id'")->fetch();
    $has_manager_col = !empty($col);
} catch (Exception $e) {
    $has_manager_col = false;
}

// Detect if cinemas table has latitude/longitude columns
$has_geo_cols = false;
try {
  $c1 = $pdo->query("SHOW COLUMNS FROM cinemas LIKE 'latitude'")->fetch();
  $c2 = $pdo->query("SHOW COLUMNS FROM cinemas LIKE 'longitude'")->fetch();
  $has_geo_cols = !empty($c1) && !empty($c2);
} catch (Exception $e) {
  $has_geo_cols = false;
}

// Detect (and create if missing) a lightweight mapping table for cinema managers
$has_cinema_managers_table = false;
try {
    $tbl = $pdo->query("SHOW TABLES LIKE 'cinema_managers'")->fetch();
    $has_cinema_managers_table = !empty($tbl);
    if (!$has_cinema_managers_table) {
        // Try to create mapping table so managers can own multiple cinemas even when schema
        // lacks a `manager_id` column on `cinemas`.
        $pdo->exec("CREATE TABLE IF NOT EXISTS cinema_managers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            cinema_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY ux_user_cinema (user_id, cinema_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $has_cinema_managers_table = true;
    }
} catch (Exception $e) {
    // If creation or detection fails, just operate without the mapping table.
    $has_cinema_managers_table = false;
}

// Load managed cinemas (supports both `cinemas.manager_id` and the fallback mapping table)
$managed_cinemas = [];
if ($has_manager_col) {
  $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE manager_id = ? ORDER BY name');
  $stmt->execute([current_user_id()]);
  $managed_cinemas = $stmt->fetchAll();
} elseif ($has_cinema_managers_table) {
  try {
    $stmt = $pdo->prepare('SELECT c.* FROM cinemas c JOIN cinema_managers cm ON c.id = cm.cinema_id WHERE cm.user_id = ? ORDER BY c.name');
    $stmt->execute([current_user_id()]);
    $managed_cinemas = $stmt->fetchAll();
  } catch (Exception $e) {
    $managed_cinemas = [];
  }
} else {
  $cname = trim($user['cinema_name'] ?? '');
  if ($cname) {
    $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE name = ? LIMIT 1');
    $stmt->execute([$cname]);
    $row = $stmt->fetch();
    if ($row) $managed_cinemas = [$row];
  }
}

$view = $_GET['view'] ?? 'dashboard';
$flash = flash_get('manager_flash');

// If an edit request is present via GET, load the movie for editing
$edit_movie_id = (int)($_GET['edit'] ?? 0);
$edit_movie = null;
if ($edit_movie_id) {
  $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = ? LIMIT 1');
  $stmt->execute([$edit_movie_id]);
  $edit_movie = $stmt->fetch();
  if (!$edit_movie) {
    flash_set('manager_flash', 'Movie not found');
    header('Location: ' . $_SERVER['REQUEST_URI']); exit;
  }
  // permission check: ensure current manager owns the cinema for this movie
  $allowed = false;
  if ($has_manager_col) {
    $perm = $pdo->prepare('SELECT id FROM cinemas WHERE id = ? AND manager_id = ? LIMIT 1');
    $perm->execute([$edit_movie['cinema_id'], current_user_id()]);
    if ($perm->fetch()) $allowed = true;
  } else {
    $user = get_user_by_id(current_user_id());
    $perm = $pdo->prepare('SELECT id, name FROM cinemas WHERE id = ? LIMIT 1');
    $perm->execute([$edit_movie['cinema_id']]);
    $cin = $perm->fetch();
    if ($user && $cin && trim($user['cinema_name'] ?? '') === trim($cin['name'])) $allowed = true;
  }
  if (!$allowed) { flash_set('manager_flash', 'Unauthorized'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
}

// Handle simple POST actions: create location, create movie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_location') {
        $name = trim($_POST['name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
      $latitude = null;
      $longitude = null;
      $created_cinema_id = null;
      // Accept coords as a single comma-separated string like "9.019751047518447, 38.813807795572075"
      if (!empty($_POST['coords'])) {
        $coords = trim($_POST['coords']);
        if (strpos($coords, ',') !== false) {
          list($latPart, $lngPart) = array_map('trim', explode(',', $coords, 2));
          if ($latPart !== '' && $lngPart !== '' && is_numeric($latPart) && is_numeric($lngPart)) {
            $latitude = $latPart;
            $longitude = $lngPart;
          }
        }
      }
      // Fallback to individual fields if provided
      if ($latitude === null && isset($_POST['latitude']) && $_POST['latitude'] !== '') $latitude = trim($_POST['latitude']);
      if ($longitude === null && isset($_POST['longitude']) && $_POST['longitude'] !== '') $longitude = trim($_POST['longitude']);
      if ($latitude !== null && !is_numeric($latitude)) $latitude = null;
      if ($longitude !== null && !is_numeric($longitude)) $longitude = null;
        if ($name !== '') {
            try {
          // Try to include geo columns if present
          if ($has_manager_col && $has_geo_cols) {
            $stmt = $pdo->prepare('INSERT INTO cinemas (name, city, address, latitude, longitude, contact, manager_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $city, $address, $latitude, $longitude, $contact, current_user_id()]);
            $created_cinema_id = (int)$pdo->lastInsertId();
            // If we have mapping table, ensure an explicit mapping exists too
            if ($has_cinema_managers_table) {
              try { $pdo->prepare('INSERT IGNORE INTO cinema_managers (user_id, cinema_id) VALUES (?, ?)')->execute([current_user_id(), $created_cinema_id]); } catch (Exception $ecm) {}
            }
          } elseif ($has_geo_cols) {
            $stmt = $pdo->prepare('INSERT INTO cinemas (name, city, address, latitude, longitude, contact) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $city, $address, $latitude, $longitude, $contact]);
            $created_cinema_id = (int)$pdo->lastInsertId();
            // Link created cinema to current user for non-manager_id setups so it appears in "My Locations"
            try {
              $pdo->prepare('UPDATE users SET cinema_name = ? WHERE id = ?')->execute([$name, current_user_id()]);
            } catch (Exception $eup) {
              // ignore failures to update user record
            }
          } elseif ($has_manager_col) {
            $stmt = $pdo->prepare('INSERT INTO cinemas (name, city, address, contact, manager_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $city, $address, $contact, current_user_id()]);
            $created_cinema_id = (int)$pdo->lastInsertId();
            if ($has_cinema_managers_table) {
              try { $pdo->prepare('INSERT IGNORE INTO cinema_managers (user_id, cinema_id) VALUES (?, ?)')->execute([current_user_id(), $created_cinema_id]); } catch (Exception $ecm) {}
            }
          } else {
            $stmt = $pdo->prepare('INSERT INTO cinemas (name, city, address, contact) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $city, $address, $contact]);
            $created_cinema_id = (int)$pdo->lastInsertId();
            // link via users.cinema_name as fallback
            try {
              $pdo->prepare('UPDATE users SET cinema_name = ? WHERE id = ?')->execute([$name, current_user_id()]);
            } catch (Exception $eup) {
              // ignore failures to update user record
            }
            if ($has_cinema_managers_table) {
              try { $pdo->prepare('INSERT IGNORE INTO cinema_managers (user_id, cinema_id) VALUES (?, ?)')->execute([current_user_id(), $created_cinema_id]); } catch (Exception $ecm) {}
            }
          }
                $flash = 'Location created';
                flash_set('manager_flash', $flash);
            } catch (Exception $e) {
                $flash = 'Failed to create location: ' . $e->getMessage();
                flash_set('manager_flash', $flash);
            }
        } else {
              $flash = 'Location name is required';
              flash_set('manager_flash', $flash);
        }
        // If user requested to open the coords in Google Maps and coords are present, redirect there
        if (!empty($_POST['open_map']) && $latitude !== null && $longitude !== null && strpos(($flash ?? ''), 'Location created') !== false) {
          $lat_enc = rawurlencode($latitude);
          $lng_enc = rawurlencode($longitude);
          $maps_url = 'https://www.google.com/maps/search/?api=1&query=' . $lat_enc . ',' . $lng_enc;
          header('Location: ' . $maps_url);
          exit;
        }

        // If we created a cinema and the manager_id column is missing, ensure it's visible to the current manager
        if ($created_cinema_id && !$has_manager_col) {
          // Store the created id in session so when we re-fetch managed cinemas it picks it up
          $_SESSION['last_created_cinema_id'] = $created_cinema_id;
        }

        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

      if ($action === 'delete_movie') {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        if ($movie_id <= 0) { flash_set('manager_flash', 'Invalid movie'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
        // verify ownership
        $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = ? LIMIT 1');
        $stmt->execute([$movie_id]);
        $mv = $stmt->fetch();
        if (!$mv) { flash_set('manager_flash', 'Movie not found'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
        // check cinema ownership if manager column exists
        if ($has_manager_col) {
          $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE id = ? AND manager_id = ? LIMIT 1');
          $stmt->execute([$mv['cinema_id'], current_user_id()]);
          if (!$stmt->fetch()) { flash_set('manager_flash', 'Unauthorized'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
        } else {
          // fallback: ensure current user's cinema_name matches cinema
          $user = get_user_by_id(current_user_id());
          $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE id = ? LIMIT 1'); $stmt->execute([$mv['cinema_id']]); $cin = $stmt->fetch();
          if ($user && $cin && trim($user['cinema_name'] ?? '') !== trim($cin['name'])) { flash_set('manager_flash', 'Unauthorized'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
        }
        // delete poster file if exists
        if (!empty($mv['poster'])) {
          $f = get_media_filesystem_path($mv['poster']);
          if ($f && file_exists($f)) @unlink($f);
        }
        // delete movie (seats have ON DELETE CASCADE)
        try {
          $stmt = $pdo->prepare('DELETE FROM movies WHERE id = ?');
          $stmt->execute([$movie_id]);
          flash_set('manager_flash', 'Movie deleted');
        } catch (Exception $e) {
          flash_set('manager_flash', 'Failed to delete movie: ' . $e->getMessage());
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
      }

      if ($action === 'edit_movie') {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $cinema_id = (int)($_POST['cinema_id'] ?? 0);
        $show_date = $_POST['show_date'] ?? null;
        $show_time = $_POST['show_time'] ?? null;
        $genre = trim($_POST['genre'] ?? '');
        $imdb_url = trim($_POST['imdb_url'] ?? '');
        $rating = (isset($_POST['rating']) && $_POST['rating'] !== '') ? (float)$_POST['rating'] : null;
        $poster_file = $_FILES['poster_file'] ?? null;
        if (!$movie_id || !$title || !$cinema_id) { flash_set('manager_flash','Missing fields'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
        // permission check similar to delete
        $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = ? LIMIT 1'); $stmt->execute([$movie_id]); $mv = $stmt->fetch();
        if (!$mv) { flash_set('manager_flash','Movie not found'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
        if ($has_manager_col) {
          $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE id = ? AND manager_id = ? LIMIT 1'); $stmt->execute([$cinema_id, current_user_id()]);
          if (!$stmt->fetch()) { flash_set('manager_flash','Unauthorized'); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
        }
        // handle new poster if uploaded (store relative path in DB)
        $newPoster = null;
        if (($poster_file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
          $up = save_image_upload($poster_file, 'assets/uploads/posters', 5 * 1024 * 1024);
          if (!$up['success']) { flash_set('manager_flash', 'Poster upload failed: ' . $up['error']); header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
          $newPoster = $up['path'];
        }
        try {
          if ($newPoster) {
            // delete old
            if (!empty($mv['poster'])) { $old = get_media_filesystem_path($mv['poster']); if ($old && file_exists($old)) @unlink($old); }
            try {
              $stmt = $pdo->prepare('UPDATE movies SET title=?, cinema_id=?, show_date=?, show_time=?, genre=?, poster=?, imdb_url=?, rating=? WHERE id=?');
              $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $newPoster, $imdb_url, $rating, $movie_id]);
            } catch (Exception $e2) {
              // fallback if some columns missing
              $msg2 = $e2->getMessage();
              if (stripos($msg2, 'unknown column') !== false || stripos($msg2, 'imdb_url') !== false || stripos($msg2, 'rating') !== false) {
                try {
                  $stmt = $pdo->prepare('UPDATE movies SET title=?, cinema_id=?, show_date=?, show_time=?, genre=?, poster=?, imdb_url=? WHERE id=?');
                  $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $newPoster, $imdb_url, $movie_id]);
                } catch (Exception $e3) {
                  $stmt = $pdo->prepare('UPDATE movies SET title=?, cinema_id=?, show_date=?, show_time=?, genre=?, poster=? WHERE id=?');
                  $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $newPoster, $movie_id]);
                }
              } else {
                throw $e2;
              }
            }
          } else {
            try {
              $stmt = $pdo->prepare('UPDATE movies SET title=?, cinema_id=?, show_date=?, show_time=?, genre=?, imdb_url=?, rating=? WHERE id=?');
              $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $imdb_url, $rating, $movie_id]);
            } catch (Exception $e2) {
              $msg2 = $e2->getMessage();
              if (stripos($msg2, 'unknown column') !== false || stripos($msg2, 'imdb_url') !== false || stripos($msg2, 'rating') !== false) {
                try {
                  $stmt = $pdo->prepare('UPDATE movies SET title=?, cinema_id=?, show_date=?, show_time=?, genre=?, imdb_url=? WHERE id=?');
                  $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $imdb_url, $movie_id]);
                } catch (Exception $e3) {
                  $stmt = $pdo->prepare('UPDATE movies SET title=?, cinema_id=?, show_date=?, show_time=?, genre=? WHERE id=?');
                  $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $movie_id]);
                }
              } else {
                throw $e2;
              }
            }
          }
          flash_set('manager_flash','Movie updated');
        } catch (Exception $e) {
          flash_set('manager_flash','Failed to update: ' . $e->getMessage());
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
      }

    if ($action === 'create_movie') {
      $title = trim($_POST['title'] ?? '');
      $cinema_id = (int)($_POST['cinema_id'] ?? 0);
      $show_date = $_POST['show_date'] ?? null;
      $show_time = $_POST['show_time'] ?? null;
      $genre = trim($_POST['genre'] ?? '');
      $imdb_url = trim($_POST['imdb_url'] ?? '');
      $rating = (isset($_POST['rating']) && $_POST['rating'] !== '') ? (float)$_POST['rating'] : null;
      $poster_file = $_FILES['poster_file'] ?? null;
      if ($title && $cinema_id) {
        // poster required
        if (($poster_file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
          $flash = 'Poster image is required for movies';
          flash_set('manager_flash', $flash);
          header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        
        
        // save poster (store relative path in DB)
        $up = save_image_upload($poster_file, 'assets/uploads/posters', 5 * 1024 * 1024);
        if (!$up['success']) {
          $flash = 'Poster upload failed: ' . $up['error'];
          flash_set('manager_flash', $flash);
          header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        $poster_db_value = $up['path'];
        try {
          // Try to store imdb_url and rating if DB has the columns
          $stmt = $pdo->prepare('INSERT INTO movies (title, cinema_id, show_date, show_time, genre, poster, imdb_url, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
          $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $poster_db_value, $imdb_url, $rating]);
          $flash = 'Movie/showing added';
          flash_set('manager_flash', $flash);
        } catch (Exception $e) {
          $msg = $e->getMessage();
          if (stripos($msg, 'unknown column') !== false || stripos($msg, 'imdb_url') !== false || stripos($msg, 'rating') !== false) {
            // Try fallbacks: with imdb_url only, with rating only, then without either
            try {
              $stmt = $pdo->prepare('INSERT INTO movies (title, cinema_id, show_date, show_time, genre, poster, imdb_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
              $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $poster_db_value, $imdb_url]);
              $flash = 'Movie/showing added';
              flash_set('manager_flash', $flash);
            } catch (Exception $e2) {
              try {
                $stmt = $pdo->prepare('INSERT INTO movies (title, cinema_id, show_date, show_time, genre, poster, rating) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $poster_db_value, $rating]);
                $flash = 'Movie/showing added';
                flash_set('manager_flash', $flash);
              } catch (Exception $e3) {
                try {
                  $stmt = $pdo->prepare('INSERT INTO movies (title, cinema_id, show_date, show_time, genre, poster) VALUES (?, ?, ?, ?, ?, ?)');
                  $stmt->execute([$title, $cinema_id, $show_date, $show_time, $genre, $poster_db_value]);
                  $flash = 'Movie/showing added (rating/imdb not stored - DB missing columns)';
                  flash_set('manager_flash', $flash);
                } catch (Exception $e4) {
                  $flash = 'Failed to add movie: ' . $e4->getMessage();
                  flash_set('manager_flash', $flash);
                }
              }
            }
          } else {
            $flash = 'Failed to add movie: ' . $msg;
            flash_set('manager_flash', $flash);
          }
        }
      } else {
        $flash = 'Title and cinema required';
        flash_set('manager_flash', $flash);
      }
      header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }
}

// Re-fetch managed cinemas after potential changes
if ($has_manager_col) {
    $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE manager_id = ? ORDER BY name');
    $stmt->execute([current_user_id()]);
    $managed_cinemas = $stmt->fetchAll();
} else {
  $cname = trim($user['cinema_name'] ?? '');
  if (!empty($_SESSION['last_created_cinema_id'])) {
    $lid = (int)$_SESSION['last_created_cinema_id'];
    unset($_SESSION['last_created_cinema_id']);
    $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE id = ? LIMIT 1');
    $stmt->execute([$lid]);
    $row = $stmt->fetch();
    if ($row) {
      $managed_cinemas = [$row];
    } elseif ($cname) {
      $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE name = ? LIMIT 1');
      $stmt->execute([$cname]);
      $row = $stmt->fetch();
      if ($row) $managed_cinemas = [$row]; else $managed_cinemas = [];
    } else {
      $managed_cinemas = [];
    }
  } else {
    if ($cname) {
      $stmt = $pdo->prepare('SELECT * FROM cinemas WHERE name = ? LIMIT 1');
      $stmt->execute([$cname]);
      $row = $stmt->fetch();
      if ($row) $managed_cinemas = [$row]; else $managed_cinemas = [];
    } else {
      $managed_cinemas = [];
    }
  }
}

// Helper: fetch counts
$cinema_ids = array_column($managed_cinemas, 'id');
$counts = ['locations' => count($managed_cinemas), 'movies' => 0, 'upcoming' => 0, 'seats' => 0];
if (!empty($cinema_ids)) {
    $in = implode(',', array_fill(0, count($cinema_ids), '?'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE cinema_id IN ($in)");
    $stmt->execute($cinema_ids);
    $counts['movies'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE cinema_id IN ($in) AND show_date >= CURDATE()");
    $stmt->execute($cinema_ids);
    $counts['upcoming'] = (int)$stmt->fetchColumn();

    // seats count: join movies -> seats
    $stmt = $pdo->prepare("SELECT COUNT(s.id) FROM seats s JOIN movies m ON s.movie_id = m.id WHERE m.cinema_id IN ($in)");
    $stmt->execute($cinema_ids);
    $counts['seats'] = (int)$stmt->fetchColumn();
}

    // Ensure $movies exists to avoid undefined variable warnings in views
    $movies = [];

    

// Render page
$page_title = 'Manager Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="site-container">
  <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6">
    <aside class="glass-panel p-4 rounded-2xl">
      <h3 class="font-display font-bold mb-4">Cinema Manager</h3>
      <nav class="space-y-2">
        <a href="?view=dashboard" class="block px-3 py-2 rounded hover:bg-coffee-700/10 <?php echo ($view==='dashboard'?'bg-coffee-700/10':''); ?>">Dashboard</a>
        <a href="?view=locations" class="block px-3 py-2 rounded hover:bg-coffee-700/10 <?php echo ($view==='locations'?'bg-coffee-700/10':''); ?>">My Locations</a>
        <a href="?view=movies" class="block px-3 py-2 rounded hover:bg-coffee-700/10 <?php echo ($view==='movies'?'bg-coffee-700/10':''); ?>">Movies</a>
      </nav>
    </aside>

    <main>
      <?php if (!empty($flash)): ?>
        <div class="mb-4 p-3 rounded bg-amber-700/10 text-amber-200"><?php echo esc($flash); ?></div>
      <?php endif; ?>
      <?php if ($view === 'dashboard'): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="glass-panel p-6 rounded-2xl">
            <h4 class="font-semibold">Locations</h4>
            <div class="text-3xl font-bold mt-2"><?php echo $counts['locations']; ?></div>
          </div>
          <div class="glass-panel p-6 rounded-2xl">
            <h4 class="font-semibold">Movies</h4>
            <div class="text-3xl font-bold mt-2"><?php echo $counts['movies']; ?></div>
          </div>
          <div class="glass-panel p-6 rounded-2xl">
            <h4 class="font-semibold">Upcoming Showings</h4>
            <div class="text-3xl font-bold mt-2"><?php echo $counts['upcoming']; ?></div>
          </div>
        </div>

        <div class="mt-6 glass-panel p-6 rounded-2xl">
          <h3 class="font-display font-bold">Recent Movies</h3>
          <?php if ($counts['movies'] > 0):
             $in = implode(',', array_fill(0, count($cinema_ids), '?'));
             $stmt = $pdo->prepare("SELECT m.*, c.name AS cinema_name FROM movies m JOIN cinemas c ON m.cinema_id = c.id WHERE m.cinema_id IN ($in) ORDER BY m.show_date DESC LIMIT 8");
             $stmt->execute($cinema_ids);
             $recent = $stmt->fetchAll();
          ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <?php foreach ($recent as $r): ?>
                <div class="p-3 rounded-md bg-white/5 flex gap-3">
                  <?php if (!empty($r['poster'])): ?>
                    <img src="<?php echo esc(get_media_url($r['poster'])); ?>" alt="<?php echo esc($r['title']); ?>" style="width:72px;height:72px;object-fit:cover;border-radius:8px;">
                  <?php endif; ?>
                  <div>
                    <div class="font-semibold"><?php echo esc($r['title']); ?></div>
                    <div class="text-sm text-coffee-300"><?php echo esc($r['cinema_name']); ?> • <?php echo date('M d, Y', strtotime($r['show_date'])); ?> <?php echo esc($r['show_time']); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="mt-3 text-coffee-300">No movies found. Use the Movies tab to add showings.</p>
          <?php endif; ?>
        </div>

      <?php elseif ($view === 'locations'): ?>
        <div class="glass-panel p-6 rounded-2xl">
          <div class="flex items-center justify-between">
            <h3 class="font-display font-bold">My Locations</h3>
            <button id="showAddLocation" class="btn btn-primary">Add Location</button>
          </div>
          <div class="mt-4">
            <?php if ($managed_cinemas): ?>
              <table class="table table-sm">
                <thead><tr><th>Name</th><th>City</th><th>Contact</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($managed_cinemas as $c): ?>
                  <tr>
                    <td><?php echo esc($c['name']); ?></td>
                    <td><?php echo esc($c['city']); ?></td>
                    <td><?php echo esc($c['contact']); ?></td>
                    <td><a href="manager.php?view=locations&edit=<?php echo $c['id']; ?>" class="btn btn-sm">Edit</a></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p class="text-coffee-300">You have not added any locations yet.</p>
            <?php endif; ?>
          </div>

          <form id="addLocationForm" method="post" class="mt-6 hidden" style="max-width:560px;">
            <input type="hidden" name="action" value="create_location">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <input name="name" placeholder="Location name" class="form-control" required>
              <input name="city" placeholder="City" class="form-control">
              <input name="address" placeholder="Address" class="form-control md:col-span-2">
              <input name="contact" placeholder="Contact" class="form-control md:col-span-2">
              <input name="coords" id="coords" placeholder="Latitude, Longitude (e.g. 9.019751047518447, 38.813807795572075)" class="form-control md:col-span-2 hidden" value="">
              <div class="md:col-span-2 text-sm text-gray-300 mt-1">Enter coordinates as <strong>lat, lng</strong>. Example: 9.019751047518447, 38.813807795572075</div>
              <div class="md:col-span-2 mt-2">
                <label class="inline-flex items-center text-sm text-gray-300"><input type="checkbox" name="open_map" id="open_map" value="1" class="mr-2">Open in Google Maps after create</label>
              </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary">Create Location</button></div>
          </form>
        </div>

      <?php elseif ($view === 'movies'): ?>
        <div class="glass-panel p-6 rounded-2xl">
          <div class="flex items-center justify-between">
            <h3 class="font-display font-bold">Movies & Showings</h3>
            <button id="showAddMovie" class="btn btn-primary">Add Movie/Showing</button>
          </div>
          <div class="mt-4">
            <?php if ($counts['movies'] > 0):
                $in = implode(',', array_fill(0, count($cinema_ids), '?'));
                $stmt = $pdo->prepare("SELECT m.*, c.name AS cinema_name FROM movies m JOIN cinemas c ON m.cinema_id = c.id WHERE m.cinema_id IN ($in) ORDER BY m.show_date DESC");
                $stmt->execute($cinema_ids);
                $movies = $stmt->fetchAll();
            ?>
              <table class="table table-sm">
                <thead><tr><th>Title</th><th>Cinema</th><th>Date</th><th>Time</th><th>Genre</th><th>IMDb</th></tr></thead>
                <tbody>
                  <?php foreach ($movies as $m): ?>
                    <tr>
                      <td>
                        <?php if (!empty($m['poster'])): ?>
                          <img src="<?php echo esc(get_media_url($m['poster'])); ?>" alt="<?php echo esc($m['title']); ?>" style="width:56px;height:56px;object-fit:cover;border-radius:6px;margin-right:8px;vertical-align:middle;">
                        <?php endif; ?>
                        <?php echo esc($m['title']); ?>
                      </td>
                      <td><?php echo esc($m['cinema_name']); ?></td>
                      <td><?php echo esc($m['show_date']); ?></td>
                      <td><?php echo esc($m['show_time']); ?></td>
                      <td><?php echo esc($m['genre']); ?></td>
                      <td>
                        <?php if (!empty($m['imdb_url'])):
                          $r = $m['rating'] ?? '';
                          if (empty($r)) $r = get_imdb_rating($m['imdb_url']);
                        ?>
                          <a href="<?php echo esc($m['imdb_url']); ?>" target="_blank" class="text-amber-400"><?php echo esc($r ?: 'IMDb'); ?></a>
                        <?php else: ?>
                          —
                        <?php endif; ?>
                      </td>
                      <td>
                        <a href="?view=movies&edit=<?php echo $m['id']; ?>" class="btn btn-sm">Edit</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this movie?');">
                          <input type="hidden" name="action" value="delete_movie">
                          <input type="hidden" name="movie_id" value="<?php echo $m['id']; ?>">
                          <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p class="text-coffee-300">No movies/showings yet.</p>
            <?php endif; ?>
          </div>

          <?php if (!empty($edit_movie)): ?>
          <form id="editMovieForm" method="post" enctype="multipart/form-data" class="mt-6" style="max-width:720px;">
            <input type="hidden" name="action" value="edit_movie">
            <input type="hidden" name="movie_id" value="<?php echo esc($edit_movie['id']); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <input name="title" placeholder="Title" class="form-control" required value="<?php echo esc($edit_movie['title']); ?>">
              <select name="cinema_id" class="form-control" required>
                <?php if (!empty($managed_cinemas)): ?>
                  <?php if (count($managed_cinemas) > 1): ?>
                    <option value="">Select a cinema</option>
                  <?php endif; ?>
                  <?php foreach ($managed_cinemas as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($c['id']==($edit_movie['cinema_id']??0) ? 'selected' : ''); ?>><?php echo esc($c['name']); ?></option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="">No locations available — add one first</option>
                <?php endif; ?>
              </select>
              <input name="show_date" type="date" class="form-control" value="<?php echo esc($edit_movie['show_date'] ?? ''); ?>">
              <input name="show_time" type="time" class="form-control" value="<?php echo esc($edit_movie['show_time'] ?? ''); ?>">
              <input name="genre" placeholder="Genre" class="form-control md:col-span-2" value="<?php echo esc($edit_movie['genre'] ?? ''); ?>">
              <input name="imdb_url" placeholder="IMDb URL (optional)" class="form-control md:col-span-2" value="<?php echo esc($edit_movie['imdb_url'] ?? ''); ?>">
              <input name="rating" placeholder="Rating (e.g. 7.5)" class="form-control" value="<?php echo esc($edit_movie['rating'] ?? ''); ?>">
              <div class="md:col-span-2">
                <label class="text-sm text-gray-400">Poster image (optional — leave empty to keep current)</label>
                <input name="poster_file" type="file" accept="image/*" class="form-control mt-1">
                <p class="text-xs text-gray-400 mt-1">Upload a new poster to replace existing. Max 5MB.</p>
              </div>
            </div>
            <div class="mt-3">
              <button class="btn btn-primary">Save Changes</button>
              <a href="?view=movies" class="btn btn-secondary">Cancel</a>
            </div>
          </form>
          <?php else: ?>
          <form id="addMovieForm" method="post" enctype="multipart/form-data" class="mt-6 hidden" style="max-width:720px;">
            <input type="hidden" name="action" value="create_movie">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <input name="title" placeholder="Title" class="form-control" required>
              <select name="cinema_id" class="form-control" required>
                <?php if (!empty($managed_cinemas)): ?>
                  <?php if (count($managed_cinemas) > 1): ?>
                    <option value="">Select a cinema</option>
                  <?php endif; ?>
                  <?php foreach ($managed_cinemas as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo (count($managed_cinemas) === 1 ? 'selected' : ''); ?>><?php echo esc($c['name']); ?></option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="">No locations available — add one first</option>
                <?php endif; ?>
              </select>
              <input name="show_date" type="date" class="form-control">
              <input name="show_time" type="time" class="form-control">
              <input name="genre" placeholder="Genre" class="form-control md:col-span-2">
              <input name="imdb_url" placeholder="IMDb URL (optional)" class="form-control md:col-span-2">
              <input name="rating" placeholder="Rating (e.g. 7.5)" class="form-control">
              <div class="md:col-span-2">
                <label class="text-sm text-gray-400">Poster image (required)</label>
                <input name="poster_file" type="file" accept="image/*" required class="form-control mt-1">
                <p class="text-xs text-gray-400 mt-1">Upload a poster image (JPG/PNG/WEBP/TIFF). Max 5MB.</p>
              </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary" <?php echo empty($managed_cinemas) ? 'disabled title="Add a location first"' : ''; ?>>Add Movie</button></div>
          </form>
          <?php endif; ?>

          <!-- Movie Cards Grid -->
          <div class="mt-8">
            <h4 class="font-display font-bold mb-3">All Movies</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <?php if (!empty($movies)): ?>
                <?php foreach ($movies as $m): ?>
                <div class="rounded-2xl overflow-hidden bg-white/5 p-0">
                  <div class="relative h-56 overflow-hidden bg-gray-800">
                    <?php if (!empty($m['poster'])): ?>
                      <img src="<?php echo esc(get_media_url($m['poster'])); ?>" alt="<?php echo esc($m['title']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                      <div class="w-full h-full flex items-center justify-center bg-coffee-300"><i data-lucide="film" class="w-12 h-12 text-coffee-300"></i></div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-3">
                      <div class="w-full flex items-center justify-between">
                        <div class="text-white font-semibold"><?php echo esc($m['title']); ?></div>
                        <div class="flex gap-2">
                          <a href="?view=movies&edit=<?php echo $m['id']; ?>" class="px-3 py-1 bg-amber-600/90 text-white rounded text-sm">Edit</a>
                          <form method="post" onsubmit="return confirm('Delete this movie?');" style="display:inline">
                            <input type="hidden" name="action" value="delete_movie">
                            <input type="hidden" name="movie_id" value="<?php echo $m['id']; ?>">
                            <button class="px-3 py-1 bg-red-600/90 text-white rounded text-sm">Delete</button>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                    <div class="p-3">
                    <div class="text-sm text-coffee-300"><?php echo esc($m['genre']); ?></div>
                    <div class="text-xs text-coffee-400 mt-1">Showing: <?php echo esc($m['show_date']); ?> <?php echo esc($m['show_time']); ?></div>
                    <?php
                      $imdbLink = $m['imdb_url'] ?? '';
                      $ratingVal = $m['rating'] ?? '';
                      if (empty($ratingVal) && !empty($imdbLink)) $ratingVal = get_imdb_rating($imdbLink);
                    ?>
                    <div class="mt-2 flex items-center justify-between">
                      <div class="text-sm text-amber-400"><?php echo esc($ratingVal ?: ''); ?></div>
                      <?php if (!empty($imdbLink)): ?>
                        <a href="<?php echo esc($imdbLink); ?>" target="_blank" class="text-xs text-amber-400">IMDb</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="col-span-full text-coffee-300">No movies to display.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      <?php else: ?>
        <div class="glass-panel p-6 rounded-2xl">
          <h3 class="font-display font-bold"><?php echo ucfirst($view); ?></h3>
          <p class="text-coffee-300">This section is coming soon or contains advanced tools. Contact the dev to enable features.</p>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<?php // Include map libraries only for this manager page (Google Maps if key provided, else Leaflet) ?>
<!-- Map libraries removed: coordinates are entered manually in the coords input -->

<script>
  // Toggle add forms
  document.getElementById('showAddLocation')?.addEventListener('click', function(){
    var f = document.getElementById('addLocationForm'); if(f) {
      f.classList.toggle('hidden');
      // If now visible, show map
      var coordsInput = document.getElementById('coords');
          if (coordsInput) {
            coordsInput.classList.toggle('hidden');
          }
    }
  });

  document.getElementById('showAddMovie')?.addEventListener('click', function(){
    var f = document.getElementById('addMovieForm'); if(f) f.classList.toggle('hidden');
  });

  // Initialize location picker (Google Maps or Leaflet)
  function initLocationPicker() {
    // Map removed: coordinates are entered manually in the `coords` field.
    return;
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php';
