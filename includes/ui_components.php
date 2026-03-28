<?php
/**
 * Kalkan Social - HeroUI Style Components Helper
 * Replicates HeroUI (NextUI) design system using vanilla Tailwind CSS.
 */


/**
 * Determines user status based on last_seen timestamp
 * @param string|null $last_seen
 * @return string
 */
function getUserStatus($last_seen) {
    if (!$last_seen) return 'offline';
    $last_seen_time = strtotime($last_seen);
    $diff = time() - $last_seen_time;
    // Online if active in the last 5 minutes (300 seconds)
    return ($diff < 300) ? 'online' : 'offline';
}

/**
 * Returns human-readable "last active" text
 * @param string|null $last_seen
 * @param array $translations - Language translations array ($t)
 * @return string
 */
function getLastActiveText($last_seen, $translations = []) {
    if (!$last_seen) return '';
    
    $last_seen_time = strtotime($last_seen);
    $diff = time() - $last_seen_time;
    
    // If online (within 5 minutes), return online status
    if ($diff < 300) {
        return $translations['online_now'] ?? 'Online now';
    }
    
    // Calculate time difference
    $minutes = floor($diff / 60);
    $hours = floor($diff / 3600);
    $days = floor($diff / 86400);
    
    if ($diff < 60) {
        // Less than a minute
        return $translations['just_now'] ?? 'Just now';
    } elseif ($minutes < 60) {
        // Less than an hour
        $unit = $translations['minutes_ago'] ?? 'minutes ago';
        return $minutes . ' ' . $unit;
    } elseif ($hours < 24) {
        // Less than a day
        $unit = $translations['hours_ago'] ?? 'hours ago';
        return $hours . ' ' . $unit;
    } else {
        // Days
        $unit = $translations['days_ago'] ?? 'days ago';
        return $days . ' ' . $unit;
    }
}

/**
 * Renders a HeroUI-style Avatar
 * @param string $path Image path
 * @param array $options Configuration options:
 *      - size: 'xs' (24px), 'sm' (32px), 'md' (40px), 'lg' (56px), 'xl' (80px), '2xl' (128px)
 *      - isBordered: boolean (adds ring + offset)
 *      - color: 'primary' (pink-500), 'success' (emerald-500), 'danger' (red-500), 'warning' (amber-500), 'default' (slate-200)
 *      - status: 'online', 'busy', 'offline' (adds indicator dot)
 *      - statusPosition: 'top-right', 'bottom-right', 'top-left', 'bottom-left' (default: 'bottom-right')
 *      - last_seen: string (if provided, overrides status by calculating it)
 *      - classes: string (additional custom classes)
 *      - radius: 'full', 'lg', 'md'
 */
function renderAvatar($path, $options = []) {
    $size = $options['size'] ?? 'md';
    $isBordered = $options['isBordered'] ?? false;
    $color = $options['color'] ?? 'default';
    $status = $options['status'] ?? null;
    $statusPosition = $options['statusPosition'] ?? 'bottom-right';
    $radius = $options['radius'] ?? 'full';
    $customClasses = $options['classes'] ?? '';

    // Calculate status from last_seen if provided
    if (isset($options['last_seen'])) {
        $status = getUserStatus($options['last_seen']);
    }

    // Size Mapping
    $sizeMap = [
        'xs' => 'w-6 h-6',
        'sm' => 'w-8 h-8',
        'md' => 'w-10 h-10',
        'lg' => 'w-14 h-14',
        'xl' => 'w-20 h-20',
        '2xl' => 'w-32 h-32'
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];

    // Color Mapping (HeroUI Palette)
    $colorMap = [
        'primary' => 'ring-pink-500',
        'success' => 'ring-emerald-500',
        'danger'  => 'ring-red-500',
        'warning' => 'ring-amber-500',
        'default' => 'ring-slate-300 dark:ring-slate-600',
        
        // Role Colors
        'founder'   => 'ring-red-500',
        'moderator' => 'ring-indigo-500',
        'local_guide' => 'ring-amber-500',
        'local guide' => 'ring-amber-500',
        'guide'       => 'ring-amber-500',
        'captain'   => 'ring-blue-500',
        'taxi'      => 'ring-yellow-500',
        'business'  => 'ring-emerald-500',
        'verified_business' => 'ring-emerald-500',
        'user'      => 'ring-slate-300 dark:ring-slate-600'
    ];
    $colorClass = $colorMap[$color] ?? $colorMap['default'];

    // Radius Mapping
    $radiusMap = [
        'full' => 'rounded-full',
        'lg'   => 'rounded-2xl',
        'md'   => 'rounded-xl'
    ];
    $radiusClass = $radiusMap[$radius] ?? $radiusMap['full'];

    // Border Logic
    $borderClass = $isBordered ? "ring-2 ring-offset-2 ring-offset-white dark:ring-offset-slate-900 $colorClass" : "";

    // Fallback Image
    $finalPath = empty($path) ? 'https://ui-avatars.com/api/?background=random' : $path;
    
    // CDN Integration: Use media_url() if available
    if (!empty($path) && function_exists('media_url')) {
        $finalPath = media_url($path);
    } elseif (!empty($path) && strpos($path, 'http') === false) {
        // Fallback: Fix paths without CDN
        if (strpos($path, '/') !== 0 && strpos($path, '.') !== 0) {
            if (preg_match('/^(uploads|assets|profiles|cats|groups)\//', $path)) {
                $finalPath = '/' . $path;
            }
        }
    }

    // Indicator Dot
    $statusDot = '';
    if ($status) {
        $statusColors = [
            'online' => 'bg-emerald-500',
            'busy'   => 'bg-red-500',
            'offline' => 'bg-slate-400'
        ];
        
        $positionMap = [
            'top-right'    => 'top-0 right-0',
            'bottom-right' => 'bottom-0 right-0',
            'top-left'     => 'top-0 left-0',
            'bottom-left'  => 'bottom-0 left-0'
        ];
        $posClass = $positionMap[$statusPosition] ?? $positionMap['bottom-right'];
        $dotColor = $statusColors[$status] ?? 'bg-slate-400';
        
        // Scale dot size based on avatar size
        $dotSizeMap = [
            'xs' => 'w-2 h-2',
            'sm' => 'w-2.5 h-2.5',
            'md' => 'w-3 h-3',
            'lg' => 'w-4 h-4',
            'xl' => 'w-5 h-5',
            '2xl' => 'w-6 h-6'
        ];
        $dotSize = $dotSizeMap[$size] ?? $dotSizeMap['md'];
        
        $statusDot = '<span class="absolute ' . $posClass . ' block ' . $dotSize . ' rounded-full ring-2 ring-white dark:ring-slate-900 ' . $dotColor . ' z-10"></span>';
    }

    // Generate raw width/height for CLS
    $rawSize = 40; // Default md
    if (isset($sizeMap[$size])) {
        // Handle cases like 'w-10 h-10' or composite classes
        if (preg_match('/w-(\d+)/', $sizeMap[$size], $matches)) {
            $rawSize = intval($matches[1]) * 4;
        }
    }

    // Alt text
    $altText = $options['alt'] ?? 'Avatar';

    // HTML Rendering
    $html = '<span class="relative inline-flex shrink-0 overflow-hidden ' . $sizeClass . ' ' . $radiusClass . ' ' . $borderClass . ' ' . $customClasses . '">';
    $html .= '<img class="w-full h-full object-cover ' . $radiusClass . ' transition-opacity opacity-100" src="' . htmlspecialchars($finalPath) . '" alt="' . htmlspecialchars($altText) . '" loading="lazy" width="' . $rawSize . '" height="' . $rawSize . '">';
    $html .= $statusDot;
    $html .= '</span>';

    return $html;
}

/**
 * Renders a User Role Badge (MyBB Style)
 * @param string $badge_type Badge type (founder, moderator, verified_business, business, verified, user)
 * @return string HTML span element
 */
function getBadgeHTML($badge_type) {
    $badge_type = strtolower(trim($badge_type ?? ''));
    if ($badge_type === '') return '';
    switch($badge_type) {
        case 'founder':
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-red-500 text-white text-xs font-bold shadow-lg shadow-red-500/30 uppercase tracking-wider"><i class="fas fa-crown"></i> Founder</span>';
        case 'moderator':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-500/30 uppercase tracking-wider"><i class="fas fa-shield-alt"></i> Moderator</span>';
        case 'verified_business':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-gradient-to-r from-[#0055FF] to-blue-600 text-white text-xs font-bold shadow-lg shadow-blue-500/30 uppercase tracking-wider"><i class="fas fa-check-circle"></i> VIP Business</span>';
        case 'business':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-700 text-white text-xs font-bold shadow-lg shadow-slate-500/30 uppercase tracking-wider"><i class="fas fa-briefcase"></i> Business</span>';
        case 'verified':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-500/30 uppercase tracking-wider"><i class="fas fa-check"></i> Verified</span>';
        case 'captain':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-cyan-600 text-white text-xs font-bold shadow-lg shadow-cyan-500/30 uppercase tracking-wider"><i class="fas fa-anchor"></i> Captain</span>';
        case 'real_estate':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold shadow-lg shadow-emerald-500/30 uppercase tracking-wider"><i class="fas fa-sign-hanging"></i> Real Estate</span>';
        case 'local_guide':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-500 text-white text-xs font-bold shadow-lg shadow-amber-500/30 uppercase tracking-wider"><i class="fas fa-map-marked-alt"></i> Local Guide</span>';
        case 'photographer':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-purple-600 text-white text-xs font-bold shadow-lg shadow-purple-500/30 uppercase tracking-wider"><i class="fas fa-camera-retro"></i> Photographer</span>';
        case 'place_scout':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider" style="background-color:#0d9488;color:#fff;"><i class="fas fa-binoculars"></i> Place Scout</span>';
        case 'foodie':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-orange-500 text-white text-xs font-bold shadow-lg shadow-orange-500/30 uppercase tracking-wider"><i class="fas fa-utensils"></i> Foodie</span>';
        case 'explorer':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-green-600 text-white text-xs font-bold shadow-lg shadow-green-500/30 uppercase tracking-wider"><i class="fas fa-map-marked-alt"></i> Explorer</span>';
        case 'historian':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-600 text-white text-xs font-bold shadow-lg shadow-amber-500/30 uppercase tracking-wider"><i class="fas fa-book"></i> Historian</span>';
        case 'local':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-violet-600 text-white text-xs font-bold shadow-lg shadow-violet-500/30 uppercase tracking-wider"><i class="fas fa-certificate"></i> Local Expert</span>';
        case 'taxi':
             return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-yellow-400 text-slate-900 text-xs font-bold shadow-lg shadow-yellow-500/30 uppercase tracking-wider"><i class="fas fa-taxi"></i> Taxi</span>';
        case 'user':
        default:
            return '';
    }
}
