<?php
/**
 * index.php üzerinden dahil edilir.
 * Hazır değişkenler: $pdo, $canUpdate (bu sayfanın kendi güncelleme yetkisi)
 */

$successMsg = '';
$errorMsg   = '';

// ---- Tüm gruplar (dropdown için) ----
$allGroups = $pdo->query("SELECT id, group_name FROM groups_table ORDER BY group_name")->fetchAll();

if (empty($allGroups)) {
    echo '<div class="card"><h2>Yetki Yönetimi</h2><p>Önce en az bir grup oluşturmalısınız.</p></div>';
    return;
}

// ---- Seçili grup (formdan ya da varsayılan ilk grup) ----
$selectedGroupId = (int)($_POST['group_id'] ?? $_GET['group_id'] ?? $allGroups[0]['id']);

// ---- Tüm sayfalar, modüllere göre gruplu ----
$allPages = $pdo->query(
    "SELECT p.id, p.page_title, p.page_key, m.module_name, m.sort_order AS module_sort, p.sort_order AS page_sort
     FROM pages p
     INNER JOIN modules m ON m.id = p.module_id
     WHERE p.is_active = 1
     ORDER BY m.sort_order ASC, p.sort_order ASC"
)->fetchAll();

// ---- KAYDETME ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_permissions' && $canUpdate) {
    $pdo->beginTransaction();
    try {
        foreach ($allPages as $pg) {
            $pid       = (int)$pg['id'];
            $canView   = isset($_POST['can_view'][$pid])   ? 1 : 0;
            $canAddP   = isset($_POST['can_add'][$pid])    ? 1 : 0;
            $canUpd    = isset($_POST['can_update'][$pid]) ? 1 : 0;
            $canDel    = isset($_POST['can_delete'][$pid]) ? 1 : 0;

            $stmt = $pdo->prepare(
                "INSERT INTO group_page_permissions (group_id, page_id, can_view, can_add, can_update, can_delete)
                 VALUES (:g, :p, :v, :a, :u, :d)
                 ON DUPLICATE KEY UPDATE
                    can_view = VALUES(can_view),
                    can_add = VALUES(can_add),
                    can_update = VALUES(can_update),
                    can_delete = VALUES(can_delete)"
            );
            $stmt->execute([
                'g' => $selectedGroupId, 'p' => $pid,
                'v' => $canView, 'a' => $canAddP, 'u' => $canUpd, 'd' => $canDel,
            ]);
        }
        $pdo->commit();
        $successMsg = 'Yetkiler başarıyla kaydedildi.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMsg = 'Yetkiler kaydedilirken bir hata oluştu.';
    }
}

// ---- Seçili grubun mevcut yetkilerini çek (page_id => permissions) ----
$stmt = $pdo->prepare("SELECT * FROM group_page_permissions WHERE group_id = :g");
$stmt->execute(['g' => $selectedGroupId]);
$existingPerms = [];
foreach ($stmt->fetchAll() as $row) {
    $existingPerms[(int)$row['page_id']] = $row;
}

// Sayfaları modüle göre grupla (görüntü için)
$grouped = [];
foreach ($allPages as $pg) {
    $grouped[$pg['module_name']][] = $pg;
}
?>

<?php if ($successMsg): ?>
    <div class="card" style="background:#dcfce7; border-color:#86efac; margin-bottom:16px;">
        <?= e($successMsg) ?>
    </div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="login-error" style="margin-bottom:16px;"><?= e($errorMsg) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Yetki Yönetimi</h2>

    <form method="get" action="index.php" style="margin-bottom:20px;">
        <input type="hidden" name="page" value="yetki_yonetimi">
        <label>Yetkilerini düzenlemek istediğiniz grubu seçin:</label>
        <select name="group_id" onchange="this.form.submit()" style="width:100%; max-width:320px; padding:8px; margin-top:6px;">
            <?php foreach ($allGroups as $g): ?>
                <option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === $selectedGroupId ? 'selected' : '' ?>>
                    <?= e($g['group_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($canUpdate): ?>
    <form method="post" action="index.php?page=yetki_yonetimi">
        <input type="hidden" name="action" value="save_permissions">
        <input type="hidden" name="group_id" value="<?= $selectedGroupId ?>">

        <?php foreach ($grouped as $moduleName => $pages): ?>
            <h3 style="margin-top:24px; margin-bottom:8px; color:#374151;"><?= e($moduleName) ?></h3>
            <table class="data-table" style="margin-bottom:12px;">
                <thead>
                <tr>
                    <th>Sayfa</th>
                    <th style="width:90px; text-align:center;">Görme</th>
                    <th style="width:90px; text-align:center;">Ekleme</th>
                    <th style="width:100px; text-align:center;">Güncelleme</th>
                    <th style="width:90px; text-align:center;">Silme</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $pg):
                    $pid  = (int)$pg['id'];
                    $perm = $existingPerms[$pid] ?? ['can_view' => 0, 'can_add' => 0, 'can_update' => 0, 'can_delete' => 0];
                ?>
                    <tr>
                        <td><?= e($pg['page_title']) ?></td>
                        <td style="text-align:center;">
                            <input type="checkbox" name="can_view[<?= $pid ?>]" value="1" <?= $perm['can_view'] ? 'checked' : '' ?>>
                        </td>
                        <td style="text-align:center;">
                            <input type="checkbox" name="can_add[<?= $pid ?>]" value="1" <?= $perm['can_add'] ? 'checked' : '' ?>>
                        </td>
                        <td style="text-align:center;">
                            <input type="checkbox" name="can_update[<?= $pid ?>]" value="1" <?= $perm['can_update'] ? 'checked' : '' ?>>
                        </td>
                        <td style="text-align:center;">
                            <input type="checkbox" name="can_delete[<?= $pid ?>]" value="1" <?= $perm['can_delete'] ? 'checked' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-success" style="margin-top:12px;">Yetkileri Kaydet</button>
    </form>
    <?php else: ?>
        <p style="color:#6b7280;">Yetkileri görüntüleyebiliyorsunuz ancak değiştirme izniniz yok.</p>
    <?php endif; ?>
</div>