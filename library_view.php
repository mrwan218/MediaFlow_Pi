<?php require_once 'header.php'; ?>
<?php
if (!isset($_GET['name']) || empty(trim($_GET['name']))) {
    echo '<div class="dashboard-card full-width"><h2>Error</h2><p>No library specified.</p></div>';
} else {
    $library_name = urldecode(trim($_GET['name']));
    $media_items = [];
    $is_allowed = false;
    $config_file_path = __DIR__ . '/backend/config.json';
    if (file_exists($config_file_path)) {
        $config = json_decode(file_get_contents($config_file_path), true);
        foreach ($config['libraries'] as $lib) {
            if ($lib['name'] === $library_name && $lib['public']) {
                $is_allowed = true;
                break;
            }
        }
    }
    if (!$is_allowed) {
        $stmt = $conn->prepare("SELECT 1 FROM user_library_access WHERE user_id = ? AND library_name = ?");
        $stmt->bind_param("is", $_SESSION['user_id'], $library_name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $is_allowed = true;
        }
        $stmt->close();
    }
    if ($is_allowed) {
        $rating_hierarchy = ['G' => 1, 'PG' => 2, 'PG-13' => 3, 'R' => 4, 'NC-17' => 5];
        $user_max_rating = $_SESSION['max_allowed_rating'] ?? 'R';
        $user_max_level = $rating_hierarchy[$user_max_rating];

        $stmt = $conn->prepare("SELECT id, file_path, title, year, poster_path, overview, backdrop_path, rating FROM media_items WHERE library_name = ? ORDER BY title ASC");
        $stmt->bind_param("s", $library_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $all_items = $result->fetch_all(MYSQLI_ASSOC);
        $media_items = [];
        foreach ($all_items as $item) {
            $item_rating = $item['rating'] ?? 'R';
            $item_level = $rating_hierarchy[$item_rating] ?? 4;
            if ($item_level <= $user_max_level) {
                $media_items[] = $item;
            }
        }
        $stmt->close();
    }
?>
    <div class="library-header">
        <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        <h1><?php echo htmlspecialchars($library_name); ?></h1>
    </div>
    <div class="media-grid">
        <?php if (!$is_allowed): ?>
            <div class="dashboard-card full-width"><h2>Access Denied</h2><p>You do not have permission to view this library.</p></div>
        <?php elseif (empty($media_items)): ?>
            <div class="dashboard-card full-width"><h2>No Available Content</h2><p>This library has no media files that match your content rating permissions.</p></div>
        <?php else: ?>
            <?php foreach ($media_items as $item): ?>
                <?php
                    $posterUrl = !empty($item['poster_path']) ? "https://image.tmdb.org/t/p/w400" . $item['poster_path'] : 'assets/images/no-poster.png';
                    $title = htmlspecialchars($item['title'] ?? basename($item['file_path']));
                    $year = htmlspecialchars($item['year'] ?? '');
                    $overview = htmlspecialchars($item['overview'] ?? 'No overview available.');
                    $rating = htmlspecialchars($item['rating'] ?? 'N/A');
                ?>
                <div class="media-card" onclick="openModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                    <img src="<?php echo $posterUrl; ?>" alt="<?php echo $title; ?> Poster" class="media-poster" loading="lazy">
                    <div class="media-info">
                        <h3 class="media-title"><?php echo $title; ?></h3>
                        <span class="media-year"><?php echo $year; ?></span>
                        <span class="media-rating">Rated: <?php echo $rating; ?></span>
                    </div>
                    <div class="media-overlay">
                        <span class="play-icon">&#9658;</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div id="media-modal" class="media-modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <div class="modal-body">
                <div class="modal-media">
                    <img id="modal-backdrop" src="" alt="" class="modal-backdrop" loading="lazy">
                    <video id="modal-video" controls style="display: none; width: 100%; height: auto;"></video>
                </div>
                <div class="modal-details">
                    <h2 id="modal-title"></h2>
                    <p id="modal-overview"></p>
                    <button id="modal-play-btn" class="btn-play">Play Now</button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
</main>
</body>
</html>
