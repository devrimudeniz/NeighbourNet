<?php
/**
 * Heroicons to Font Awesome Adapter
 * Maps abstract icon names used in the project to Font Awesome 6 classes.
 * Returns <i class="fas fa-..."></i> tags.
 */
function heroicon($name, $classes = "") {
    // Map abstract names to Font Awesome classes
    $icons = [
        'home' => 'fa-home',
        'feed' => 'fa-stream', // or fa-rss
        'plus' => 'fa-plus',
        'messages' => 'fa-comment-dots',
        'profile' => 'fa-user',
        'calendar' => 'fa-calendar-alt',
        'users' => 'fa-users',
        'group' => 'fa-user-friends',
        'paw' => 'fa-paw',
        'pharmacy' => 'fa-prescription-bottle-medical', // or fa-pills (pro) or fa-clinic-medical
        'pills' => 'fa-pills',
        'marketplace' => 'fa-shopping-bag',
        'ship' => 'fa-ship',
        'store' => 'fa-store',
        'briefcase' => 'fa-briefcase',
        'transportation' => 'fa-bus',
        'property_hub' => 'fa-city', // Custom?
        'real_estate' => 'fa-sign-hanging', // or fa-house-user
        'admin' => 'fa-shield-alt',
        'pound' => 'fa-sterling-sign', // fa-pound-sign
        'sparkles' => 'fa-wand-magic-sparkles',
        'bolt' => 'fa-bolt',
        'moon' => 'fa-moon',
        'sun' => 'fa-sun',
        'bell' => 'fa-bell',
        'chevron_up' => 'fa-chevron-up',
        'chevron_down' => 'fa-chevron-down',
        'bars' => 'fa-bars',
        'times' => 'fa-times',
        'paper_plane' => 'fa-paper-plane',
        'image' => 'fa-image',
        'video' => 'fa-video',
        'link' => 'fa-link',
        'smile' => 'fa-smile',
        'location' => 'fa-map-marker-alt',
        'trash' => 'fa-trash',
        'edit' => 'fa-pen', // fa-edit is alias
        'ellipsis' => 'fa-ellipsis-h',
        'clock' => 'fa-clock',
        'fire' => 'fa-fire',
        'check' => 'fa-check',
        'logout' => 'fa-sign-out-alt',
        'comment_dots' => 'fa-comment-dots',
        'search' => 'fa-search',
        'chevron_right' => 'fa-chevron-right',
        'spinner' => 'fa-spinner',
        'lock' => 'fa-lock',
        'shield' => 'fa-shield-alt',
        'gavel' => 'fa-gavel',
        'anchor' => 'fa-anchor',
        'taxi' => 'fa-taxi',
        'foodie' => 'fa-utensils',
        'place_scout' => 'fa-binoculars',
        'camera' => 'fa-camera',
        'library' => 'fa-book', // or fa-university
        'certificate' => 'fa-certificate',
        'user' => 'fa-user',
        'user_plus' => 'fa-user-plus',
        'user_minus' => 'fa-user-minus',
        'user_check' => 'fa-user-check',
        'arrow_left' => 'fa-arrow-left',
        'arrow_right' => 'fa-arrow-right',
        'fingerprint' => 'fa-fingerprint',
        'heart' => 'fa-heart',
        'share' => 'fa-share',
        'phone' => 'fa-phone',
        'envelope' => 'fa-envelope',
        'globe' => 'fa-globe',
        'identification' => 'fa-id-card',
        'cake' => 'fa-birthday-cake',
        'star' => 'fa-star',
        'exclamation_circle' => 'fa-exclamation-circle',
        'login' => 'fa-sign-in-alt',
        'pencil_square' => 'fa-pen-to-square', // or fa-edit
        'paper_clip' => 'fa-paperclip',
        'chat_bubble_left_right' => 'fa-comments',
        'x_circle' => 'fa-times-circle',
        'ban' => 'fa-ban',
        'document' => 'fa-file-alt',
        'adjustments' => 'fa-sliders-h',
        'ellipsis_vertical' => 'fa-ellipsis-v',
        'cube' => 'fa-cube', // or fa-box
        'check_badge' => 'fa-check-double', // or fa-certificate?
        'information_circle' => 'fa-info-circle',
        'arrow_up_tray' => 'fa-upload',
        'check_circle' => 'fa-check-circle',
        'key' => 'fa-key',
        
        // Mute/Block/Extra
        'speaker_x_mark' => 'fa-volume-mute', // FA5/FA6 compatible
        'speaker_wave' => 'fa-volume-up',   // FA5/FA6 compatible
        'no_symbol' => 'fa-ban',
        'ellipsis_horizontal' => 'fa-ellipsis-h',
        'ellipsis_vertical' => 'fa-ellipsis-v',
        
        // Semantic Corrections
        'bed' => 'fa-bed',
        'bath' => 'fa-bath',
        'ruler' => 'fa-ruler-combined',
        'water' => 'fa-water',
        'swimmer' => 'fa-swimmer',
        'wifi' => 'fa-wifi',
        
        // Trail/Map Icons
        'map' => 'fa-map-marked-alt',
        'hiking' => 'fa-hiking',
        'route' => 'fa-route',
        'mountain' => 'fa-mountain',
        'compass' => 'fa-compass',
        
        // Settings
        'cog' => 'fa-cog',
        'settings' => 'fa-cog',
        'cog_6_tooth' => 'fa-cog',
        'swatch' => 'fa-palette',
        'bookmark' => 'fa-bookmark',
        'document_text' => 'fa-file-lines',
        'car' => 'fa-car',
        'hospital' => 'fa-hospital',
        'language' => 'fa-language',
        'cocktail' => 'fa-cocktail',
        'music' => 'fa-music',
        'guitar' => 'fa-guitar',
        'play_circle' => 'fa-play-circle',
        'youtube' => 'fa-youtube',
        'grid' => 'fa-th-large',
        'x_mark' => 'fa-times',
        'magnifying_glass' => 'fa-search',
        'chat_bubble_left' => 'fa-comment'
    ];

    // Fallback or explicit mapping logic
    $fa_class = isset($icons[$name]) ? $icons[$name] : 'fa-question-circle';

    // Construct the <i> tag
    // Font Awesome 6 uses 'fa-solid' as standard for solid icons
    return '<i class="fa-solid ' . $fa_class . ' ' . $classes . '"></i>';
}
