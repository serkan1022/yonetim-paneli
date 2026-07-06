<?php
/**
 * Oturum yönetimi ve erişim kontrolü
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kullanıcı giriş yapmış mı kontrol eder, yapmamışsa login sayfasına yönlendirir.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * O anki kullanıcının grup id'sini döner.
 */
function currentGroupId(): int
{
    return (int)($_SESSION['group_id'] ?? 0);
}

function currentUsername(): string
{
    return $_SESSION['username'] ?? '';
}

function currentFullName(): string
{
    return $_SESSION['full_name'] ?? '';
}

/**
 * Belirli bir sayfa üzerinde, o anki kullanıcının istenen yetkisi yoksa
 * erişimi keser ve 403 mesajı gösterir.
 */
function requirePermission(PDO $pdo, int $pageId, string $action = 'view'): void
{
    $groupId = currentGroupId();
    if (!groupHasPermission($pdo, $groupId, $pageId, $action)) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}
