<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canAdd, $canUpdate, $canDelete
 */

$editId    = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$errorMsg  = '';

// ---- SİLME ----
if (isset($_GET['delete']) && $canDelete) {
    $deleteId = (int)$_GET['delete'];

    // Bu gruba bağlı kullanıcı var mı kontrol et
    $check = $pdo->prepare("SELECT COUNT(*) AS adet FROM users WHERE group_id = :id");
    $check->execute(['id' => $deleteId]);
    $adet = (int)$check->fetch()['adet'];

    if ($adet > 0) {
        $errorMsg = "Bu grup silinemez: {$adet} kullanıcı bu gruba bağlı. Önce kullanıcıları başka bir gruba taşıyın.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM groups_table WHERE id = :id");
        $stmt->execute(['id' => $deleteId]);
        header('Location: index.php?page=grup_listesi');
        exit;
    }
}

// ---- EKLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create' && $canAdd) {
    $groupName = trim($_POST['group_name'] ?? '');
    $desc      = trim($_POST['description'] ?? '');

    if ($groupName === '') {
        $errorMsg = 'Grup adı zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO groups_table (group_name, description) VALUES (:n, :d)");
            $stmt->execute(['n' => $groupName, 'd' => $desc]);
            header('Location: index.php?page=grup_listesi');
            exit;
        } catch (PDOException $e) {
            $errorMsg = 'Bu grup adı zaten kullanılıyor.';
        }
    }
}

// ---- GÜNCELLEME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id']) && $canUpdate) {
    $updateId  = (int)$_POST['update_id'];
    $groupName = trim($_POST['group_name'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    if ($groupName !== '') {
        $stmt = $pdo->prepare(
            "UPDATE groups_table SET group_name = :n, description = :d, is_active = :a WHERE id = :id"
        );
        $stmt->execute(['n' => $groupName, 'd' => $desc, 'a' => $isActive, 'id' => $updateId]);
        header('Location: index.php?page=grup_listesi');
        exit;
    }
}

// ---- DÜZENLENEN GRUBU ÇEK ----
$editingGroup = null;
if ($editId && $canUpdate) {
    $stmt = $pdo->prepare("SELECT * FROM groups_table WHERE id = :id");
    $stmt->execute(['id' => $editId]);
    $editingGroup = $stmt->fetch();
}

// ---- LİSTE (her grubun kullanıcı sayısıyla birlikte) ----
$groups = $pdo->query(
    "SELECT g.*, (SELECT COUNT(*) FROM users u WHERE u.group_id = g.id) AS user_count
     FROM groups_table g
     ORDER BY g.id ASC"
)->fetchAll();
?>

<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>

<?php if ($editingGroup): ?>
    <!-- ================= DÜZENLEME FORMU ================= -->
    <div class="card">
        <h2>Grubu Düzenle</h2>
        <form method="post" action="index.php?page=grup_listesi">
            <input type="hidden" name="update_id" value="<?= (int)$editingGroup['id'] ?>">

            <label>Grup Adı</label>
            <input type="text" name="group_name" value="<?= e($editingGroup['group_name']) ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Açıklama</label>
            <input type="text" name="description" value="<?= e($editingGroup['description'] ?? '') ?>"
                   style="width:100%;padding:8px;margin-bottom:12px;">

            <label style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <input type="checkbox" name="is_active" value="1" <?= $editingGroup['is_active'] ? 'checked' : '' ?>>
                Aktif
            </label>

            <button type="submit" class="btn btn-success">Güncelle</button>
            <a href="index.php?page=grup_listesi" class="btn" style="background:#e5e7eb;">Vazgeç</a>
        </form>
    </div>
<?php else: ?>

    <?php if ($canAdd): ?>
    <!-- ================= YENİ GRUP EKLEME FORMU ================= -->
    <div class="card">
        <h2>Yeni Grup Ekle</h2>
        <form method="post" action="index.php?page=grup_listesi">
            <input type="hidden" name="action" value="create">
            <label>Grup Adı</label>
            <input type="text" name="group_name" style="width:100%;padding:8px;margin-bottom:12px;" required>

            <label>Açıklama</label>
            <input type="text" name="description" style="width:100%;padding:8px;margin-bottom:16px;">

            <button type="submit" class="btn btn-primary">Grup Ekle</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ================= GRUP LİSTESİ ================= -->
    <div class="card">
        <h2>Grup Listesi</h2>
        <table class="data-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Grup Adı</th>
                <th>Açıklama</th>
                <th>Kullanıcı Sayısı</th>
                <th>Durum</th>
                <?php if ($canUpdate || $canDelete): ?><th style="width:160px;">İşlemler</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $g): ?>
                <tr>
                    <td><?= (int)$g['id'] ?></td>
                    <td><?= e($g['group_name']) ?></td>
                    <td><?= e($g['description'] ?? '-') ?></td>
                    <td><?= (int)$g['user_count'] ?></td>
                    <td>
                        <?php if ($g['is_active']): ?>
                            <span class="badge badge-yes">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-no">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($canUpdate || $canDelete): ?>
                        <td>
                            <?php if ($canUpdate): ?>
                                <a href="index.php?page=grup_listesi&edit=<?= (int)$g['id'] ?>" class="btn btn-primary">Düzenle</a>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                                <a href="index.php?page=grup_listesi&delete=<?= (int)$g['id'] ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Bu grubu silmek istediğinize emin misiniz?');">Sil</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>