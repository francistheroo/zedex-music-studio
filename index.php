<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$admin = require_admin();

$stats = ['total' => 0, 'progress' => 0, 'released' => 0];
$recentReleases = [];
try {
    $pdo = database_connection();
    $stats['total'] = (int)$pdo->query('SELECT COUNT(*) FROM releases')->fetchColumn();
    $stats['progress'] = (int)$pdo->query("SELECT COUNT(*) FROM releases WHERE status IN ('preparing','submitted','scheduled')")->fetchColumn();
    $stats['released'] = (int)$pdo->query("SELECT COUNT(*) FROM releases WHERE status = 'released'")->fetchColumn();
    $recentReleases = $pdo->query('SELECT title, artist_name, status, release_date FROM releases ORDER BY updated_at DESC LIMIT 5')->fetchAll();
} catch (Throwable $e) {
    error_log('Zedex dashboard database error: ' . $e->getMessage());
    $databaseError = true;
}
$appName = 'Zedex Music Studio';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#090b12"><title><?= h($appName) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <a class="brand" href="index.php"><span class="brand-mark">Z</span><span>ZEDEX<span class="brand-sub">MUSIC STUDIO</span></span></a>
      <div class="nav-label">WORKSPACE</div>
      <a class="nav-item active" href="index.php"><span>▦</span> Overview</a>
      <a class="nav-item" href="releases.php"><span>♫</span> My releases</a>
      <a class="nav-item" href="distribution.php"><span>◈</span> Distribution</a>
      <div class="sidebar-bottom"><span class="status-dot"></span> Secure admin session</div>
    </aside>
    <main class="main">
      <header class="topbar">
        <div><div class="eyebrow">YOUR MUSIC, YOUR CONTROL</div><h1>Overview</h1></div>
        <div class="account-area"><span><?= h((string)$admin['email']) ?></span><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="logout-button" type="submit">Sign out</button></form></div>
      </header>
      <?php if (!empty($databaseError)): ?><div class="form-error">Could not load release information. Check your Neon database configuration.</div><?php endif; ?>
      <section class="welcome">
        <div><div class="eyebrow">WELCOME TO YOUR STUDIO</div><h2>Make your next release<br><span>count.</span></h2>
          <p>Keep your songs, artwork, release details and distribution progress organised in one place.</p>
          <a class="primary-button" href="releases.php">Manage your releases <span>↗</span></a>
        </div>
        <div class="hero-art" aria-hidden="true"><div class="disc"><div class="disc-center">Z</div></div><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div></div>
      </section>
      <section class="stats" aria-label="Release summary">
        <article class="stat-card"><div class="stat-icon">♫</div><div class="stat-label">TOTAL RELEASES</div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-note">Your music catalogue</div></article>
        <article class="stat-card"><div class="stat-icon">◷</div><div class="stat-label">IN PROGRESS</div><div class="stat-value"><?= $stats['progress'] ?></div><div class="stat-note">Releases being prepared</div></article>
        <article class="stat-card"><div class="stat-icon">✓</div><div class="stat-label">RELEASED</div><div class="stat-value"><?= $stats['released'] ?></div><div class="stat-note">Published releases</div></article>
      </section>
      <section class="section-grid" id="releases">
        <article class="panel">
          <div class="panel-heading"><div><div class="eyebrow">YOUR CATALOGUE</div><h3>Recent releases</h3></div><span class="pill">Catalogue database ready</span></div>
          <?php if ($recentReleases): ?>
            <div class="release-list"><?php foreach ($recentReleases as $release): ?>
              <div class="release-row"><div><strong><?= h($release['title']) ?></strong><small><?= h($release['artist_name']) ?></small></div><span class="pill"><?= h(ucfirst($release['status'])) ?></span></div>
            <?php endforeach; ?></div>
          <?php else: ?>
            <div class="empty-state"><div class="empty-icon">♫</div><h4>Your music space is ready</h4><p>Your release catalogue is empty. Add your first release to get started.</p><a class="primary-button" href="releases.php">Add your first release →</a></div>
          <?php endif; ?>
        </article>
        <article class="panel" id="distribution">
          <div class="eyebrow">DISTRIBUTION</div><h3>Release everywhere</h3>
          <p class="panel-copy">Prepare your release information here, then use an authorised music distributor to deliver tracks to supported platforms.</p>
          <div class="platform-list"><span>Instagram Music</span><span>Spotify</span><span>Apple Music</span><span>YouTube Music</span></div>
          <div class="notice">Zedex Music Studio organises your releases. It does not itself deliver music to streaming platforms.</div><a class="secondary-button" href="distribution.php">Open distribution tracker →</a>
        </article>
      </section>
      <footer>ZEDEX MUSIC STUDIO <span>PERSONAL MUSIC WORKSPACE</span></footer>
    </main>
  </div>
</body>
</html>
