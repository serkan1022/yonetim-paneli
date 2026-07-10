<?php
/**
 * Basit dil (i18n) sistemi.
 *
 * KAPSAM: Sadece sabit arayüz metinleri (üst menü, giriş ekranı, genel butonlar,
 * ana sayfa karşılama metni, 403/404 mesajları). Modül sayfalarının içeriği
 * (örn. "Stok Listesi", "Fatura Ekle" gibi ekranlardaki metinler ve veritabanından
 * gelen sayfa/menü isimleri) bu kapsamın dışındadır, Türkçe kalmaya devam eder.
 *
 * Tercih tarayıcıda çerez (cookie) olarak saklanır, veritabanına yazılmaz.
 */

function currentLang(): string
{
    $lang = $_COOKIE['panel_lang'] ?? 'tr';
    return in_array($lang, ['tr', 'en'], true) ? $lang : 'tr';
}

function currentTheme(): string
{
    $theme = $_COOKIE['panel_theme'] ?? 'light';
    return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
}

$GLOBALS['__PANEL_STRINGS'] = [
    'tr' => [
        'brand_title'          => 'Yönetim Paneli',
        'logout'               => 'Çıkış Yap',
        'welcome'              => 'Hoş geldin',
        'your_group'           => 'Grubun',
        'welcome_hint'         => 'Sol menüden, yetkin olan modül ve sayfalara erişebilirsin.',
        'home'                 => 'Ana Sayfa',
        'login_title'          => 'Yönetim Paneli Girişi',
        'username'             => 'Kullanıcı Adı',
        'password'             => 'Şifre',
        'login_button'         => 'Giriş Yap',
        'login_error_empty'    => 'Kullanıcı adı ve şifre zorunludur.',
        'login_error_wrong'    => 'Kullanıcı adı veya şifre hatalı.',
        'not_found_title'      => '404 - Sayfa Bulunamadı',
        'not_found_body'       => 'Aradığınız sayfa tanımlı değil.',
        'access_denied_title'  => 'Erişim Engellendi',
        'access_denied_body'   => 'Bu sayfayı görüntülemek için yetkiniz bulunmuyor. Gerekli izinler için sistem yöneticinizle iletişime geçin.',
        'back_home'            => 'Ana Sayfaya Dön',
        'theme_light_tooltip'  => 'Açık temaya geç',
        'theme_dark_tooltip'   => 'Koyu temaya geç',
    ],
    'en' => [
        'brand_title'          => 'Management Panel',
        'logout'               => 'Log Out',
        'welcome'              => 'Welcome',
        'your_group'           => 'Your Group',
        'welcome_hint'         => 'You can access the modules and pages you have permission for from the left menu.',
        'home'                 => 'Home',
        'login_title'          => 'Management Panel Login',
        'username'             => 'Username',
        'password'             => 'Password',
        'login_button'         => 'Log In',
        'login_error_empty'    => 'Username and password are required.',
        'login_error_wrong'    => 'Incorrect username or password.',
        'not_found_title'      => '404 - Page Not Found',
        'not_found_body'       => 'The page you are looking for does not exist.',
        'access_denied_title'  => 'Access Denied',
        'access_denied_body'   => 'You do not have permission to view this page. Contact your system administrator for access.',
        'back_home'            => 'Back to Home',
        'theme_light_tooltip'  => 'Switch to light theme',
        'theme_dark_tooltip'   => 'Switch to dark theme',
    ],
];

function t(string $key): string
{
    $lang = currentLang();
    return $GLOBALS['__PANEL_STRINGS'][$lang][$key] ?? $key;
}
