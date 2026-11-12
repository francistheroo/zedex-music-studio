<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/release_schema.php';
$admin = require_admin();
$pdo = database_connection();
ensure_release_schema($pdo);
$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) throw new RuntimeException('Choose a valid release.');
        $status = (string)($_POST['status'] ?? 'draft');
        $validStatuses = ['draft','preparing','submitted','scheduled','released'];
        if (!in_array($status, $validStatuses, true)) throw new RuntimeException('Choose a valid delivery status.');
        $submitted = trim((string)($_POST['submitted_at'] ?? ''));
        $platforms = $_POST['platforms'] ?? [];
        if (!is_array($platforms)) $platforms = [];
        $allowedPlatforms = ['Spotify','Apple Music','YouTube Music','Instagram Music','Amazon Music','Deezer','Other'];
        $platforms = array_values(array_intersect($allowedPlatforms, array_map('strval', $platforms)));
        if ($submitted !== '' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $submitted)) throw new RuntimeException('Enter a valid date.');
        $stmt = $pdo->prepare('UPDATE releases SET status=:status, submitted_at=:submitted, platforms=:platforms, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['status'=>$status,'submitted'=>$submitted !== '' ? $submitted : null,'platforms'=>implode(', ', $platforms),'id'=>$id]);
        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT id FROM releases WHERE id=:id'); $exists->execute(['id'=>$id]);
            if (!$exists->fetch()) throw new RuntimeException('Release not found.');
        }
        redirect_to('distribution.php?saved=1');
    } catch (Throwable $e) { $errors[] = $e->getMessage(); }
}
if (isset($_GET['saved'])) $notice = 'Zedex delivery plan updated.';
$releases = $pdo->query('SELECT id,title,artist_name,status,release_date,submitted_at,platforms FROM releases ORDER BY updated_at DESC,id DESC')->fetchAll();
$platformOptions = ['Spotify','Apple Music','YouTube Music','Instagram Music','Amazon Music','Deezer','Other'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Zedex Delivery · Zedex Music Studio</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="app-shell"><aside class="sidebar"><a class="brand" href="index.php"><span class="brand-mark">Z</span><span>ZEDEX<span class="brand-sub">MUSIC STUDIO</span></span></a><div class="nav-label">WORKSPACE</div><a class="nav-item" href="index.php"><span>▦</span> Overview</a><a class="nav-item" href="releases.php"><span>♫</span> My releases</a><a class="nav-item active" href="distribution.php"><span>◈</span> Zedex delivery</a><div class="sidebar-bottom"><span class="status-dot"></span> Private admin workspace</div></aside>
<main class="main"><header class="topbar"><div><div class="eyebrow">PRIVATE DELIVERY WORKSPACE</div><h1>Zedex Delivery</h1></div><div class="account-area"><span><?= h((string)$admin['email']) ?></span><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="logout-button">Sign out</button></form></div></header>
<?php if ($notice): ?><div class="success-message"><?= h($notice) ?></div><?php endif; ?><?php foreach ($errors as $error): ?><div class="form-error"><?= h($error) ?></div><?php endforeach; ?>
<section class="panel"><div class="eyebrow">ZEDEX DIRECT DELIVERY</div><h3>Your private music delivery workspace</h3><p class="panel-copy">Prepare and manage your own releases for future direct delivery from Zedex. This dashboard currently stores release plans and target platforms; it does not yet transmit audio or metadata to streaming services.</p>
<div class="notice">Direct delivery can only be activated after Zedex obtains the required platform or authorised delivery-partner agreements, credentials and technical approval. Until then, every platform connection is shown as not connected. No release will be marked as delivered automatically.</div>
<div class="platform-list"><span>Spotify · Not connected</span><span>Apple Music · Not connected</span><span>YouTube Music · Not connected</span><span>Instagram Music · Not connected</span><span>Amazon Music · Not connected</span><span>Deezer · Not connected</span></div></section>
<section class="panel distribution-tracker"><div class="panel-heading"><div><div class="eyebrow">PRIVATE CATALOGUE</div><h3>Release delivery plans</h3></div><span class="pill"><?= count($releases) ?> releases</span></div>
<?php if (!$releases): ?><div class="empty-state"><div class="empty-icon">◈</div><h4>No releases to prepare</h4><p>Add a song in My Releases to create its Zedex delivery plan.</p><a class="primary-button" href="releases.php">Go to My Releases →</a></div><?php else: ?>
<?php foreach ($releases as $release): $selectedPlatforms = array_filter(array_map('trim', explode(',', (string)($release['platforms'] ?? '')))); ?>
<article class="distribution-item"><div class="distribution-title"><div><strong><?= h((string)$release['title']) ?></strong><small><?= h((string)$release['artist_name']) ?><?= !empty($release['release_date']) ? ' · Planned release: ' . h((string)$release['release_date']) : '' ?></small></div><span class="pill"><?= h(ucfirst((string)$release['status'])) ?></span></div>
<form method="post" class="distribution-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$release['id'] ?>">
<label>Preparation / submission date<input type="date" name="submitted_at" value="<?= h((string)($release['submitted_at'] ?? '')) ?>"></label>
<label>Internal delivery status<select name="status"><?php foreach (['draft'=>'Draft','preparing'=>'Preparing files','submitted'=>'Ready for approval','scheduled'=>'Approved / scheduled','released'=>'Confirmed released'] as $v=>$label): ?><option value="<?= $v ?>"<?= $release['status']===$v?' selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<fieldset class="platform-checks"><legend>Intended streaming platforms</legend><?php foreach ($platformOptions as $platform): ?><label><input type="checkbox" name="platforms[]" value="<?= h($platform) ?>"<?= in_array($platform,$selectedPlatforms,true)?' checked':'' ?>> <?= h($platform) ?></label><?php endforeach; ?></fieldset>
<div class="full-field"><button class="secondary-button" type="submit">Save Zedex delivery plan</button></div></form></article>
<?php endforeach; endif; ?></section>
<footer>ZEDEX MUSIC STUDIO <span>PRIVATE DISTRIBUTION WORKSPACE</span></footer></main></div></body></html>
