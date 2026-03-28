<?php
/**
 * Dynamic SEO & Open Graph Generator
 * Included in <head> section of pages
 */
require_once __DIR__ . '/site_settings.php';

// 1. System Configuration
$base_domain = app_url();
$current_url = $base_domain . $_SERVER['REQUEST_URI'];
$site_name   = site_name();

// Ensure PDO exists 
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

// 2. Default Meta Values
$meta = [
    'title'       => site_title('tr'),
    'description' => 'Kalkan\'ın dijital kalbi. Etkinlikler, nöbetçi eczane, Kalkan rehberi, kayıp hayvan, marketplace. Kalkan topluluğuna katıl.',
    'image'       => $base_domain . '/assets/og.png',
    'type'        => 'website',
    'url'         => $current_url
];

// 3. Dynamic Logic
try {
    // Scenario: Index/Home (Kalkan ana sayfa)
    $script_name = basename($_SERVER['PHP_SELF'] ?? 'index.php');
    if (in_array($script_name, ['index.php', 'index'])) {
        $meta['title'] = site_title('tr');
        $meta['description'] = 'Kalkan\'ın dijital kalbi. Etkinlikler, nöbetçi eczane, Kalkan rehberi, kayıp hayvan, marketplace. Kalkan topluluğuna katıl.';
    }
    elseif (($script_name === 'feed.php' && isset($_GET['post_id'])) || ($script_name === 'post_detail.php' && isset($_GET['id']))) {
        $pid = $_GET['post_id'] ?? $_GET['id'];
        
        $stmt = $pdo->prepare("SELECT content, image, media_type, image_url, media_url FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$pid]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            // Title
            $clean_content = trim(strip_tags($post['content']));
            // If content is empty/media-only, use generic
            $title_text = !empty($clean_content) ? mb_substr($clean_content, 0, 50, 'UTF-8') . '...' : 'New Post on Kalkan Social';
            $meta['title'] = $title_text . ' - ' . $site_name;

            // Description
            $meta['description'] = !empty($clean_content) ? mb_substr($clean_content, 0, 160, 'UTF-8') : 'View this post on Kalkan Social';
            
            // Image
            // image_url from new multi-image system or image column (legacy) or media_url
            // Prioritize: image column (if absolute/relative handled manually)
            // Note: DB stores 'uploads/filename.webp' usually.
            
            $img_path = '';
            if (!empty($post['image'])) $img_path = $post['image']; // Legacy column often stores full path or relative
            elseif (!empty($post['image_url'])) $img_path = $post['image_url'];
            elseif (!empty($post['media_url']) && $post['media_type'] === 'image') $img_path = $post['media_url'];

            if ($img_path) {
                // Formatting URL
                if (filter_var($img_path, FILTER_VALIDATE_URL)) {
                    $meta['image'] = $img_path; 
                } else {
                    $meta['image'] = $base_domain . '/' . ltrim($img_path, '/');
                }
            }
            
            $meta['type'] = 'article';
        }
    } 
    // Scenario B: Profile
    elseif ($script_name === 'profile.php') {
        $user_data = null;
        
        if (isset($_GET['username'])) {
            $stmt = $pdo->prepare("SELECT username, bio, avatar, full_name FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$_GET['username']]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (isset($_GET['uid']) || isset($_GET['id'])) {
            $u_id = $_GET['uid'] ?? $_GET['id'];
            $stmt = $pdo->prepare("SELECT username, bio, avatar, full_name FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$u_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($user_data) {
            $display_name = !empty($user_data['full_name']) ? $user_data['full_name'] : $user_data['username'];
            $meta['title'] = $display_name . ' (@' . $user_data['username'] . ') - ' . $site_name;
            
            if (!empty($user_data['bio'])) {
                $meta['description'] = mb_substr(trim(strip_tags($user_data['bio'])), 0, 160, 'UTF-8');
            } else {
                // Generic description if bio is empty
                $meta['description'] = 'Check out ' . $display_name . '\'s profile and updates on ' . $site_name . '.';
            }

            if (!empty($user_data['avatar'])) {
                if (filter_var($user_data['avatar'], FILTER_VALIDATE_URL)) {
                    $meta['image'] = $user_data['avatar'];
                } else {
                    $meta['image'] = $base_domain . '/' . ltrim($user_data['avatar'], '/');
                }
            }
            
            $meta['type'] = 'profile';
        }
    }
    // Scenario C: Guidebook Listing (SEO: Kalkan guide, Kalkan rehber)
    elseif ($script_name === 'guidebook.php') {
        $cat = $_GET['cat'] ?? '';
        if ($cat) {
            $meta['title'] = $cat . ' - Kalkan Guide | Kalkan Rehber | ' . $site_name;
            $meta['description'] = 'Kalkan ' . $cat . ' rehberi. Yerel uzmanlardan Kalkan ipuçları ve önerileri. Kalkan guide, Kalkan rehber - en iyi Kalkan rehberlik içerikleri.';
        } else {
            $meta['title'] = 'Kalkan Guide | Kalkan Rehber - Yerel Uzman Tavsiyeleri | Kalkan Social';
            $meta['description'] = 'Kalkan rehberi ve seyahat ipuçları. Kalkan guide - restoranlar, plajlar, aktiviteler. Yerel uzmanlardan Kalkan\'ın en iyi adresleri.';
        }
    }
    // Scenario D: Guidebook Detail
    elseif ($script_name === 'guidebook_detail.php' && isset($_GET['slug'])) {
        $stmt = $pdo->prepare("
            SELECT g.*, u.full_name, u.username, u.avatar 
            FROM guidebooks g 
            JOIN users u ON g.user_id = u.id 
            WHERE g.slug = ? AND g.status = 'published' 
            LIMIT 1
        ");
        $stmt->execute([$_GET['slug']]);
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($guide) {
            $meta['title'] = htmlspecialchars($guide['title']) . ' - Kalkan Guidehub';
            $meta['description'] = mb_substr(strip_tags($guide['content']), 0, 160, 'UTF-8') . '...';
            $meta['url'] = $base_domain . '/guidebook_detail.php?slug=' . $guide['slug'];
            $meta['type'] = 'article';
            
            if (!empty($guide['cover_image'])) {
                if (filter_var($guide['cover_image'], FILTER_VALIDATE_URL)) {
                    $meta['image'] = $guide['cover_image'];
                } else {
                    $meta['image'] = $base_domain . '/' . ltrim($guide['cover_image'], '/');
                }
            }

            // JSON-LD for BlogPosting
            $json_ld = [
                "@context" => "https://schema.org",
                "@type" => "BlogPosting",
                "headline" => $guide['title'],
                "image" => $meta['image'],
                "author" => [
                    "@type" => "Person",
                    "name" => $guide['full_name'],
                    "url" => $base_domain . "/profile.php?username=" . $guide['username']
                ],
                "publisher" => [
                    "@type" => "Organization",
                    "name" => $site_name,
                    "logo" => [
                        "@type" => "ImageObject",
                        "url" => $base_domain . "/assets/logo_header.webp"
                    ]
                ],
                "datePublished" => date('c', strtotime($guide['created_at'])),
                "description" => $meta['description'],
                "articleBody" => strip_tags($guide['content'])
            ];
            $meta['json_ld'] = json_encode($json_ld);
        }
    }
    // Scenario E: News Detail
    elseif ($script_name === 'news.php' && isset($_GET['id'])) {
         // Logic for news if needed
    }
    // ─── Service Pages (SEO: Kalkan nöbetçi eczane, Kalkan guide, Kalkan lost pets) ───
    elseif (in_array($script_name, ['duty_pharmacy.php', 'duty_pharmacy'])) {
        $meta['title'] = 'Kalkan Nöbetçi Eczane | Kaş Nöbetçi Eczane - Antalya | Kalkan Social';
        $meta['description'] = 'Kalkan ve Kaş bölgesi nöbetçi eczane listesi. 24 saat açık eczaneler, adres, telefon ve harita. Kalkan Social ile güncel nöbetçi eczane bilgisi.';
        $meta['json_ld'] = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Kalkan Nöbetçi Eczane - Kaş Bölgesi',
            'description' => $meta['description'],
            'url' => $meta['url'],
            'publisher' => ['@type' => 'Organization', 'name' => $site_name],
            'mainEntity' => ['@type' => 'Service', 'name' => 'Nöbetçi Eczane Kalkan Kaş', 'serviceType' => 'Pharmacy']
        ]);
    }
    elseif (in_array($script_name, ['pati_safe.php', 'pati_safe'])) {
        $meta['title'] = 'Kalkan Lost Pets | Kayıp Hayvan İlanları - Pati Safe | Kalkan Social';
        $meta['description'] = 'Kalkan kayıp hayvan ilanları. Kayıp kedi, köpek ve diğer evcil hayvanları bulmak için Pati Safe. Kalkan Social topluluğu ile kayıp dostlarınızı bulun.';
        $meta['json_ld'] = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Kalkan Lost Pets - Pati Safe Kayıp Hayvan',
            'description' => $meta['description'],
            'url' => $meta['url'],
            'publisher' => ['@type' => 'Organization', 'name' => $site_name]
        ]);
    }
    elseif (in_array($script_name, ['lost_found.php', 'lost_found'])) {
        $meta['title'] = 'Kalkan Kayıp Eşya | Lost & Found - Anahtar, Cüzdan, Telefon | Kalkan Social';
        $meta['description'] = 'Kalkan kayıp eşya ilanları. Anahtar, cüzdan, telefon, çanta kaybettiniz mi? Bulduğunuz eşyaları paylaşın. Kalkan Social Lost & Found.';
        $meta['json_ld'] = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Kalkan Kayıp Eşya - Lost & Found',
            'description' => $meta['description'],
            'url' => $meta['url'],
            'publisher' => ['@type' => 'Organization', 'name' => $site_name]
        ]);
    }
    elseif (in_array($script_name, ['services.php', 'services'])) {
        $meta['title'] = 'Kalkan Hizmetler | Nöbetçi Eczane, Rehber, Kayıp Hayvan, Etkinlikler | Kalkan Social';
        $meta['description'] = 'Kalkan\'da ihtiyacınız olan tüm hizmetler: nöbetçi eczane, Kalkan rehberi, kayıp hayvan ilanları, etkinlikler, iş ilanları, marketplace. Kalkan Social hizmet merkezi.';
    }
    elseif (in_array($script_name, ['first_aid.php', 'first_aid'])) {
        $meta['title'] = 'Kalkan İlk Yardım Rehberi | First Aid Guide - Kalkan Social';
        $meta['description'] = 'Kalkan ilk yardım rehberi. Acil durumlarda ne yapılır? İlk yardım adımları, acil telefon numaraları. Kalkan ve Kaş bölgesi için pratik bilgiler.';
    }
    elseif (in_array($script_name, ['jobs.php', 'jobs'])) {
        $meta['title'] = 'Kalkan İş İlanları | Job Board - Garson, Aşçı, Resepsiyon | Kalkan Social';
        $meta['description'] = 'Kalkan iş ilanları. Garson, aşçı, barmen, resepsiyon, oda temizlik, tekne mürettebatı. Sezonluk ve tam zamanlı iş fırsatları. Kalkan Job Board.';
    }

} catch (Exception $e) {
    // Silent fail
}

function safe_meta_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
<!-- SEO & Open Graph Tags -->
<title><?php echo safe_meta_attr($meta['title']); ?></title>
<meta name="description" content="<?php echo safe_meta_attr($meta['description']); ?>">
<link rel="canonical" href="<?php echo safe_meta_attr($meta['url']); ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="<?php echo safe_meta_attr($meta['type']); ?>">
<meta property="og:url" content="<?php echo safe_meta_attr($meta['url']); ?>">
<meta property="og:title" content="<?php echo safe_meta_attr($meta['title']); ?>">
<meta property="og:description" content="<?php echo safe_meta_attr($meta['description']); ?>">
<meta property="og:image" content="<?php echo safe_meta_attr($meta['image']); ?>">
<meta property="og:site_name" content="<?php echo safe_meta_attr($site_name); ?>">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo safe_meta_attr($meta['url']); ?>">
<meta property="twitter:title" content="<?php echo safe_meta_attr($meta['title']); ?>">
<meta property="twitter:description" content="<?php echo safe_meta_attr($meta['description']); ?>">
<meta property="twitter:image" content="<?php echo safe_meta_attr($meta['image']); ?>">

<?php if (isset($meta['json_ld'])): ?>
<!-- JSON-LD Structured Data -->
<script type="application/ld+json">
<?php echo $meta['json_ld']; ?>
</script>
<?php endif; ?>
