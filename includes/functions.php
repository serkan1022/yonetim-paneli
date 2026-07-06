<?php
/**
 * Genel yardımcı fonksiyonlar
 * - Sayfa bilgisini page_key'e göre veritabanından çeker
 * - Kullanıcının bir sayfada yetkisi olup olmadığını kontrol eder
 * - Kullanıcının görebileceği modül/menü listesini hazırlar
 */

/**
 * URL'den gelen page_key değerine göre sayfa kaydını getirir.
 */
function getPageByKey(PDO $pdo, string $pageKey): ?array
{
    $stmt = $pdo->prepare(
        "SELECT p.*, m.module_name, m.module_key
         FROM pages p
         INNER JOIN modules m ON m.id = p.module_id
         WHERE p.page_key = :page_key AND p.is_active = 1
         LIMIT 1"
    );
    $stmt->execute(['page_key' => $pageKey]);
    $page = $stmt->fetch();
    return $page ?: null;
}

/**
 * Belirli bir grup, belirli bir sayfada istenen yetkiye (view/add/update/delete) sahip mi?
 * $action: 'view' | 'add' | 'update' | 'delete'
 */
function groupHasPermission(PDO $pdo, int $groupId, int $pageId, string $action): bool
{
    $column = match ($action) {
        'view'   => 'can_view',
        'add'    => 'can_add',
        'update' => 'can_update',
        'delete' => 'can_delete',
        default  => null,
    };

    if ($column === null) {
        return false;
    }

    $stmt = $pdo->prepare(
        "SELECT {$column} FROM group_page_permissions
         WHERE group_id = :group_id AND page_id = :page_id
         LIMIT 1"
    );
    $stmt->execute(['group_id' => $groupId, 'page_id' => $pageId]);
    $row = $stmt->fetch();

    return $row && (int)$row[$column] === 1;
}

/**
 * Giriş yapan kullanıcının grubuna göre, menüde görünmesi gereken
 * modülleri ve her modüle ait menüleri (görme yetkisi olan sayfalara bağlı) döner.
 *
 * Kural: Bir modülün menüsü sadece o modüle ait EN AZ 1 sayfada
 * "can_view = 1" yetkisi varsa görünür.
 */
function getMenuForGroup(PDO $pdo, int $groupId): array
{
    $sql = "
        SELECT
            mo.id            AS module_id,
            mo.module_name,
            mo.module_key,
            mo.icon          AS module_icon,
            mo.sort_order    AS module_sort,
            me.id            AS menu_id,
            me.menu_title,
            me.icon          AS menu_icon,
            me.sort_order    AS menu_sort,
            pg.page_key,
            pg.page_title
        FROM group_page_permissions gpp
        INNER JOIN pages pg   ON pg.id = gpp.page_id
        INNER JOIN modules mo ON mo.id = pg.module_id
        INNER JOIN menus me   ON me.page_id = pg.id
        WHERE gpp.group_id = :group_id
          AND gpp.can_view = 1
          AND pg.is_active = 1
          AND mo.is_active = 1
          AND me.is_active = 1
        ORDER BY mo.sort_order ASC, me.sort_order ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['group_id' => $groupId]);
    $rows = $stmt->fetchAll();

    // Modüllere göre grupla
    $menu = [];
    foreach ($rows as $row) {
        $modKey = $row['module_key'];
        if (!isset($menu[$modKey])) {
            $menu[$modKey] = [
                'module_id'   => $row['module_id'],
                'module_name' => $row['module_name'],
                'module_icon' => $row['module_icon'],
                'items'       => [],
            ];
        }
        $menu[$modKey]['items'][] = [
            'menu_title' => $row['menu_title'],
            'menu_icon'  => $row['menu_icon'],
            'page_key'   => $row['page_key'],
        ];
    }

    return $menu;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
