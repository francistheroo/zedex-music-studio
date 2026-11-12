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

function read_artwork_upload(array $file, array $allowedTypes): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Artwork upload failed. Please try again.');
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Artwork must be 5 MB or smaller.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']);
    if (!isset($allowedTypes[$mime])) throw new RuntimeException('Use a JPG, PNG or WEBP image.');
    $data = file_get_contents((string)$file['tmp_name']);
    if ($data === false) throw new RuntimeException('Could not read the uploaded artwork.');
    return ['mime' => $mime, 'data' => $data];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Invalid release selected.');
            $pdo->prepare('DELETE FROM releases WHERE id = :id')->execute(['id' => $id]);
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
            if ($title === '' || strlen($title) > 800) throw new RuntimeException('Enter a release title (maximum 200 characters).');
            if ($artist === '' || strlen($artist) > 800) throw new RuntimeException('Enter an artist name (maximum 200 characters).');
            if (!in_array($type, ['single','ep','album'], true)) throw new RuntimeException('Choose a valid release type.');
            if (!in_array($status, ['draft','preparing','submitted','scheduled','released'], true)) throw new RuntimeException('Choose a valid status.');
            if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new RuntimeException('Enter a valid release date.');

            if ($id > 0) {
                $check = $pdo->prepare('SELECT id FROM releases WHERE id = :id');
                $check->execute(['id' => $id]);
                if (!$check->fetch()) throw new RuntimeException('That release could not be found.');
            }
            $newArtwork = read_artwork_upload($_FILES['artwork'] ?? [], $allowedTypes);
            $params = ['title'=>$title,'artist'=>$artist,'type'=>$type,'status'=>$status,'date'=>$date !== '' ? $date : null,'notes'=>$notes !== '' ? $notes : null,'artwork'=>$newArtwork ? 'database' : null];
            if ($id > 0) {
                $params['id'] = $id;
                $sql = 'UPDATE releases SET title=:title, artist_name=:artist, release_type=:type, status=:status, release_date=:date, notes=:notes, updated_at=NOW()';
                if ($newArtwork) $sql .= ', artwork_path=:artwork';
                $sql .= ' WHERE id=:id';
                if (!$newArtwork) unset($params['artwork']);
                $pdo->prepare($sql)->execute($params);
                $releaseId = $id;
            } else {
                $pdo->prepare('INSERT INTO releases (title,artist_name,release_type,status,release_date,notes,artwork_path) VALUES (:title,:artist,:type,:status,:date,:notes,:artwork)')->execute($params);
                $releaseId = (int)$pdo->lastInsertId('releases_id_seq');
            }
            if ($newArtwork) {
                $imageStmt = $pdo->prepare('INSERT INTO release_artwork (release_id,mime_type,image_data,updated_at) VALUES (:id,:mime,:data,NOW()) ON CONFLICT (release_id) DO UPDATE SET mime_type=EXCLUDED.mime_type,image_data=EXCLUDED.image_data,updated_at=NOW()');
                $imageStmt->bindValue(':id', $releaseId, PDO::PARAM_INT);
                $imageStmt->bindValue(':mime', $newArtwork['mime'], PDO::PARAM_STR);
                $imageStmt->bindValue(':data', $newArtwork['data'], PDO::PARAM_LOB);
                $imageStmt->execute();
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
<div class="release-art"><?php if (!empty($release['artwork_path'])): ?><img src="artwork.php?id=<?= (int)$release['id'] ?>" alt="Cover artwork for <?= h((string)$release['title']) ?>"><?php else: ?><span>♫</span><?php endif; ?></div>
<div class="managed-release-info"><strong><?= h((string)$release['title']) ?></strong><small><?= h((string)$release['artist_name']) ?> · <?= h(strtoupper((string)$release['release_type'])) ?></small><span class="pill"><?= h(ucfirst((string)$release['status'])) ?></span><?php if (!empty($release['release_date'])): ?><small>Release date: <?= h((string)$release['release_date']) ?></small><?php endif; ?></div>
<div class="release-actions"><a class="secondary-button" href="releases.php?edit=<?= (int)$release['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this release? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$release['id'] ?>"><button class="danger-button" type="submit">Delete</button></form></div>
</article><?php endforeach; ?></div><?php endif; ?></section>
<footer>ZEDEX MUSIC STUDIO <span>PERSONAL MUSIC WORKSPACE</span></footer></main></div></body></html>
