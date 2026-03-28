<?php
require_once 'includes/bootstrap.php';

$stmt = $pdo->query("SELECT h.*, u.username, u.full_name, u.avatar 
                     FROM happy_hours h 
                     JOIN users u ON h.user_id = u.id 
                     ORDER BY h.start_time ASC");
$deals = $stmt->fetchAll();

// Separate by type
$happy_hours = [];
$live_music = [];
$now = date('H:i');

foreach ($deals as &$d) {
    $d['is_live'] = ($now >= date('H:i', strtotime($d['start_time'])) && $now <= date('H:i', strtotime($d['end_time'])));
    if (($d['event_type'] ?? 'happy_hour') === 'live_music') {
        $live_music[] = $d;
    } else {
        $happy_hours[] = $d;
    }
}
unset($d);

// Subscription check
$is_subscribed = false;
if (isset($_SESSION['user_id'])) {
    try {
        $sub_stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND service = 'happy_hour'");
        $sub_stmt->execute([$_SESSION['user_id']]);
        $is_subscribed = $sub_stmt->fetch() ? true : false;
    } catch (PDOException $e) { $is_subscribed = false; }
}

$active_tab = $_GET['tab'] ?? 'all';

// Dark mode colors
$dk = defined('CURRENT_THEME') && CURRENT_THEME == 'dark';
$c_bg      = $dk ? '#1e293b' : '#fff';
$c_border  = $dk ? '#334155' : '#e2e8f0';
$c_title   = $dk ? '#f1f5f9' : '#0f172a';
$c_text    = $dk ? '#cbd5e1' : '#334155';
$c_muted   = $dk ? '#64748b' : '#94a3b8';
$c_surface = $dk ? '#0f172a' : '#f1f5f9';
$c_input   = $dk ? '#0f172a' : '#fff';
$c_badge_hh_bg = $dk ? 'rgba(236,72,153,0.12)' : '#fdf2f8';
$c_badge_lm_bg = $dk ? 'rgba(124,58,237,0.12)' : '#f5f3ff';
$c_live_hh = $dk ? '#c4b5fd' : '#c4b5fd';
$c_live_lm = $dk ? '#fbcfe8' : '#fbcfe8';
$c_btn_bg  = $dk ? '#e2e8f0' : '#0f172a';
$c_btn_txt = $dk ? '#0f172a' : '#fff';
$c_map_bg  = $dk ? '#334155' : '#f1f5f9';
$c_map_txt = $dk ? '#94a3b8' : '#64748b';
$c_del_brd = $dk ? '#475569' : '#e2e8f0';
$c_del_clr = $dk ? '#64748b' : '#cbd5e1';
$c_modal_bg = $dk ? '#1e293b' : '#fff';
$c_toggle_bg = $dk ? '#0f172a' : '#f1f5f9';
$c_toggle_active = $dk ? '#334155' : '#fff';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="<?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang == 'en' ? 'Nightlife & Events' : 'Gece Hayatı & Etkinlikler'; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
        .event-card { transition: transform 0.15s, box-shadow 0.15s; }
        .event-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        .tab-btn { transition: all 0.15s; }
        .tab-btn:hover { background: #f1f5f9; }
        .dark .tab-btn:hover { background: rgba(51,65,85,0.5); }
        .tab-btn.active { font-weight: 800; background: #e2e8f0 !important; }
        .dark .tab-btn.active { background: #334155 !important; color: #e2e8f0 !important; }
        @keyframes pulse-live { 0%,100% { opacity:1; } 50% { opacity:0.4; } }
        .live-dot { animation: pulse-live 1.5s infinite; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen pb-24">

    <?php include 'includes/header.php'; ?>

    <div class="max-w-2xl mx-auto px-4 pt-24 pb-8">

        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;">
            <div>
                <h1 style="font-size:22px;font-weight:900;margin:0;color:<?php echo $c_title; ?>;"><?php echo $lang == 'en' ? 'Nightlife & Events' : 'Gece Hayatı'; ?></h1>
                <p style="font-size:13px;color:#94a3b8;margin:3px 0 0;">
                    <?php echo count($deals); ?> <?php echo $lang == 'en' ? 'active events in Kalkan' : 'aktif etkinlik'; ?>
                </p>
            </div>
            <div style="display:flex;gap:8px;">
                <?php if(isset($_SESSION['user_id'])): ?>
                <button onclick="toggleSubscription('happy_hour', this)" style="width:40px;height:40px;border-radius:10px;background:<?php echo $c_bg; ?>;border:1px solid <?php echo $c_border; ?>;cursor:pointer;display:flex;align-items:center;justify-content:center;color:<?php echo $is_subscribed ? '#f59e0b' : $c_muted; ?>;font-size:16px;position:relative;" title="<?php echo $lang == 'en' ? 'Notifications' : 'Bildirimler'; ?>">
                    <i class="<?php echo $is_subscribed ? 'fas' : 'far'; ?> fa-bell"></i>
                    <?php if($is_subscribed): ?>
                    <div style="position:absolute;top:4px;right:4px;width:8px;height:8px;border-radius:50%;background:#ef4444;border:2px solid <?php echo $c_bg; ?>;"></div>
                    <?php endif; ?>
                </button>
                <?php endif; ?>
                <button onclick="openAddModal()" style="padding:10px 16px;border-radius:10px;background:<?php echo $c_btn_bg; ?>;color:<?php echo $c_btn_txt; ?>;border:none;cursor:pointer;font-size:13px;font-weight:700;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-plus" style="font-size:11px;"></i> <?php echo $lang == 'en' ? 'Add' : 'Ekle'; ?>
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div style="display:flex;gap:6px;margin-bottom:20px;">
            <button onclick="filterTab('all')" class="tab-btn <?php echo $active_tab === 'all' ? 'active' : ''; ?>" data-tab="all" style="padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;color:inherit;background:transparent;">
                <?php echo $lang == 'en' ? 'All' : 'Tümü'; ?> <span style="opacity:0.5;"><?php echo count($deals); ?></span>
            </button>
            <button onclick="filterTab('happy_hour')" class="tab-btn" data-tab="happy_hour" style="padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;color:inherit;background:transparent;">
                🍹 Happy Hour <span style="opacity:0.5;"><?php echo count($happy_hours); ?></span>
            </button>
            <button onclick="filterTab('live_music')" class="tab-btn" data-tab="live_music" style="padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;color:inherit;background:transparent;">
                🎸 Live <span style="opacity:0.5;"><?php echo count($live_music); ?></span>
            </button>
        </div>

        <!-- Events List -->
        <?php if(empty($deals)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <div style="width:64px;height:64px;border-radius:50%;background:<?php echo $c_surface; ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;">
                🎵
            </div>
            <h3 style="font-weight:800;color:<?php echo $c_muted; ?>;font-size:16px;margin:0 0 4px;"><?php echo $lang == 'en' ? 'No Events Yet' : 'Henüz Etkinlik Yok'; ?></h3>
            <p style="font-size:13px;color:<?php echo $c_del_clr; ?>;margin:0 0 16px;">
                <?php echo $lang == 'en' ? 'Be the first to share a happy hour or live music event!' : 'İlk etkinliği siz paylaşın!'; ?>
            </p>
            <button onclick="openAddModal()" style="padding:10px 20px;border-radius:10px;background:<?php echo $c_btn_bg; ?>;color:<?php echo $c_btn_txt; ?>;border:none;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-plus" style="margin-right:6px;"></i><?php echo $lang == 'en' ? 'Add Event' : 'Etkinlik Ekle'; ?>
            </button>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px;" id="events-list">
            <?php foreach($deals as $deal): 
                $type = $deal['event_type'] ?? 'happy_hour';
                $start = date('H:i', strtotime($deal['start_time']));
                $end = date('H:i', strtotime($deal['end_time']));
                $is_live = $deal['is_live'];
                $is_music = ($type === 'live_music');
            ?>
            <div class="event-card" data-type="<?php echo $type; ?>" style="background:<?php echo $c_bg; ?>;border:1px solid <?php echo $is_live ? ($is_music ? '#c4b5fd' : '#fbcfe8') : $c_border; ?>;border-radius:16px;padding:16px;position:relative;overflow:hidden;">
                
                <?php if($is_live): ?>
                <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?php echo $is_music ? '#7c3aed' : '#ec4899'; ?>;"></div>
                <?php endif; ?>

                <div style="display:flex;gap:14px;">
                    
                    <!-- Time Column -->
                    <div style="flex-shrink:0;text-align:center;min-width:48px;">
                        <?php if($is_live): ?>
                        <div style="display:flex;align-items:center;gap:4px;margin-bottom:4px;">
                            <div class="live-dot" style="width:6px;height:6px;border-radius:50%;background:<?php echo $is_music ? '#7c3aed' : '#ec4899'; ?>;"></div>
                            <span style="font-size:9px;font-weight:800;color:<?php echo $is_music ? '#7c3aed' : '#ec4899'; ?>;text-transform:uppercase;letter-spacing:0.5px;">LIVE</span>
                        </div>
                        <?php endif; ?>
                        <div style="font-size:18px;font-weight:900;color:<?php echo $c_title; ?>;line-height:1;"><?php echo $start; ?></div>
                        <div style="font-size:11px;color:#94a3b8;font-weight:600;"><?php echo $end; ?></div>
                    </div>

                    <!-- Content -->
                    <div style="flex:1;min-width:0;">
                        
                        <!-- Type + Venue -->
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;flex-wrap:wrap;">
                            <span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;<?php echo $is_music ? 'background:'.$c_badge_lm_bg.';color:#7c3aed;' : 'background:'.$c_badge_hh_bg.';color:#ec4899;'; ?>">
                                <?php echo $is_music ? '🎸 Live Music' : '🍹 Happy Hour'; ?>
                            </span>
                            <h3 style="font-size:15px;font-weight:800;margin:0;color:<?php echo $c_title; ?>;"><?php echo htmlspecialchars($deal['venue_name']); ?></h3>
                        </div>

                        <?php if($is_music && !empty($deal['performer_name'])): ?>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <span style="font-size:14px;font-weight:700;color:<?php echo $is_music ? '#7c3aed' : '#334155'; ?>;"><?php echo htmlspecialchars($deal['performer_name']); ?></span>
                            <?php if(!empty($deal['music_genre'])): ?>
                            <span style="font-size:11px;color:#94a3b8;font-weight:600;"><?php echo htmlspecialchars($deal['music_genre']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($deal['description'])): ?>
                        <p style="font-size:13px;color:#64748b;margin:0 0 8px;line-height:1.5;"><?php echo htmlspecialchars($deal['description']); ?></p>
                        <?php endif; ?>

                        <?php if(!empty($deal['photo_url'])): ?>
                        <div style="border-radius:12px;overflow:hidden;margin-bottom:8px;height:140px;">
                            <img src="<?php echo htmlspecialchars($deal['photo_url']); ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy" alt="">
                        </div>
                        <?php endif; ?>

                        <!-- Footer -->
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <img src="<?php echo htmlspecialchars($deal['avatar'] ?? ''); ?>" style="width:24px;height:24px;border-radius:50%;object-fit:cover;border:1px solid <?php echo $c_border; ?>;" alt="">
                                <span style="font-size:11px;color:<?php echo $c_muted; ?>;font-weight:600;">@<?php echo htmlspecialchars($deal['username']); ?></span>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <a href="https://maps.google.com/?q=<?php echo urlencode($deal['venue_name'] . ' Kalkan'); ?>" target="_blank" style="padding:6px 12px;border-radius:8px;background:<?php echo $c_map_bg; ?>;color:<?php echo $c_map_txt; ?>;font-size:11px;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:4px;">
                                    <i class="fas fa-map-marker-alt" style="font-size:10px;"></i> <?php echo $lang == 'en' ? 'Map' : 'Harita'; ?>
                                </a>
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $deal['user_id']): ?>
                                <button onclick="confirmDeleteEvent(<?php echo $deal['id']; ?>)" style="padding:6px 10px;border-radius:8px;background:transparent;color:<?php echo $c_del_clr; ?>;border:1px solid <?php echo $c_del_brd; ?>;font-size:11px;cursor:pointer;" title="<?php echo $lang == 'en' ? 'Delete' : 'Sil'; ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Subscription Modal -->
    <div id="sub-success-modal" style="display:none;position:fixed;inset:0;z-index:60;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);" onclick="if(event.target===this)this.style.display='none'">
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:<?php echo $c_modal_bg; ?>;border-radius:16px;padding:28px;text-align:center;width:90%;max-width:340px;box-shadow:0 20px 60px rgba(0,0,0,0.25);border:1px solid <?php echo $c_border; ?>;">
            <div style="width:56px;height:56px;border-radius:50%;background:<?php echo $dk ? 'rgba(16,185,129,0.12)' : '#ecfdf5'; ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fas fa-check" style="font-size:22px;color:#10b981;"></i>
            </div>
            <h3 style="font-size:18px;font-weight:900;margin:0 0 6px;color:<?php echo $c_title; ?>;"><?php echo $lang == 'en' ? 'Subscribed!' : 'Abone Olundu!'; ?></h3>
            <p style="font-size:13px;color:<?php echo $c_muted; ?>;margin:0 0 18px;">
                <?php echo $lang == 'en' ? 'You will be notified about new events.' : 'Yeni etkinliklerden haberdar olacaksınız.'; ?>
            </p>
            <button onclick="document.getElementById('sub-success-modal').style.display='none'" style="width:100%;padding:12px;border-radius:10px;background:<?php echo $c_btn_bg; ?>;color:<?php echo $c_btn_txt; ?>;border:none;font-size:13px;font-weight:700;cursor:pointer;">
                <?php echo $lang == 'en' ? 'Got it' : 'Tamam'; ?>
            </button>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div id="add-deal-modal" style="display:none;position:fixed;inset:0;z-index:60;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);overflow-y:auto;" onclick="if(event.target===this)this.style.display='none'">
        <div style="min-height:100%;display:flex;align-items:flex-end;justify-content:center;padding:0;">
            <div style="background:<?php echo $c_modal_bg; ?>;border-radius:20px 20px 0 0;padding:24px;width:100%;max-width:480px;box-shadow:0 -10px 40px rgba(0,0,0,0.15);border-top:1px solid <?php echo $c_border; ?>;" onclick="event.stopPropagation()">
                
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                    <h3 style="font-size:18px;font-weight:900;margin:0;color:<?php echo $c_title; ?>;"><?php echo $lang == 'en' ? 'New Event' : 'Yeni Etkinlik'; ?></h3>
                    <button onclick="document.getElementById('add-deal-modal').style.display='none'" style="width:32px;height:32px;border-radius:8px;background:<?php echo $c_surface; ?>;border:none;cursor:pointer;color:<?php echo $c_muted; ?>;font-size:14px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="addDealForm">
                    
                    <!-- Type Toggle -->
                    <div style="display:flex;gap:4px;padding:4px;background:<?php echo $c_toggle_bg; ?>;border-radius:10px;margin-bottom:16px;">
                        <button type="button" onclick="setEventType('happy_hour')" id="type-btn-happy_hour" style="flex:1;padding:10px;border-radius:8px;border:none;font-size:13px;font-weight:700;cursor:pointer;background:<?php echo $c_toggle_active; ?>;color:#ec4899;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                            🍹 Happy Hour
                        </button>
                        <button type="button" onclick="setEventType('live_music')" id="type-btn-live_music" style="flex:1;padding:10px;border-radius:8px;border:none;font-size:13px;font-weight:700;cursor:pointer;background:transparent;color:<?php echo $c_muted; ?>;">
                            🎸 Live Music
                        </button>
                    </div>
                    <input type="hidden" name="event_type" id="event_type_input" value="happy_hour">

                    <div style="margin-bottom:12px;">
                        <label style="display:block;font-size:11px;font-weight:700;color:<?php echo $c_muted; ?>;text-transform:uppercase;margin-bottom:5px;"><?php echo $lang == 'en' ? 'Venue Name' : 'Mekan Adı'; ?> *</label>
                        <input type="text" name="venue_name" required placeholder="<?php echo $lang == 'en' ? 'e.g. Black & Gold Bar' : 'örn. Black & Gold Bar'; ?>"
                            style="width:100%;padding:11px 14px;border-radius:10px;border:1px solid <?php echo $c_border; ?>;font-size:14px;font-weight:600;outline:none;box-sizing:border-box;background:<?php echo $c_input; ?>;color:<?php echo $c_title; ?>;">
                    </div>

                    <!-- Live Music Fields -->
                    <div id="live_music_fields" style="display:none;">
                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:11px;font-weight:700;color:<?php echo $c_muted; ?>;text-transform:uppercase;margin-bottom:5px;"><?php echo $lang == 'en' ? 'Performer / Band' : 'Sanatçı / Grup'; ?></label>
                            <input type="text" name="performer_name" id="input_performer" placeholder="<?php echo $lang == 'en' ? 'e.g. Jazz Trio' : 'örn. Caz Grubu'; ?>"
                                style="width:100%;padding:11px 14px;border-radius:10px;border:1px solid <?php echo $c_border; ?>;font-size:14px;font-weight:600;outline:none;box-sizing:border-box;background:<?php echo $c_input; ?>;color:<?php echo $c_title; ?>;">
                        </div>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:11px;font-weight:700;color:<?php echo $c_muted; ?>;text-transform:uppercase;margin-bottom:5px;"><?php echo $lang == 'en' ? 'Genre' : 'Tür'; ?></label>
                            <select name="music_genre" style="width:100%;padding:11px 14px;border-radius:10px;border:1px solid <?php echo $c_border; ?>;font-size:14px;outline:none;box-sizing:border-box;background:<?php echo $c_input; ?>;color:<?php echo $c_title; ?>;">
                                <option value="">--</option>
                                <option value="Jazz">Jazz</option>
                                <option value="Acoustic">Acoustic</option>
                                <option value="Rock">Rock</option>
                                <option value="Pop">Pop</option>
                                <option value="Turkish Pop">Turkish Pop</option>
                                <option value="DJ">DJ</option>
                                <option value="Traditional">Traditional / Fasıl</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                        <div>
                            <label style="display:block;font-size:11px;font-weight:700;color:<?php echo $c_muted; ?>;text-transform:uppercase;margin-bottom:5px;"><?php echo $lang == 'en' ? 'Start' : 'Başlangıç'; ?> *</label>
                            <input type="time" name="start_time" required style="width:100%;padding:11px 14px;border-radius:10px;border:1px solid <?php echo $c_border; ?>;font-size:14px;font-weight:600;outline:none;box-sizing:border-box;background:<?php echo $c_input; ?>;color:<?php echo $c_title; ?>;">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;font-weight:700;color:<?php echo $c_muted; ?>;text-transform:uppercase;margin-bottom:5px;"><?php echo $lang == 'en' ? 'End' : 'Bitiş'; ?> *</label>
                            <input type="time" name="end_time" required style="width:100%;padding:11px 14px;border-radius:10px;border:1px solid <?php echo $c_border; ?>;font-size:14px;font-weight:600;outline:none;box-sizing:border-box;background:<?php echo $c_input; ?>;color:<?php echo $c_title; ?>;">
                        </div>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label style="display:block;font-size:11px;font-weight:700;color:<?php echo $c_muted; ?>;text-transform:uppercase;margin-bottom:5px;"><?php echo $lang == 'en' ? 'Description' : 'Açıklama'; ?></label>
                        <textarea name="description" rows="2" placeholder="<?php echo $lang == 'en' ? 'e.g. All cocktails 20% off' : 'örn. Tüm kokteyller %20 indirimli'; ?>"
                            style="width:100%;padding:11px 14px;border-radius:10px;border:1px solid <?php echo $c_border; ?>;font-size:14px;outline:none;resize:none;box-sizing:border-box;background:<?php echo $c_input; ?>;color:<?php echo $c_title; ?>;"></textarea>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:11px;font-weight:700;color:<?php echo $c_muted; ?>;text-transform:uppercase;margin-bottom:5px;"><?php echo $lang == 'en' ? 'Photo (optional)' : 'Fotoğraf (isteğe bağlı)'; ?></label>
                        <input type="file" name="photo" accept="image/*" style="width:100%;padding:10px;border-radius:10px;border:1px solid <?php echo $c_border; ?>;font-size:13px;box-sizing:border-box;background:<?php echo $c_input; ?>;color:<?php echo $c_title; ?>;">
                    </div>

                    <button type="submit" id="submit-event-btn" style="width:100%;padding:14px;border-radius:10px;background:<?php echo $c_btn_bg; ?>;color:<?php echo $c_btn_txt; ?>;border:none;font-size:14px;font-weight:800;cursor:pointer;">
                        <span id="submit-text"><i class="fas fa-paper-plane" style="margin-right:6px;"></i><?php echo $lang == 'en' ? 'Publish Event' : 'Etkinliği Yayınla'; ?></span>
                        <span id="submit-loading" style="display:none;"><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i><?php echo $lang == 'en' ? 'Publishing...' : 'Yayınlanıyor...'; ?></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Tab filtering
    function filterTab(type) {
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.tab === type);
        });
        document.querySelectorAll('.event-card').forEach(function(card) {
            card.style.display = (type === 'all' || card.dataset.type === type) ? '' : 'none';
        });
    }

    // Event type toggle in modal
    var _toggleActive = '<?php echo $c_toggle_active; ?>';
    var _toggleMuted = '<?php echo $c_muted; ?>';
    function setEventType(type) {
        document.getElementById('event_type_input').value = type;
        var btnH = document.getElementById('type-btn-happy_hour');
        var btnM = document.getElementById('type-btn-live_music');
        var fields = document.getElementById('live_music_fields');
        
        if (type === 'live_music') {
            btnM.style.background = _toggleActive; btnM.style.color = '#7c3aed'; btnM.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
            btnH.style.background = 'transparent'; btnH.style.color = _toggleMuted; btnH.style.boxShadow = 'none';
            fields.style.display = 'block';
        } else {
            btnH.style.background = _toggleActive; btnH.style.color = '#ec4899'; btnH.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
            btnM.style.background = 'transparent'; btnM.style.color = _toggleMuted; btnM.style.boxShadow = 'none';
            fields.style.display = 'none';
        }
    }

    function openAddModal() {
        <?php if(!isset($_SESSION['user_id'])): ?>
        location.href = 'login'; return;
        <?php endif; ?>
        document.getElementById('add-deal-modal').style.display = 'block';
    }

    // Subscribe
    function toggleSubscription(service, btn) {
        var icon = btn.querySelector('i');
        fetch('api/subscribe.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ service: service })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                if (data.subscribed) {
                    icon.className = 'fas fa-bell';
                    btn.style.color = '#f59e0b';
                    document.getElementById('sub-success-modal').style.display = 'block';
                } else {
                    icon.className = 'far fa-bell';
                    btn.style.color = '#94a3b8';
                }
            } else if (data.message === 'Login required') {
                location.href = 'login';
            }
        });
    }

    // Submit form
    document.getElementById('addDealForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        var btn = document.getElementById('submit-event-btn');
        document.getElementById('submit-text').style.display = 'none';
        document.getElementById('submit-loading').style.display = 'inline';
        btn.disabled = true;

        fetch('api/add_happy_hour.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                setTimeout(function() { location.reload(); }, 500);
            } else {
                alert(data.message || 'Error');
                document.getElementById('submit-text').style.display = 'inline';
                document.getElementById('submit-loading').style.display = 'none';
                btn.disabled = false;
            }
        })
        .catch(function() {
            document.getElementById('submit-text').style.display = 'inline';
            document.getElementById('submit-loading').style.display = 'none';
            btn.disabled = false;
        });
    });

    // Delete
    function confirmDeleteEvent(id) {
        if (confirm('<?php echo $lang == 'en' ? 'Delete this event?' : 'Bu etkinliği silmek istediğinize emin misiniz?'; ?>')) {
            fetch('api/delete_happy_hour.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') location.reload();
                else alert(data.message || 'Error');
            });
        }
    }

    // Init
    filterTab('all');
    </script>
</body>
</html>
