<?php
// Use the already-set $lang from lang.php; fallback to 'en' for guests
if (!isset($lang)) {
    $lang = $_SESSION['language'] ?? 'en';
}
$consent_msg = $lang == 'en' 
    ? 'We use cookies for theme, language and to improve your experience.' 
    : 'Tema, dil ve deneyiminizi iyileştirmek için çerezler kullanıyoruz.';
$accept_btn = $lang == 'en' ? 'Accept' : 'Kabul Et';
$privacy_link = $lang == 'en' ? 'Privacy Policy' : 'Gizlilik Politikası';
?>
<div id="cookie-consent" class="hidden fixed bottom-0 left-0 right-0 z-[9999] p-4 md:p-6 shadow-2xl" style="background: rgba(15, 23, 42, 0.98); border-top: 1px solid rgba(148, 163, 184, 0.3); backdrop-filter: blur(12px);">
    <div class="container mx-auto max-w-4xl flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-sm font-medium text-center md:text-left" style="color: #e2e8f0;">
            🍪 <?php echo $consent_msg; ?> 
            <a href="privacy" style="color: #38bdf8; font-weight: 700; text-decoration: underline;"><?php echo $privacy_link; ?></a>
        </p>
        <button type="button" onclick="acceptCookies()" class="shrink-0 font-bold rounded-xl transition-colors whitespace-nowrap cursor-pointer" style="padding: 10px 24px; background: #0055FF; color: #ffffff; border: none;">
            <?php echo $accept_btn; ?>
        </button>
    </div>
</div>
<script>
(function() {
    if (!document.cookie.includes('cookie_consent=1')) {
        document.getElementById('cookie-consent').classList.remove('hidden');
    }
})();
function acceptCookies() {
    document.cookie = 'cookie_consent=1;path=/;max-age=31536000;SameSite=Lax';
    document.getElementById('cookie-consent').classList.add('hidden');
}
</script>
