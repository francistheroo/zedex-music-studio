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
        if (!in_array($status, $validStatuses, true)) throw new RuntimeException('Choose a valid release status.');
        $distributor = trim((string)($_POST['distributor_name'] ?? ''));
        $url = trim((string)($_POST['distributor_url'] ?? ''));
        $submitted = trim((string)($_POST['submitted_at'] ?? ''));
        $platforms = $_POST['platforms'] ?? [];
        if (!is_array($platforms)) $platforms = [];
        $allowedPlatforms = ['Spotify','Apple Music','YouTube Music','Instagram Music','Amazon Music','Deezer','Other'];
        $platforms = array_values(array_intersect($allowedPlatforms, array_map('strval', $platforms)));
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) throw new RuntimeException('Enter a complete distributor tracking URL beginning with https://.');
        if ($url !== '' && !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['https','http'], true)) throw new RuntimeException('Distributor URL must use HTTP or HTTPS.');
        if ($submitted !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $submitted)) throw new RuntimeException('Enter a valid submission date.');
        $stmt = $pdo->prepare('UPDATE releases SET status=:status, distributor_name=:distributor, distributor_url=:url, submitted_at=:submitted, platforms=:platforms, updated_at=NOW() WHERE id=:id');
        $stmt->execute(['status'=>$status,'distributor'=>$distributor !== '' ? $distributor : null,'url'=>$url !== '' ? $url : null,'submitted'=>$submitted !== '' ? $submitted : null,'platforms'=>implode(', ', $platforms),'id'=>$id]);
        if ($stmt->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT id FROM releases WHERE id=:id'); $exists->execute(['id'=>$id]);
            if (!$exists->fetch()) throw new RuntimeException('Release not found.');
        }
        redirect_to('distribution.php?saved=1');
    } catch (Throwable $e) { $errors[] = $e->getMessage(); }
}
if (isset($_GET['saved'])) $notice = 'Distribution details updated.';
$releases = $pdo->query('SELECT id,title,artist_name,status,release_date,distributor_name,distributor_url,submitted_at,platforms FROM releases ORDER BY updated_at DESC,id DESC')->fetchAll();
$platformOptions = ['Spotify','Apple Music','YouTube Music','Instagram Music','Amazon Music','Deezer','Other'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Distribution · Zedex Music Studio</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="app-shell"><aside class="sidebar"><a class="brand" href="index.php"><span class="brand-mark">Z</span><span>ZEDEX<span class="brand-sub">MUSIC STUDIO</span></span></a><div class="nav-label">WORKSPACE</div><a class="nav-item" href="index.php"><span>▦</span> Overview</a><a class="nav-item" href="releases.php"><span>♫</span> My releases</a><a class="nav-item active" href="distribution.php"><span>◈</span> Distribution</a><div class="sidebar-bottom"><span class="status-dot"></span> Secure admin session</div></aside>
<main class="main"><header class="topbar"><div><div class="eyebrow">DELIVERY & RELEASE TRACKING</div><h1>Distribution</h1></div><div class="account-area"><span><?= h((string)$admin['email']) ?></span><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="logout-button">Sign out</button></form></div></header>
<?php if ($notice): ?><div class="success-message"><?= h($notice) ?></div><?php endif; ?><?php foreach ($errors as $error): ?><div class="form-error"><?= h($error) ?></div><?php endforeach; ?>
<section class="panel"><div class="eyebrow">HOW MUSIC DELIVERY WORKS</div><h3>Choose an authorised distributor</h3><p class="panel-copy">A distributor delivers your approved audio and release metadata to streaming services. Zedex helps you prepare and track the release; it does not upload music to these platforms itself.</p>
<div class="distributor-grid">
<article class="distributor-card"><strong>RouteNote</strong><p>Offers free and paid distribution options. Check current terms, payout rules and eligibility.</p><a href="https://www.routenote.com/" target="_blank" rel="noopener noreferrer">Official RouteNote website ↗</a></article>
<article class="distributor-card"><strong>DistroKid</strong><p>Subscription-based music distribution. Review plan features, renewal and account requirements.</p><a href="https://distrokid.com/" target="_blank" rel="noopener noreferrer">Official DistroKid website ↗</a></article>
<article class="distributor-card"><strong>TuneCore</strong><p>Music distribution and artist services. Check current pricing and available plans.</p><a href="https://www.tunecore.com/" target="_blank" rel="noopener noreferrer">Official TuneCore website ↗</a></article>
<article class="distributor-card"><strong>CD Baby</strong><p>Music distribution services. Review current fees, terms and delivery options.</p><a href="https://cdbaby.com/" target="_blank" rel="noopener noreferrer">Official CD Baby website ↗</a></article>
</div><div class="notice">Before signing up or paying, check each distributor’s current age, account, payment and rights requirements. If you are under 18, ask a parent or guardian to help with any account or agreement that requires an adult. Only submit music and artwork you have permission to distribute.</div></section>
<section class="panel distribution-tracker"><div class="panel-heading"><div><div class="eyebrow">YOUR RELEASES</div><h3>Distribution tracker</h3></div><span class="pill"><?= count($releases) ?> releases</span></div>
<?php if (!$releases): ?><div class="empty-state"><div class="empty-icon">◈</div><h4>No releases to track</h4><p>Add a song in My Releases to start tracking its distribution.</p><a class="primary-button" href="releases.php">Go to My Releases →</a></div><?php else: ?>
<?php foreach ($releases as $release): $selectedPlatforms = array_filter(array_map('trim', explode(',', (string)($release['platforms'] ?? '')))); ?>
<article class="distribution-item"><div class="distribution-title"><div><strong><?= h((string)$release['title']) ?></strong><small><?= h((string)$release['artist_name']) ?><?= !empty($release['release_date']) ? ' · Release date: ' . h((string)$release['release_date']) : '' ?></small></div><span class="pill"><?= h(ucfirst((string)$release['status'])) ?></span></div>
<form method="post" class="distribution-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$release['id'] ?>">
<label>Distributor name<input name="distributor_name" maxlength="200" value="<?= h((string)($release['distributor_name'] ?? '')) ?>" placeholder="e.g. RouteNote"></label>
<label>Distributor release / dashboard link<input type="url" name="distributor_url" value="<?= h((string)($release['distributor_url'] ?? '')) ?>" placeholder="https://..."></label>
<label>Submission date<input type="date" name="submitted_at" value="<?= h((string)($release['submitted_at'] ?? '')) ?>"></label>
<label>Release status<select name="status"><?php foreach (['draft'=>'Draft','preparing'=>'Preparing','submitted'=>'Submitted','scheduled'=>'Scheduled','released'=>'Released'] as $v=>$label): ?><option value="<?= $v ?>"<?= $release['status']===$v?' selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<fieldset class="platform-checks"><legend>Target platforms</legend><?php foreach ($platformOptions as $platform): ?><label><input type="checkbox" name="platforms[]" value="<?= h($platform) ?>"<?= in_array($platform,$selectedPlatforms,true)?' checked':'' ?>> <?= h($platform) ?></label><?php endforeach; ?></fieldset>
<div class="full-field"><button class="secondary-button" type="submit">Save tracking details</button><?php if (!empty($release['distributor_url'])): ?> <a class="text-link" href="<?= h((string)$release['distributor_url']) ?>" target="_blank" rel="noopener noreferrer">Open distributor link ↗</a><?php endif; ?></div></form></article>
<?php endforeach; endif; ?></section>
<footer>ZEDEX MUSIC STUDIO <span>PERSONAL MUSIC WORKSPACE</span></footer></main></div></body></html>
