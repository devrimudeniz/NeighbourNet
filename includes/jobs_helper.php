<?php
/**
 * Job categories and types - shared across jobs.php, post_job.php, job_detail.php
 */

function getJobCategories($lang = 'en') {
    return [
        'waiter'        => ['en' => 'Waiter', 'tr' => 'Garson', 'icon' => '🍽️', 'color' => 'orange'],
        'chef'          => ['en' => 'Chef', 'tr' => 'Aşçı', 'icon' => '👨‍🍳', 'color' => 'red'],
        'bartender'     => ['en' => 'Bartender', 'tr' => 'Barmen', 'icon' => '🍹', 'color' => 'violet'],
        'receptionist'  => ['en' => 'Receptionist', 'tr' => 'Resepsiyon', 'icon' => '📞', 'color' => 'blue'],
        'housekeeping'  => ['en' => 'Housekeeping', 'tr' => 'Oda Temizlik', 'icon' => '🛏️', 'color' => 'teal'],
        'kitchen_staff' => ['en' => 'Kitchen Staff', 'tr' => 'Mutfak Personeli', 'icon' => '🥗', 'color' => 'emerald'],
        'manager'       => ['en' => 'Manager', 'tr' => 'Yönetici', 'icon' => '👔', 'color' => 'indigo'],
        'boat_crew'     => ['en' => 'Boat Crew', 'tr' => 'Tekne Mürettebatı', 'icon' => '⛵', 'color' => 'cyan'],
        'other'         => ['en' => 'Other', 'tr' => 'Diğer', 'icon' => '📋', 'color' => 'slate'],
    ];
}

function getJobTypes($lang = 'en') {
    return [
        'seasonal'  => ['en' => 'Seasonal', 'tr' => 'Sezonluk', 'icon' => '☀️'],
        'full_time' => ['en' => 'Full Time', 'tr' => 'Tam Zamanlı', 'icon' => '💼'],
        'part_time' => ['en' => 'Part Time', 'tr' => 'Yarı Zamanlı', 'icon' => '⏰'],
    ];
}

function getCategoryLabel($cat, $lang = 'en') {
    $cats = getJobCategories($lang);
    $c = $cats[$cat] ?? $cats['other'];
    return $c[$lang];
}

function getTypeLabel($type, $lang = 'en') {
    $types = getJobTypes($lang);
    $t = $types[$type] ?? $types['seasonal'];
    return $t[$lang];
}
