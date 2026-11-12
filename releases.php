<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/release_schema.php';
$admin = require_admin();
$pdo = database_connection();
ensure_release_schema($pdo);

$errors = [];
$notice = '';
$allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

function store_artwork(array $file, array $allowedTypes): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Artwork upload failed. Please try again.');
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Artwork must be 5 MB or smaller.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']);
    if (!isset($allowedTypes[$mime])) throw new RuntimeException('Use a JPG, PNG or WEBP image.');
    $dir = __DIR__ . '/uploads/artwork';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Could not create the artwork folder.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    if (!move_uploaded_file((string)$file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save the artwork.');
    return 'uploads/artwork/' . $name;
}

function remove_artwork(?string $path): void
{
    if (!$path || !str_starts_with($path, 'uploads/artwork/')) return;
    $full = __DIR__ . '/' . $path;
    if (is_file($full)) @unlink($full);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Invalid release selected.');
            $stmt = $pdo->prepare('SELECT artwork_path FROM releases WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $old = $stmt->fetch();
            $pdo->prepare('DELETE FROM releases WHERE id = :id')->execute(['id' => $id]);
            if ($old) remove_artwork($old['artwork_path'] ?? null);
            redirect_to('releases.php?deleted=1');
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $artist = trim((string)($_POST['artist_name'] ?? ''));
            $type = (string)($_POST['release_type'] ?? 'single');
            $status = (string)($_POST['status'] ?? 'draft');
            $date = trim((string)($_POST['release_date'] ?? ''));
            $notes = trim((string)($_POST['notes'] ?? ''));
            if ($title === '' || mb_strlen($title) > 200) throw new RuntimeException('Enter a release title (maximum 200 characters).');
            if ($artist === '' || mb_strlen($artist) > 200) throw new RuntimeException('Enter an artist name (maximum 200 characters).');
            if (!in_array($type, ['single','ep','album'], true)) throw new RuntimeException('Choose a valid release type.');
            if (!in_array($status, ['draft','preparing','submitted','scheduled','released'], true)) throw new RuntimeException('Choose a valid status.');
            if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new RuntimeException('Enter a valid release date.');

            $oldArtwork = null;
            if ($id > 0) {
                $check = $pdo->prepare('SELECT artwork_path FROM releases WHERE id = :id');
                $check->execute(['id' => $id]);
                $existing = $check->fetch();
                if (!$existing) throw new RuntimeException('That release could not be found.');
                $oldArtwork = $existing['artwork_path'] ?? null;
            }
            $newArtwork = store_artwork($_FILES['artwork'] ?? [], $allowedTypes);
            $artwork = $newArtwork ?? $oldArtwork;
            $params = ['title'=>$title,'artist'=>$artist,'type'=>$type,'status'=>$status,'date'=>$date !== '' ? $date : null,'notes'=>$notes !== '' ? $notes : null,'artwork'=>$artwork];
            if ($id > 0) {
                $params['id'] = $id;
                $pdo->prepare('UPDATE releases SET title=:title, artist_name=:artist, release_type=:type, status=:status, release_date=:date, notes=:notes, artwork_path=:artwork, updated_at=NOW() WHERE id=:id')->execute($params);
                if ($newArtwork) remove_artwork($oldArtwork);
            } else {
                $pdo->prepare('INSERT INTO releases (title,artist_name,release_type,status,release_date,notes,artwork_path) VALUES (:title,:artist,:type,:status,:date,:notes,:artwork)')->execute($params);
            }
            redirect_to('releases.php?saved=1');
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

if (isset($_GET['saved'])) $notice = 'Release saved successfully.';
if (isset($_GET['deleted'])) $notice = 'Release deleted.';
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;
$editing = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM releases WHERE id=:id');
    $stmt->execute(['id'=>$editId]);
    $editing = $stmt->fetch() ?: null;
    if (!$editing) $errors[] = 'The release you selected was not found.';
}
$releases = $pdo->query('SELECT * FROM releases ORDER BY updated_at DESC, id DESC')->fetchAll();
function selected(string $actual, string $value): string { return $actual === $value ? ' selected' : ''; }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>My Releases · Zedex Music Studio</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="app-shell">
<aside class="sidebar"><a class="brand" href="index.php"><span class="brand-mark">Z</span><span>ZEDEX<span class="brand-sub">MUSIC STUDIO</span></span></a><div class="nav-label">WORKSPACE</div><a class="nav-item" href="index.php"><span>▦</span> Overview</a><a class="nav-item active" href="releases.php"><span>♫</span> My releases</a><a class="nav-item" href="distribution.php"><span>◈</span> Distribution</a><div class="sidebar-bottom"><span class="status-dot"></span> Secure admin session</div></aside>
<main class="main"><header class="topbar"><div><div class="eyebrow">YOUR CATALOGUE</div><h1>My Releases</h1></div><div class="account-area"><span><?= h((string)$admin['email']) ?></span><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="logout-button">Sign out</button></form></div></header>
<?php if ($notice): ?><div class="success-message"><?= h($notice) ?></div><?php endif; ?><?php foreach ($errors as $error): ?><div class="form-error"><?= h($error) ?></div><?php endforeach; ?>
<section class="panel release-editor"><div class="panel-heading"><div><div class="eyebrow"><?= $editing ? 'UPDATE YOUR CATALOGUE' : 'NEW MUSIC' ?></div><h3><?= $editing ? 'Edit release' : 'Add a release' ?></h3></div><?php if ($editing): ?><a class="secondary-button" href="releases.php">Cancel edit</a><?php endif; ?></div>
<form class="release-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
<label>Song / release title<input name="title" maxlength="200" required value="<?= h((string)($editing['title'] ?? '')) ?>" placeholder="Enter release title"></label>
<label>Artist name<input name="artist_name" maxlength="200" required value="<?= h((string)($editing['artist_name'] ?? '')) ?>" placeholder="Artist or band name"></label>
<label>Release type<select name="release_type"><?php foreach (['single'=>'Single','ep'=>'EP','album'=>'Album'] as $v=>$label): ?><option value="<?= $v ?>"<?= selected((string)($editing['release_type'] ?? 'single'),$v) ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label>Status<select name="status"><?php foreach (['draft'=>'Draft','preparing'=>'Preparing','submitted'=>'Submitted','scheduled'=>'Scheduled','released'=>'Released'] as $v=>$label): ?><option value="<?= $v ?>"<?= selected((string)($editing['status'] ?? 'draft'),$v) ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label>Release date<input type="date" name="release_date" value="<?= h((string)($editing['release_date'] ?? '')) ?>"></label>
<label>Cover artwork (JPG, PNG or WEBP · max 5 MB)<input type="file" name="artwork" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><?php if (!empty($editing['artwork_path'])): ?><small>Current artwork is saved. Choose a new image to replace it.</small><?php endif; ?></label>
<label class="full-field">Notes<textarea name="notes" rows="3" placeholder="Credits, reminders or release notes"><?= h((string)($editing['notes'] ?? '')) ?></textarea></label>
<div class="full-field"><button class="primary-button" type="submit"><?= $editing ? 'Save changes' : 'Add release' ?> <span>→</span></button></div></form></section>
<section class="panel release-catalogue"><div class="panel-heading"><div><div class="eyebrow">YOUR MUSIC</div><h3>Release catalogue</h3></div><span class="pill"><?= count($releases) ?> releases</span></div>
<?php if (!$releases): ?><div class="empty-state"><div class="empty-icon">♫</div><h4>No releases yet</h4><p>Use the form above to add your first song.</p></div><?php else: ?><div class="managed-release-list"><?php foreach ($releases as $release): ?><article class="managed-release">
<div class="release-art"><?php if (!empty($release['artwork_path'])): ?><img src="<?= h((string)$release['artwork_path']) ?>" alt="Cover artwork for <?= h((string)$release['title']) ?>"><?php else: ?><span>♫</span><?php endif; ?></div>
<div class="managed-release-info"><strong><?= h((string)$release['title']) ?></strong><small><?= h((string)$release['artist_name']) ?> · <?= h(strtoupper((string)$release['release_type'])) ?></small><span class="pill"><?= h(ucfirst((string)$release['status'])) ?></span><?php if (!empty($release['release_date'])): ?><small>Release date: <?= h((string)$release['release_date']) ?></small><?php endif; ?></div>
<div class="release-actions"><a class="secondary-button" href="releases.php?edit=<?= (int)$release['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this release? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$release['id'] ?>"><button class="danger-button" type="submit">Delete</button></form></div>
</article><?php endforeach; ?></div><?php endif; ?></section>
<footer>ZEDEX MUSIC STUDIO <span>PERSONAL MUSIC WORKSPACE</span></footer></main></div></body></html>
