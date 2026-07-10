function panelSwitchLang(lang) {
    document.cookie = 'panel_lang=' + lang + ';path=/;max-age=' + (60 * 60 * 24 * 365);
    location.reload();
}

function panelToggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'light';
    var next = current === 'light' ? 'dark' : 'light';
    document.cookie = 'panel_theme=' + next + ';path=/;max-age=' + (60 * 60 * 24 * 365);
    location.reload();
}
