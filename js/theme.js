function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.toggle('dark');
    const theme = isDark ? 'dark' : 'light';

    // Set Cookie
    document.cookie = "theme=" + theme + "; path=/; max-age=31536000";

    // Call API and Reload to force server-sync
    fetch('api/update_theme.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'theme=' + theme
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                console.error('Theme update failed:', data.message);
            }
        })
        .catch(err => alert('Bağlantı hatası: ' + err));
}
