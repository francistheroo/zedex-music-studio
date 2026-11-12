<?php
declare(strict_types=1);
session_start();
$appName = 'Zedex Music Studio';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#090b12">
  <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <a class="brand" href="index.php"><span class="brand-mark">Z</span><span>ZEDEX<span class="brand-sub">MUSIC STUDIO</span></span></a>
      <div class="nav-label">WORKSPACE</div>
      <a class="nav-item active" href="index.php"><span>▦</span> Overview</a>
      <a class="nav-item" href="#releases"><span>♫</span> My releases</a>
      <a class="nav-item" href="#distribution"><span>◈</span> Distribution</a>
      <div class="sidebar-bottom"><span class="status-dot"></span> Personal workspace</div>
    </aside>

    <main class="main">
      <header class="topbar">
        <div><div class="eyebrow">YOUR MUSIC, YOUR CONTROL</div><h1>Overview</h1></div>
        <div class="avatar">Z</div>
      </header>

      <section class="welcome">
        <div>
          <div class="eyebrow">WELCOME TO YOUR STUDIO</div>
          <h2>Make your next release<br><span>count.</span></h2>
          <p>Keep your songs, artwork, release details and distribution progress organised in one place.</p>
          <a class="primary-button" href="#releases">Explore your workspace <span>↗</span></a>
        </div>
        <div class="hero-art" aria-hidden="true"><div class="disc"><div class="disc-center">Z</div></div><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div></div>
      </section>

      <section class="stats" aria-label="Release summary">
        <article class="stat-card"><div class="stat-icon">♫</div><div class="stat-label">TOTAL RELEASES</div><div class="stat-value">—</div><div class="stat-note">Your music catalogue</div></article>
        <article class="stat-card"><div class="stat-icon">◷</div><div class="stat-label">IN PROGRESS</div><div class="stat-value">—</div><div class="stat-note">Releases being prepared</div></article>
        <article class="stat-card"><div class="stat-icon">✓</div><div class="stat-label">RELEASED</div><div class="stat-value">—</div><div class="stat-note">Published releases</div></article>
      </section>

      <section class="section-grid" id="releases">
        <article class="panel">
          <div class="panel-heading"><div><div class="eyebrow">YOUR CATALOGUE</div><h3>Recent releases</h3></div><span class="pill">Coming next</span></div>
          <div class="empty-state"><div class="empty-icon">♫</div><h4>Your music space is ready</h4><p>Release entries will appear here once catalogue management is connected.</p></div>
        </article>
        <article class="panel" id="distribution">
          <div class="eyebrow">DISTRIBUTION</div><h3>Release everywhere</h3>
          <p class="panel-copy">Prepare your release information here, then use an authorised music distributor to deliver tracks to supported platforms.</p>
          <div class="platform-list"><span>Instagram Music</span><span>Spotify</span><span>Apple Music</span><span>YouTube Music</span></div>
          <div class="notice">Zedex Music Studio organises your releases. It does not itself deliver music to streaming platforms.</div>
        </article>
      </section>
      <footer>ZEDEX MUSIC STUDIO <span>PERSONAL MUSIC WORKSPACE</span></footer>
    </main>
  </div>
</body>
</html>
