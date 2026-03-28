<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/cdn_helper.php';
require_once 'includes/lang.php';
require_once 'includes/icon_helper.php';
require_once 'includes/ui_components.php';

// $lang is already set by lang.php

// Check if user has business badge
if (!isset($_SESSION['user_id']) || !isset($_SESSION['badge']) || 
    !in_array($_SESSION['badge'], ['business', 'verified_business', 'vip_business', 'founder', 'moderator'])) {
    header('Location: request_verification?type=business');
    exit();
}

$user_id = $_SESSION['user_id'];
$is_dark = (defined('CURRENT_THEME') && CURRENT_THEME == 'dark');

// Get user's businesses
$stmt = $pdo->prepare("
    SELECT bl.*, 
           (SELECT COUNT(*) FROM business_menu_categories WHERE business_id = bl.id) as menu_categories_count,
           (SELECT COUNT(*) FROM business_menu_items WHERE business_id = bl.id) as menu_items_count,
           (SELECT COUNT(*) FROM business_reviews WHERE business_id = bl.id) as reviews_count
    FROM business_listings bl
    WHERE bl.owner_id = ?
    ORDER BY bl.created_at DESC
");
$stmt->execute([$user_id]);
$businesses = $stmt->fetchAll();

// Get analytics summary
$analytics = [];
foreach ($businesses as $business) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT bv.id) as total_views,
            COUNT(DISTINCT br.id) as total_reviews,
            COALESCE(AVG(br.rating), 0) as avg_rating,
            COUNT(DISTINCT bf.id) as total_favorites
        FROM business_listings bl
        LEFT JOIN business_views bv ON bl.id = bv.business_id
        LEFT JOIN business_reviews br ON bl.id = br.business_id
        LEFT JOIN business_favorites bf ON bl.id = bf.business_id
        WHERE bl.id = ?
    ");
    $stmt->execute([$business['id']]);
    $analytics[$business['id']] = $stmt->fetch();
}

// Colors
$c_card = $is_dark ? '#1e293b' : '#ffffff';
$c_card_border = $is_dark ? '#334155' : '#e2e8f0';
$c_title = $is_dark ? '#f1f5f9' : '#0f172a';
$c_subtitle = $is_dark ? '#94a3b8' : '#64748b';
$c_text = $is_dark ? '#cbd5e1' : '#475569';
$c_stat_bg = $is_dark ? '#0f172a' : '#f8fafc';
$c_stat_border = $is_dark ? '#1e293b' : '#f1f5f9';
$c_action_bg = $is_dark ? '#0f172a' : '#f8fafc';
$c_action_border = $is_dark ? '#334155' : '#e2e8f0';
$c_action_hover = $is_dark ? '#1e293b' : '#f1f5f9';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo $is_dark ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Business Panel' : 'İşletme Paneli'; ?> - Kalkan Social</title>
    <meta name="description" content="<?php echo $lang == 'en' ? 'Manage your business, menu, and customer reviews' : 'İşletmenizi, menünüzü ve müşteri yorumlarınızı yönetin'; ?>">
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .bp-action {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.15s;
            border: 2px solid <?php echo $c_action_border; ?>;
            background: <?php echo $c_action_bg; ?>;
        }
        .bp-action:hover {
            background: <?php echo $c_action_hover; ?>;
            border-color: <?php echo $is_dark ? '#475569' : '#cbd5e1'; ?>;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .bp-action-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .bp-stat-card {
            text-align: center;
            padding: 16px 8px;
            border-radius: 14px;
            background: <?php echo $c_stat_bg; ?>;
            border: 1px solid <?php echo $c_stat_border; ?>;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-white min-h-screen pb-32 transition-colors duration-300 overflow-x-hidden">

    <!-- Background -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none dark:hidden" style="background: linear-gradient(135deg, #DBEAFE 0%, #EFF6FF 50%, #FFFFFF 100%);"></div>
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none hidden dark:block" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);"></div>

    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 pt-24 md:pt-28 max-w-3xl">
        
        <!-- Page Header -->
        <div style="margin-bottom:28px;">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
                <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-store" style="color:#fff;font-size:20px;"></i>
                </div>
                <div>
                    <h1 style="font-size:26px;font-weight:900;margin:0;color:<?php echo $c_title; ?>;">
                        <?php echo $lang == 'en' ? 'Your Business Panel' : 'İşletme Paneli'; ?>
                    </h1>
                    <p style="font-size:14px;color:<?php echo $c_subtitle; ?>;margin:2px 0 0;">
                        <?php echo $lang == 'en' ? 'Manage everything about your business from here' : 'İşletmenizle ilgili her şeyi buradan yönetin'; ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if (empty($businesses)): ?>
        <!-- Empty State -->
        <div style="background:<?php echo $c_card; ?>;border:2px solid <?php echo $c_card_border; ?>;border-radius:20px;padding:48px 24px;text-align:center;">
            <div style="width:80px;height:80px;border-radius:50%;background:<?php echo $is_dark ? '#1e1b4b' : '#ede9fe'; ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="fas fa-store" style="font-size:32px;color:#8b5cf6;"></i>
            </div>
            <h2 style="font-size:22px;font-weight:800;color:<?php echo $c_title; ?>;margin:0 0 8px;">
                <?php echo $lang == 'en' ? 'No Businesses Yet' : 'Henüz İşletme Eklenmedi'; ?>
            </h2>
            <p style="font-size:15px;color:<?php echo $c_subtitle; ?>;margin:0 0 24px;max-width:360px;margin-left:auto;margin-right:auto;line-height:1.6;">
                <?php echo $lang == 'en' 
                    ? 'Add your business to start getting customers from Kalkan Social. It\'s free and takes 2 minutes!' 
                    : 'İşletmenizi ekleyin ve Kalkan Social üzerinden müşteri kazanmaya başlayın. Ücretsiz ve sadece 2 dakika!'; ?>
            </p>
            <a href="add_business" style="display:inline-flex;align-items:center;gap:10px;padding:16px 32px;border-radius:14px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;font-size:16px;font-weight:800;text-decoration:none;box-shadow:0 4px 12px rgba(139,92,246,0.3);transition:all 0.15s;">
                <i class="fas fa-plus-circle" style="font-size:18px;"></i>
                <?php echo $lang == 'en' ? 'Add Your Business' : 'İşletmeni Ekle'; ?>
            </a>
        </div>

        <?php else: ?>

        <!-- Each Business -->
        <?php foreach ($businesses as $business): 
            $stats = $analytics[$business['id']];
            $has_menu = $business['menu_items_count'] > 0;

            // Subdomain info
            $subdomain_stmt = $pdo->prepare("SELECT subdomain, subdomain_status FROM business_listings WHERE id = ?");
            $subdomain_stmt->execute([$business['id']]);
            $subdomain_info = $subdomain_stmt->fetch();

            // Menu URL
            $business_menu_url = 'menu/' . $business['id'];
            if (!empty($business['subdomain']) && $business['subdomain_status'] === 'approved') {
                $business_menu_url = 'https://' . $business['subdomain'] . '.kalkansocial.com';
            }
        ?>
        <div style="background:<?php echo $c_card; ?>;border:2px solid <?php echo $c_card_border; ?>;border-radius:20px;overflow:hidden;margin-bottom:24px;">
            
            <!-- Business Header with Cover -->
            <div style="position:relative;height:180px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);overflow:hidden;">
                <?php if ($business['cover_photo']): ?>
                    <img src="<?php echo htmlspecialchars($business['cover_photo']); ?>" alt="<?php echo htmlspecialchars($business['name']); ?>" style="width:100%;height:100%;object-fit:cover;">
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,0.7),transparent);"></div>
                <?php endif; ?>
                <div style="position:absolute;bottom:16px;left:20px;right:20px;">
                    <h2 style="font-size:24px;font-weight:900;color:#fff;margin:0 0 4px;"><?php echo htmlspecialchars($business['name']); ?></h2>
                    <div style="display:flex;align-items:center;gap:6px;color:rgba(255,255,255,0.8);font-size:13px;">
                        <i class="fas fa-map-marker-alt" style="font-size:11px;"></i>
                        <span><?php echo htmlspecialchars($business['address'] ?? 'Kalkan'); ?></span>
                    </div>
                </div>
                <!-- View page button -->
                <a href="business_detail?id=<?php echo $business['id']; ?>" style="position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.2);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;" title="<?php echo $lang == 'en' ? 'View your page' : 'Sayfanızı görün'; ?>">
                    <i class="fas fa-external-link-alt" style="font-size:13px;"></i>
                </a>
            </div>

            <div style="padding:20px;">

                <!-- Stats Row -->
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px;">
                    <div class="bp-stat-card">
                        <div style="font-size:24px;font-weight:900;color:#8b5cf6;"><?php echo number_format($stats['total_views']); ?></div>
                        <div style="font-size:12px;font-weight:600;color:<?php echo $c_subtitle; ?>;margin-top:2px;"><?php echo $lang == 'en' ? 'Views' : 'Görüntüleme'; ?></div>
                    </div>
                    <div class="bp-stat-card">
                        <div style="font-size:24px;font-weight:900;color:#eab308;">
                            <?php if($stats['avg_rating'] > 0): ?>
                                <?php echo number_format($stats['avg_rating'], 1); ?> ⭐
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </div>
                        <div style="font-size:12px;font-weight:600;color:<?php echo $c_subtitle; ?>;margin-top:2px;"><?php echo $lang == 'en' ? 'Rating' : 'Puan'; ?></div>
                    </div>
                    <div class="bp-stat-card">
                        <div style="font-size:24px;font-weight:900;color:#3b82f6;"><?php echo $stats['total_reviews']; ?></div>
                        <div style="font-size:12px;font-weight:600;color:<?php echo $c_subtitle; ?>;margin-top:2px;"><?php echo $lang == 'en' ? 'Reviews' : 'Yorum'; ?></div>
                    </div>
                    <div class="bp-stat-card">
                        <div style="font-size:24px;font-weight:900;color:#ec4899;"><?php echo $stats['total_favorites']; ?></div>
                        <div style="font-size:12px;font-weight:600;color:<?php echo $c_subtitle; ?>;margin-top:2px;"><?php echo $lang == 'en' ? 'Favorites' : 'Favori'; ?></div>
                    </div>
                </div>

                <!-- Status Badges -->
                <?php if ($subdomain_info && $subdomain_info['subdomain']): ?>
                    <?php if ($subdomain_info['subdomain_status'] === 'approved'): ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:12px;background:<?php echo $is_dark ? '#052e16' : '#f0fdf4'; ?>;border:1px solid <?php echo $is_dark ? '#166534' : '#bbf7d0'; ?>;margin-bottom:12px;">
                        <i class="fas fa-globe" style="font-size:18px;color:#22c55e;"></i>
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:700;color:<?php echo $is_dark ? '#86efac' : '#166534'; ?>;">
                                <?php echo $lang == 'en' ? 'Your website is live!' : 'Web siteniz yayında!'; ?>
                            </div>
                            <a href="https://<?php echo htmlspecialchars($subdomain_info['subdomain']); ?>.kalkansocial.com" target="_blank" style="font-size:13px;color:<?php echo $is_dark ? '#4ade80' : '#15803d'; ?>;text-decoration:underline;">
                                <?php echo htmlspecialchars($subdomain_info['subdomain']); ?>.kalkansocial.com
                            </a>
                        </div>
                        <a href="https://<?php echo htmlspecialchars($subdomain_info['subdomain']); ?>.kalkansocial.com" target="_blank" style="color:<?php echo $is_dark ? '#4ade80' : '#15803d'; ?>;font-size:14px;">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <?php elseif ($subdomain_info['subdomain_status'] === 'pending'): ?>
                    <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:12px;background:<?php echo $is_dark ? '#422006' : '#fffbeb'; ?>;border:1px solid <?php echo $is_dark ? '#92400e' : '#fde68a'; ?>;margin-bottom:12px;">
                        <i class="fas fa-clock" style="font-size:18px;color:#f59e0b;"></i>
                        <div>
                            <div style="font-size:14px;font-weight:700;color:<?php echo $is_dark ? '#fcd34d' : '#92400e'; ?>;">
                                <?php echo $lang == 'en' ? 'Website pending approval' : 'Web sitesi onay bekliyor'; ?>
                            </div>
                            <div style="font-size:12px;color:<?php echo $is_dark ? '#fbbf24' : '#b45309'; ?>;">
                                <?php echo htmlspecialchars($subdomain_info['subdomain']); ?>.kalkansocial.com
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($has_menu): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:12px;background:<?php echo $is_dark ? '#052e16' : '#f0fdf4'; ?>;border:1px solid <?php echo $is_dark ? '#166534' : '#bbf7d0'; ?>;margin-bottom:12px;">
                    <i class="fas fa-check-circle" style="font-size:18px;color:#22c55e;"></i>
                    <div style="flex:1;">
                        <div style="font-size:14px;font-weight:700;color:<?php echo $is_dark ? '#86efac' : '#166534'; ?>;">
                            <?php echo $lang == 'en' ? 'Digital menu is active' : 'Dijital menünüz aktif'; ?>
                        </div>
                        <div style="font-size:12px;color:<?php echo $is_dark ? '#4ade80' : '#15803d'; ?>;">
                            <?php echo $business['menu_categories_count']; ?> <?php echo $lang == 'en' ? 'categories' : 'kategori'; ?>, 
                            <?php echo $business['menu_items_count']; ?> <?php echo $lang == 'en' ? 'items' : 'ürün'; ?>
                        </div>
                    </div>
                    <a href="<?php echo $business_menu_url; ?>" target="_blank" style="color:<?php echo $is_dark ? '#4ade80' : '#15803d'; ?>;font-size:14px;">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Section Title -->
                <div style="margin:20px 0 12px;">
                    <h3 style="font-size:16px;font-weight:800;color:<?php echo $c_title; ?>;margin:0;">
                        <?php echo $lang == 'en' ? 'What would you like to do?' : 'Ne yapmak istersiniz?'; ?>
                    </h3>
                </div>

                <!-- Action Buttons - Clear & Descriptive -->
                <div style="display:grid;gap:10px;">

                    <!-- Menu Manager -->
                    <a href="menu_manager.php?business_id=<?php echo $business['id']; ?>" class="bp-action">
                        <div class="bp-action-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
                            <i class="fas fa-utensils" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:800;color:<?php echo $c_title; ?>;">
                                <?php echo $lang == 'en' ? 'Edit Menu' : 'Menüyü Düzenle'; ?>
                            </div>
                            <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                                <?php echo $lang == 'en' 
                                    ? 'Add or change dishes, prices, categories' 
                                    : 'Yemek, fiyat ve kategorileri ekleyin veya değiştirin'; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:<?php echo $c_subtitle; ?>;font-size:12px;"></i>
                    </a>

                    <!-- QR Code -->
                    <a href="qr_menu.php?business_id=<?php echo $business['id']; ?>" class="bp-action">
                        <div class="bp-action-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                            <i class="fas fa-qrcode" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:800;color:<?php echo $c_title; ?>;">
                                <?php echo $lang == 'en' ? 'QR Code for Tables' : 'Masalar İçin QR Kod'; ?>
                            </div>
                            <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                                <?php echo $lang == 'en' 
                                    ? 'Download & print QR codes for your tables' 
                                    : 'Masalarınız için QR kodu indirin ve yazdırın'; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:<?php echo $c_subtitle; ?>;font-size:12px;"></i>
                    </a>

                    <!-- Business Settings / Edit Info -->
                    <a href="business_settings.php?business_id=<?php echo $business['id']; ?>" class="bp-action">
                        <div class="bp-action-icon" style="background:linear-gradient(135deg,#64748b,#475569);">
                            <i class="fas fa-cog" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:800;color:<?php echo $c_title; ?>;">
                                <?php echo $lang == 'en' ? 'Business Info & Settings' : 'İşletme Bilgileri & Ayarlar'; ?>
                            </div>
                            <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                                <?php echo $lang == 'en' 
                                    ? 'Phone, address, hours, photos, description' 
                                    : 'Telefon, adres, çalışma saatleri, fotoğraf, açıklama'; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:<?php echo $c_subtitle; ?>;font-size:12px;"></i>
                    </a>

                    <!-- Analytics -->
                    <a href="business_analytics.php?business_id=<?php echo $business['id']; ?>" class="bp-action">
                        <div class="bp-action-icon" style="background:linear-gradient(135deg,#f97316,#ea580c);">
                            <i class="fas fa-chart-line" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:800;color:<?php echo $c_title; ?>;">
                                <?php echo $lang == 'en' ? 'Statistics & Reports' : 'İstatistikler & Raporlar'; ?>
                            </div>
                            <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                                <?php echo $lang == 'en' 
                                    ? 'See how many people viewed your business' 
                                    : 'İşletmenizi kaç kişinin gördüğünü öğrenin'; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:<?php echo $c_subtitle; ?>;font-size:12px;"></i>
                    </a>

                    <!-- Domain / Website -->
                    <?php if (!$subdomain_info || !$subdomain_info['subdomain'] || $subdomain_info['subdomain_status'] === 'rejected'): ?>
                    <a href="request_subdomain.php?business_id=<?php echo $business['id']; ?>" class="bp-action">
                        <div class="bp-action-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a);">
                            <i class="fas fa-globe" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:800;color:<?php echo $c_title; ?>;">
                                <?php echo $lang == 'en' ? 'Get Your Own Website' : 'Kendi Web Sitenizi Alın'; ?>
                            </div>
                            <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                                <?php echo $lang == 'en' 
                                    ? 'Free website: yourname.kalkansocial.com' 
                                    : 'Ücretsiz web sitesi: isminiz.kalkansocial.com'; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:<?php echo $c_subtitle; ?>;font-size:12px;"></i>
                    </a>
                    <?php else: ?>
                    <a href="request_subdomain.php?business_id=<?php echo $business['id']; ?>" class="bp-action">
                        <div class="bp-action-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a);">
                            <i class="fas fa-globe" style="color:#fff;font-size:20px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:16px;font-weight:800;color:<?php echo $c_title; ?>;">
                                <?php echo $lang == 'en' ? 'Website Settings' : 'Web Sitesi Ayarları'; ?>
                            </div>
                            <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                                <?php echo $lang == 'en' 
                                    ? 'Manage your web address' 
                                    : 'Web adresinizi yönetin'; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:<?php echo $c_subtitle; ?>;font-size:12px;"></i>
                    </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Add Another Business -->
        <?php if (count($businesses) > 0): ?>
        <div style="text-align:center;margin:20px 0;">
            <a href="add_business" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:14px;background:<?php echo $c_card; ?>;border:2px dashed <?php echo $c_card_border; ?>;color:<?php echo $c_subtitle; ?>;font-size:15px;font-weight:700;text-decoration:none;transition:all 0.15s;">
                <i class="fas fa-plus-circle"></i>
                <?php echo $lang == 'en' ? 'Add Another Business' : 'Başka İşletme Ekle'; ?>
            </a>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        <!-- Help Section -->
        <div style="margin-top:32px;padding:24px;border-radius:18px;background:<?php echo $is_dark ? '#1e293b' : '#f8fafc'; ?>;border:1px solid <?php echo $c_card_border; ?>;">
            <h3 style="font-size:18px;font-weight:800;color:<?php echo $c_title; ?>;margin:0 0 16px;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-lightbulb" style="color:#eab308;"></i>
                <?php echo $lang == 'en' ? 'How does it work?' : 'Nasıl çalışır?'; ?>
            </h3>
            <div style="display:grid;gap:14px;">
                <!-- Step 1 -->
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:<?php echo $is_dark ? '#312e81' : '#ede9fe'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:14px;font-weight:900;color:#8b5cf6;">1</span>
                    </div>
                    <div>
                        <div style="font-size:15px;font-weight:700;color:<?php echo $c_title; ?>;">
                            <?php echo $lang == 'en' ? 'Add your dishes to the menu' : 'Yemeklerinizi menüye ekleyin'; ?>
                        </div>
                        <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                            <?php echo $lang == 'en' 
                                ? 'Click "Edit Menu", add categories (Starters, Mains, Drinks...) and dishes with prices.' 
                                : '"Menüyü Düzenle"ye tıklayın, kategoriler (Başlangıçlar, Ana Yemekler, İçecekler...) ve fiyatlarla yemekler ekleyin.'; ?>
                        </div>
                    </div>
                </div>
                <!-- Step 2 -->
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:<?php echo $is_dark ? '#0c4a6e' : '#e0f2fe'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:14px;font-weight:900;color:#0ea5e9;">2</span>
                    </div>
                    <div>
                        <div style="font-size:15px;font-weight:700;color:<?php echo $c_title; ?>;">
                            <?php echo $lang == 'en' ? 'Print QR codes for your tables' : 'Masalarınıza QR kod yazdırın'; ?>
                        </div>
                        <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                            <?php echo $lang == 'en' 
                                ? 'Click "QR Code for Tables", download the image, and print it for each table.' 
                                : '"Masalar İçin QR Kod"a tıklayın, görseli indirin ve her masaya yazdırın.'; ?>
                        </div>
                    </div>
                </div>
                <!-- Step 3 -->
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:<?php echo $is_dark ? '#052e16' : '#dcfce7'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="font-size:14px;font-weight:900;color:#22c55e;">3</span>
                    </div>
                    <div>
                        <div style="font-size:15px;font-weight:700;color:<?php echo $c_title; ?>;">
                            <?php echo $lang == 'en' ? 'Customers scan & see your menu!' : 'Müşteriler tarayıp menünüzü görsün!'; ?>
                        </div>
                        <div style="font-size:13px;color:<?php echo $c_subtitle; ?>;margin-top:2px;">
                            <?php echo $lang == 'en' 
                                ? 'Customers point their phone camera at the QR code — your menu opens instantly. No app needed!' 
                                : 'Müşteriler telefon kamerasını QR koda tutarlar — menünüz anında açılır. Uygulama indirmeye gerek yok!'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div style="text-align:center;margin:24px 0 16px;">
            <p style="font-size:13px;color:<?php echo $c_subtitle; ?>;">
                <?php echo $lang == 'en' ? 'Need help?' : 'Yardıma mı ihtiyacınız var?'; ?>
                <a href="contact" style="color:#8b5cf6;font-weight:700;text-decoration:underline;">
                    <?php echo $lang == 'en' ? 'Contact us' : 'Bize ulaşın'; ?>
                </a>
            </p>
        </div>

    </main>

    <div class="h-20 md:hidden"></div>

</body>
</html>
