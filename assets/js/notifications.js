(function () {
    function getLang() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('lang') || localStorage.getItem('site_lang') || 'tr';
    }

    const t = {
        tr: {
            confirm_delete: 'Tüm bildirimleri silmek istediğinizden emin misiniz?',
            no_notifications: 'Henüz bildirim yok',
            error: 'Hata oluştu',
            loading: 'Yükleniyor...',
            read_all: 'Tümünü Oku',
            just_now: 'Az önce',
            m_ago: ' dk önce',
            h_ago: ' saat önce',
            d_ago: ' gün önce',
            friend_req_text: 'size arkadaşlık isteği gönderdi',
            accept: 'Kabul Et',
            decline: 'Reddet',
            you_are_friends: '✨ Artık arkadaşsınız!',
            friend_req_sent: 'İstek gönderildi'
        },
        en: {
            confirm_delete: 'Are you sure you want to delete all notifications?',
            no_notifications: 'No notifications yet',
            error: 'Error occurred',
            loading: 'Loading...',
            read_all: 'Read All',
            just_now: 'Just now',
            m_ago: 'm ago',
            h_ago: 'h ago',
            d_ago: 'd ago',
            friend_req_text: 'sent you a friend request',
            accept: 'Accept',
            decline: 'Decline',
            you_are_friends: '✨ You are now friends!',
            friend_req_sent: 'Request sent'
        }
    };

    function getTimeAgo(date) {
        const langCode = (getLang() === 'en') ? 'en' : 'tr';
        const texts = t[langCode];
        const seconds = Math.floor((new Date() - date) / 1000);

        if (seconds < 60) return texts.just_now;
        if (seconds < 3600) return Math.floor(seconds / 60) + texts.m_ago;
        if (seconds < 86400) return Math.floor(seconds / 3600) + texts.h_ago;
        return Math.floor(seconds / 86400) + texts.d_ago;
    }

    window.toggleNotifications = function () {
        const modal = document.getElementById('notification-modal');
        if (!modal) return;

        const backdrop = document.getElementById('notif-backdrop');
        const panel = document.getElementById('notif-panel');
        const badge = document.getElementById('notif-badge');

        if (modal.classList.contains('hidden')) {
            // Open
            modal.classList.remove('hidden');
            // Allow browser paint
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                if (panel) {
                    panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
                    panel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
                }
            });

            loadNotifications();
            if (badge) badge.classList.add('hidden');
        } else {
            // Close
            backdrop.classList.add('opacity-0');
            if (panel) {
                panel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
                panel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            }

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    };

    window.loadNotifications = async function () {
        const container = document.getElementById('notifications-list');
        if (!container) return;

        const langCode = (getLang() === 'en') ? 'en' : 'tr';
        const texts = t[langCode];

        try {
            const response = await fetch('api/get_notifications.php');
            const data = await response.json();

            if (data.status === 'success' && data.notifications && data.notifications.length > 0) {
                updateNotificationBadge(data.unread_count);
                renderNotifications(data.notifications);
            } else {
                updateNotificationBadge(0);
                container.innerHTML = `<div class="flex flex-col items-center justify-center h-64 text-slate-400 gap-3">
                    <i class="fas fa-bell-slash text-4xl opacity-20"></i>
                    <span>${texts.no_notifications}</span>
                </div>`;
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            container.innerHTML = `<div class="p-8 text-center text-red-500 text-sm">${texts.error}</div>`;
        }
    };

    function updateNotificationBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
    }

    function renderNotifications(notifications) {
        const container = document.getElementById('notifications-list');
        if (!container) return;

        const langCode = (getLang() === 'en') ? 'en' : 'tr';
        const texts = t[langCode];

        container.innerHTML = notifications.map(notif => {
            const timeAgo = getTimeAgo(new Date(notif.created_at));
            const unreadClass = notif.is_read == 0 ? 'bg-pink-50/50 dark:bg-pink-900/10' : '';
            let icon = '❤️';
            if (notif.type === 'comment') icon = '💬';
            if (notif.type === 'follow') icon = '👤';
            if (notif.type === 'friend_request') icon = '👥';
            if (notif.type === 'friend_accept') icon = '✅';

            if (notif.type === 'friend_request') {
                return `
                    <div class="p-4 ${unreadClass} border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors" id="notification-${notif.id}">
                        <div class="flex gap-4">
                            <img src="${notif.actor_avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(notif.actor_name)}" class="w-12 h-12 rounded-full flex-shrink-0 object-cover shadow-sm cursor-pointer" onclick="window.location.href='profile?uid=${notif.source_id}'">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-800 dark:text-gray-200"><span class="font-bold text-slate-900 dark:text-white">${notif.actor_name}</span> ${texts.friend_req_text}</p>
                                <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">${icon} ${timeAgo}</p>
                                <div class="flex gap-2 mt-3">
                                    <button onclick="event.stopPropagation(); respondToRequestFromNotif(${notif.source_id}, 'accept', ${notif.id})" class="flex-1 bg-pink-500 hover:bg-pink-600 text-white font-bold py-2.5 px-3 rounded-xl text-xs shadow-lg shadow-pink-500/20 transition-all active:scale-95">
                                        <i class="fas fa-check mr-1"></i> ${texts.accept}
                                    </button>
                                    <button onclick="event.stopPropagation(); respondToRequestFromNotif(${notif.source_id}, 'decline', ${notif.id})" class="flex-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold py-2.5 px-3 rounded-xl text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors active:scale-95">
                                        <i class="fas fa-times mr-1"></i> ${texts.decline}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition-colors ${unreadClass}" onclick="markAsRead(${notif.id}, '${notif.url || '#'}')">
                    <div class="flex gap-4">
                        <img src="${notif.actor_avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(notif.actor_name)}" class="w-12 h-12 rounded-full flex-shrink-0 object-cover shadow-sm">
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-2">
                                <p class="text-sm text-slate-800 dark:text-gray-200 line-clamp-2"><span class="font-bold text-slate-900 dark:text-white">${notif.actor_name}</span> ${notif.message}</p>
                                ${notif.is_read == 0 ? '<span class="w-2 h-2 rounded-full bg-pink-500 shrink-0 mt-1.5"></span>' : ''}
                            </div>
                            <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">${icon} ${timeAgo}</p>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    window.markAsRead = async function (notifId, url) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notifId);
            await fetch('api/mark_notification_read.php', { method: 'POST', body: formData });
            // Don't reload entire list, just visually update or handle redirection
            if (url && url !== '#') window.location.href = url;
            else loadNotifications();
        } catch (error) { console.error(error); }
    };

    window.respondToRequestFromNotif = async function (userId, action, notifId) {
        const langCode = (getLang() === 'en') ? 'en' : 'tr';
        const texts = t[langCode];
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', action);
            const response = await fetch('api/friend_response.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.status === 'success') {
                const formDataRead = new FormData();
                formDataRead.append('notification_id', notifId);
                await fetch('api/mark_notification_read.php', { method: 'POST', body: formDataRead });

                if (action === 'accept') alert(texts.you_are_friends);
                loadNotifications();
            } else {
                alert(data.message || texts.error);
            }
        } catch (error) {
            console.error(error);
            alert(texts.error);
        }
    };

    window.markAllRead = async function () {
        try {
            await fetch('api/mark_notification_read.php', { method: 'POST', body: new FormData() });
            loadNotifications();
        } catch (error) { console.error(error); }
    };

    window.deleteAllNotifications = async function () {
        const langCode = (getLang() === 'en') ? 'en' : 'tr';
        const texts = t[langCode];
        if (!confirm(texts.confirm_delete)) return;
        try {
            await fetch('api/delete_all_notifications.php', { method: 'POST' });
            loadNotifications();
            updateNotificationBadge(0);
        } catch (error) { console.error(error); }
    };

    // Initial Load & Interval
    // Small delay to ensure DOM is ready if script loads early
    setTimeout(() => {
        loadNotifications();
        setInterval(loadNotifications, 30000);
    }, 500);

})();
