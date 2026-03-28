<?php
/**
 * Opening hours helper - "Şu an açık mı?" kontrolü
 */
function is_business_open_now($opening_hours_json) {
    if (empty($opening_hours_json)) return null; // Bilinmiyor
    $hours = is_string($opening_hours_json) ? json_decode($opening_hours_json, true) : $opening_hours_json;
    if (!$hours) return null;
    if (!empty($hours['is_24_7'])) return true;
    $today = strtolower(date('l')); // monday, tuesday, ...
    $today_hours = $hours[$today] ?? null;
    if (!$today_hours || !empty($today_hours['closed'])) return false;
    $now = date('H:i');
    return ($now >= ($today_hours['open'] ?? '00:00')) && ($now <= ($today_hours['close'] ?? '23:59'));
}
