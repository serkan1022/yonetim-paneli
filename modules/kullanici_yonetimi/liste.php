<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canAdd, $canUpdate, $canDelete
 */

$editId   = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$errorMsg = '';

// Grup seçim listesi (sadece aktif gruplar)
$allGroups = $pdo->query("SELECT id, group_name FROM groups_table WHERE is_active = 1 ORDER BY group_name")->fetchAll();

// ---- SİLME ----
if (isset($_GET['delete']) && $canDelete) {
    $deleteId = (int)$_GET['delete'];

    if ($deleteId === (int)($_SESSION['user_id'] ?? 0)) {
        $errorMsg = 'Kendi hesabınızı silemezsiniz.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $deleteId]);
        header('Location: index.php?page=kullanici_listesi');
        exit;
    }
}

// ---- EKLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $canAdd) {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $groupId  = (int)($_POST['group_id'] ?? 0);

    if ($username === '' || $password === '' || $groupId === 0) {
        $errorMsg = 'Kullanıcı adı, şifre ve grup seçimi zorunludur.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (group_id, username, password_hash, full_name) VALUES (:g, :u, :p, :f)"
            );
            $stmt->execute(['g' => $groupId, 'u' => $username, 'p' => $hash, 'f' => $fullName]);
            header('Location: index.php?page=kullanici_listesi');
            exit;
        } catch (PDOException $e) {
            $errorMsg = 'Bu kullanıcı adı zaten kullanılıyor.';
        }
    }
}

// ---- GÜNCELLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id']) && $canUpdate) {
    $updateId = (int)$_POST['update_id'];
    $fullName = trim($_POST['full_name'] ?? '');
    $groupId  = (int)($_POST['group_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $newPass  = $_POST['password'] ?? '';

    if ($groupId === 0) {
        $errorMsg = 'Grup seçimi zorunludur.';
    } else {
        if ($newPass !== '') {
            // Şifre de değiştirilmek isteniyor
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "UPDATE users SET full_name = :f, group_id = :g, is_active = :a, password_hash = :p WHERE id = :id"
            );
            $stmt->execute(['f' => $fullName, 'g' => $groupId, 'a' => $isActive, 'p' => $hash, 'id' => $updateId]);
        } else {
            $stmt = $pdo->prepare(
                "UPDATE users SET full_name = :f, group_id = :g, is_active = :a WHERE id = :id"
            );
            $stmt->execute(['f' => $fullName, 'g' => $groupId, 'a' => $isActive, 'id' => $updateId]);
        }
        header('Location: index.php?page=kullanici_listesi');
        exit;
    }
}

// ---- DÜZENLENEN KULLANICIYI ÇEK ----
$editingUser = null;
if ($editId && $canUpdate) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editingUser = $stmt->fetch();
}

// ---- LİSTE ----
$users = $pdo->query(
    "SELECT u.*, g.group_name
     FROM users u
     INNER JOIN groups_table g ON g.id = u.group_id
     ORDER BY u.id ASC"
)->fetchAll();
?>

<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>

<?php if ($editingUser): ?>
    <!-- ================= DÜZENLEME FORMU ================= -->
    <div class="card">
        <h2>Kullanıcıyı Düzenle: <?= e($editingUser['username']) ?></h2>
        <form method="post" action="index.php?page=kullanici_listesi">
            <input type="hidden" name="update_id" value="<?= (int)$editingUser['id'] ?>">

            <label>Kullanıcı Adı</label>
            <input type="text" value="<?= e($editingUser['username']) ?>" disabled
                   style="width:100%;padding:8px;margin-bottom:12px;background:#f3f4f6;">
            <p style="font-size:12px;color:#6b7280;margin-top:-8px;margin-bottom:12px;">Kullanıcı adı değiştirilemez.</p>

            <label>Ad Soyad</label>
            <input type="text" name="full_name" value="<?= e($editingUser['full_name'] ?? '') ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;">

            <label>Grup</label>
            <select name="group_id" style="width:100%;padding:8px;margin-bottom:12px;" required>
                <?php foreach ($allGroups as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === (int)$editingUser['group_id'] ? 'selected' : '' ?>>
                        <?= e($g['group_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Yeni Şifre (boş bırakılırsa değişmez)</label>
            <input type="password" name="password" style="width:100%;padding:8px;margin-bottom:12px;">

            <label style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <input type="checkbox" name="is_active" value="1" <?= $editingUser['is_active'] ? 'checked' : '' ?>>
                Aktif
            </label>

            <button type="submit" class="btn btn-success">Güncelle</button>
            <a href="index.php?page=kullanici_listesi" class="btn" style="background:#e5e7eb;">Vazgeç</a>
        </form>
    </div>
<?php else: ?>

    <?php if ($canAdd): ?>
    <!-- ================= YENİ KULLANICI EKLEME FORMU ================= -->
    <div class="card">
        <h2>Yeni Kullanıcı Ekle</h2>
        <form method="post" action="index.php?page=kullanici_listesi">
            <input type="hidden" name="action" value="create">

            <label>Kullanıcı Adı</label>
            <input type="text" name="username" style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Ad Soyad</label>
            <input type="text" name="full_name" style="width:100%;padding:8px;margin-bottom:12px;">

            <label>Grup</label>
            <select name="group_id" style="width:100%;padding:8px;margin-bottom:12px;" required>
                <option value="">-- Grup Seçin --</option>
                <?php foreach ($allGroups as $g): ?>
                    <option value="<?= (int)$g['id'] ?>"><?= e($g['group_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Şifre</label>
            <input type="password" name="password" style="width:100%;padding:8px;margin-bottom:16px;" required>

            <button type="submit" class="btn btn-primary">Kullanıcı Ekle</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ================= KULLANICI LİSTESİ ================= -->
    <div class="card">
        <h2>Kullanıcı Listesi</h2>
        <table class="data-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Kullanıcı Adı</th>
                <th>Ad Soyad</th>
                <th>Grup</th>
                <th>Durum</th>
                <th>Son Giriş</th>
                <?php if ($canUpdate || $canDelete): ?><th style="width:160px;">İşlemler</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['full_name'] ?? '-') ?></td>
                    <td><?= e($u['group_name']) ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge badge-yes">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-no">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['last_login'] ? e($u['last_login']) : '-' ?></td>
                    <?php if ($canUpdate || $canDelete): ?>
                        <td>
                            <?php if ($canUpdate): ?>
                                <a href="index.php?page=kullanici_listesi&edit=<?= (int)$u['id'] ?>" class="btn btn-primary">Düzenle</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="index.php?page=kullanici_listesi&delete=<?= (int)$u['id'] ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">Sil</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>