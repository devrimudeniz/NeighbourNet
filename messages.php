<?php
require_once 'includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$active_chat_user_id = isset($_GET['to']) ? (int)$_GET['to'] : 0;
// Note: We use $active_chat_user_id to set initial state, but subsequent nav is JS-driven.

// Inbox Query
$sql_inbox = "
    SELECT 
        u.id, u.username, u.full_name, u.avatar,
        m.message, m.created_at, m.is_read, m.sender_id, m.attachment_type,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM users u
    JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?)
    AND m.id IN (
        SELECT MAX(id) FROM messages 
        WHERE sender_id = ? OR receiver_id = ? 
        GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
    )
    AND u.id != ?
    ORDER BY m.created_at DESC
";
$stmt = $pdo->prepare($sql_inbox);
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$inbox_users = $stmt->fetchAll();

// Active User Data (Server-side fetch for initial load)
$initial_chat_user = null;
if ($active_chat_user_id) {
    // If 'to' is set, verify user exists
    $u_stmt = $pdo->prepare("SELECT id, full_name, username, avatar FROM users WHERE id = ?");
    $u_stmt->execute([$active_chat_user_id]);
    $initial_chat_user = $u_stmt->fetch(PDO::FETCH_ASSOC);
}
// Check friend count for modal empty state
$stmt_fc = $pdo->prepare("SELECT COUNT(*) FROM friendships WHERE (requester_id = ? OR receiver_id = ?) AND status = 'accepted'");
$stmt_fc->execute([$user_id, $user_id]);
$friend_count = $stmt_fc->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" class="h-full <?php echo (defined('CURRENT_THEME') && CURRENT_THEME == 'dark') ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $t['messages']; ?> | Kalkan Social</title>
    <?php include 'includes/header_css.php'; ?>
    <?php require_once 'includes/icon_helper.php'; ?>
    <style>
        body { font-family: 'Outfit', sans-serif; overscroll-behavior-y: none; }
        /* Clean Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
        
        /* Mobile Slide Animation */
        .panel-transition { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .no-select { -webkit-user-select: none; user-select: none; }
        
        /* Bubble Gradients */
        .bubble-me { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .bubble-other { background: white; }
        .dark .bubble-other { background: #1e293b; }

        /* Double Tap Animation */
        @keyframes heartPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.5); opacity: 1; }
            100% { transform: scale(1); opacity: 0; }
        }
        .pop-heart { animation: heartPop 0.8s ease-out forwards; }
        
        /* Force dark background for chat area when dark mode is active */
        html.dark #chatPanel,
        html.dark #activeChatView,
        html.dark #chatContainer,
        html.dark #emptyState {
            background-color: #020617 !important; /* slate-950 */
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white h-full overflow-hidden flex flex-col">

    <?php include 'includes/header.php'; ?>

    <main class="flex-1 relative overflow-hidden pt-16 flex bg-slate-50 dark:bg-slate-950">
        
        <!-- INBOX PANEL -->
        <!-- Logic: On mobile, if chat is active, translate-x-[-100%]. Desktop always 0. -->
        <div id="inboxPanel" class="w-full md:w-80 flex flex-col border-r border-slate-200 dark:border-slate-800 absolute inset-0 md:relative z-10 bg-white dark:bg-slate-900 panel-transition md:transform-none <?php echo $active_chat_user_id ? '-translate-x-full' : 'translate-x-0'; ?>">
            <!-- Inbox Header -->
            <div class="h-16 px-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-10">
                <h1 class="text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 dark:from-white dark:to-slate-300 bg-clip-text text-transparent">Messages</h1>
                <button onclick="openNewChatModal()" class="w-9 h-9 rounded-full bg-slate-50 dark:bg-slate-800 hover:bg-green-50 dark:hover:bg-green-900/20 text-green-600 dark:text-green-400 flex items-center justify-center transition-all shadow-sm">
                    <?php echo heroicon('pencil_square', 'w-5 h-5'); ?>
                </button>
            </div>
            
            <!-- Inbox List -->
            <div class="flex-1 overflow-y-auto" id="inboxList">
                <?php if(empty($inbox_users)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-slate-500 dark:text-slate-400 p-8 text-center">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
                            <?php echo heroicon('chat_bubble_left_right', 'w-8 h-8 opacity-40'); ?>
                        </div>
                        <h3 class="font-bold mb-1 text-slate-700 dark:text-slate-200">No Messages Yet</h3>
                        <p class="text-xs opacity-70 mb-4">Find friends and start chatting!</p>
                        <button onclick="openNewChatModal()" class="text-xs font-bold text-green-600 dark:text-green-400 hover:underline">Start a Chat</button>
                    </div>
                <?php else: ?>
                    <?php foreach($inbox_users as $conv): ?>
                    <div onclick="loadChat(<?php echo $conv['id']; ?>, '<?php echo addslashes(htmlspecialchars($conv['full_name'])); ?>', '<?php echo addslashes(htmlspecialchars($conv['username'])); ?>', '<?php echo $conv['avatar']; ?>')" 
                         id="conv-<?php echo $conv['id']; ?>"
                         class="flex items-center gap-3 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-b border-slate-50 dark:border-slate-800/50 cursor-pointer active:bg-slate-100 dark:active:bg-slate-800">
                        <div class="relative shrink-0">
                            <img src="<?php echo $conv['avatar']; ?>" class="w-12 h-12 rounded-full object-cover ring-2 ring-white dark:ring-slate-800" loading="lazy">
                            <?php if($conv['unread_count'] > 0): ?>
                                <div class="absolute -top-1 -right-1 bg-gradient-to-r from-pink-500 to-rose-500 text-white text-[10px] min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full font-bold border-2 border-white dark:border-slate-900 shadow-sm">
                                    <?php echo $conv['unread_count']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline mb-1">
                                <h4 class="font-bold text-sm truncate text-slate-900 dark:text-slate-100"><?php echo htmlspecialchars($conv['full_name']); ?></h4>
                                <span class="text-[10px] text-slate-400 font-medium"><?php echo date('H:i', strtotime($conv['created_at'])); ?></span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate <?php echo $conv['unread_count'] > 0 ? 'font-bold text-slate-800 dark:text-white' : ''; ?>">
                                <?php 
                                    if($conv['sender_id'] == $user_id) echo '<span class="opacity-70">You:</span> ';
                                    if($conv['attachment_type'] == 'story_reaction') echo 'Reacted to story ' . substr($conv['message'], 0, 1);
                                    elseif($conv['attachment_type'] == 'story_reply') echo 'Replied to story';
                                    elseif($conv['attachment_type'] == 'image') echo 'Sent a photo';
                                    else echo htmlspecialchars($conv['message']); 
                                ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CHAT PANEL -->
        <!-- Fixed: Mobile relies on fixed positioning z-[60] to cover EVERYTHING (nav, header) -->
        <div id="chatPanel" class="w-full h-full flex-1 flex flex-col min-h-0 bg-slate-50 dark:bg-slate-950 absolute inset-0 md:relative z-[60] md:z-auto panel-transition md:transform-none <?php echo $active_chat_user_id ? 'translate-x-0' : 'translate-x-full'; ?>">
            
            <!-- Empty State -->
            <div id="emptyState" class="<?php echo $active_chat_user_id ? 'hidden' : 'flex'; ?> hidden md:flex flex-col items-center justify-center h-full w-full bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400">
                <div class="w-24 h-24 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center mb-6 shadow-sm border border-slate-100 dark:border-slate-700">
                    <?php echo heroicon('chat_bubble_left_right', 'w-10 h-10 opacity-30'); ?>
                </div>
                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-200">Your Messages</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 opacity-80">Send photos and messages to a friend.</p>
                <button onclick="openNewChatModal()" class="mt-4 px-6 py-2 bg-slate-900 dark:bg-slate-200 text-white dark:text-slate-900 rounded-lg text-sm font-bold transition-transform active:scale-95 hover:bg-slate-800 dark:hover:bg-slate-300">Send Message</button>
            </div>

            <!-- Active Chat View -->
            <!-- Fixed: Relative container for desktop, but on mobile it fills the fixed chatPanel -->
            <div id="activeChatView" class="<?php echo $active_chat_user_id ? 'flex' : 'hidden'; ?> flex-col h-full w-full relative bg-slate-50 dark:bg-slate-950">
                
                <!-- Chat Header -->
                <div class="h-16 px-4 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0 z-30 shadow-sm sticky top-0">
                    <div class="flex items-center gap-3">
                        <button onclick="backToInbox()" class="md:hidden -ml-2 p-2 text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors">
                            <?php echo heroicon('arrow_left', 'w-6 h-6'); ?>
                        </button>
                        <div class="relative shrink-0">
                            <!-- Fixed: Inline styles to enforce size strictly -->
                            <img id="chatAvatar" src="<?php echo $initial_chat_user['avatar'] ?? ''; ?>" style="width: 36px !important; height: 36px !important; min-width: 36px !important; min-height: 36px !important;" class="rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800 bg-slate-200">
                            <div id="onlineIndicator" class="hidden absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 rounded-full border-2 border-white dark:border-slate-900"></div>
                        </div>
                        <div class="flex flex-col justify-center overflow-hidden">
                            <h3 id="chatName" class="font-bold text-sm leading-tight text-slate-800 dark:text-slate-100 truncate"><?php echo htmlspecialchars($initial_chat_user['full_name'] ?? ''); ?></h3>
                            <span id="chatStatus" class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate">@<?php echo htmlspecialchars($initial_chat_user['username'] ?? ''); ?></span>
                        </div>
                    </div>
                    <button onclick="window.location.href='profile.php?username=<?php echo htmlspecialchars($initial_chat_user['username'] ?? ''); ?>'" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2">
                        <?php echo heroicon('information_circle', 'w-6 h-6'); ?>
                    </button>
                </div>

                <!-- Messages Container -->
                <!-- Fixed: Large bottom padding (pb-32) to ensure content clears input area -->
                <div id="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-2 bg-slate-50 dark:bg-[#020617] scroll-smooth pb-32 min-h-0">
                    <!-- Messages injected here -->
                </div>

                <!-- Input Area -->
                <!-- Fixed: Position absolute bottom-0 to stick to bottom of panel -->
                <div class="absolute bottom-0 left-0 w-full bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 p-2 z-40 pb-[env(safe-area-inset-bottom)]">
                     <!-- File Preview -->
                    <div id="filePreview" class="hidden mb-2 px-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg flex items-center justify-between border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 p-1 rounded">
                                <?php echo heroicon('image', 'w-4 h-4'); ?>
                            </span>
                            <span id="fileName" class="text-xs font-medium truncate max-w-[200px]">image.jpg</span>
                        </div>
                        <button onclick="clearFile()" class="text-slate-400 hover:text-red-500">
                            <?php echo heroicon('x_mark', 'w-4 h-4'); ?>
                        </button>
                    </div>

                    <form id="chatForm" class="flex items-end gap-2 relative pb-1">
                        <input type="hidden" id="receiverId" name="receiver_id" value="<?php echo $active_chat_user_id; ?>">
                        <input type="file" id="fileInput" name="file" accept="image/*" class="hidden" onchange="handleFileSelect(this)">
                        
                        <button type="button" onclick="document.getElementById('fileInput').click()" class="p-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors shrink-0">
                            <?php echo heroicon('image', 'w-6 h-6'); ?>
                        </button>

                        <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-3xl flex items-center px-4 min-h-[44px]">
                            <textarea id="messageInput" name="message" rows="1" class="w-full bg-transparent border-0 focus:ring-0 text-sm py-3 max-h-32 resize-none placeholder-slate-400 dark:text-white" placeholder="Message..." oninput="autoResize(this)"></textarea>
                            <button type="button" id="emojiBtn" class="ml-2 text-slate-400 hover:text-yellow-500 transition-colors shrink-0">
                                <?php echo heroicon('smile', 'w-5 h-5'); ?>
                            </button>
                        </div>

                        <button type="submit" id="sendBtn" class="p-3 bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg shadow-green-600/20 transition-transform active:scale-95 disabled:opacity-50 disabled:scale-100 shrink-0 flex items-center justify-center">
                            <?php echo heroicon('paper_plane', 'w-5 h-5 -ml-0.5 mt-0.5 transform -rotate-45'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <!-- NEW CHAT MODAL -->
    <div id="newChatModal" class="fixed inset-0 z-[100] hidden bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
        <!-- ... existing modal content preserved essentially ... -->
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl flex flex-col max-h-[80vh] animate-[fadeIn_0.2s_ease-out] overflow-hidden">
             <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900">
                <h2 class="font-bold text-lg text-slate-900 dark:text-slate-100">New Message</h2>
                <button onclick="closeNewChatModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><?php echo heroicon('x_mark', 'w-6 h-6'); ?></button>
            </div>
            <?php if ($friend_count > 0): ?>
            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                <input type="text" id="friendSearchInput" placeholder="Search people..." class="w-full bg-white dark:bg-slate-800 border-0 dark:border dark:border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-green-500 placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-slate-100 text-sm" oninput="searchFriends(this.value)">
            </div>
            <div id="friendsList" class="flex-1 overflow-y-auto p-2 bg-white dark:bg-slate-900 min-h-[300px]"></div>
            <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center min-h-[300px]">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full flex items-center justify-center mb-4">
                    <?php echo heroicon('user_plus', 'w-8 h-8'); ?>
                </div>
                <p class="text-base font-bold text-slate-700 dark:text-slate-300">
                    Mesajlaşmak için önce karşı tarafı arkadaş olarak eklemelisin.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script type="module">
        import { EmojiButton } from 'https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.4/dist/index.js';
        
        // --- State ---
        let currentChatId = <?php echo $active_chat_user_id ?: '0'; ?>;
        let lastMsgId = 0;
        let pollInterval = null;
        
        // --- DOM Elements ---
        const inboxPanel = document.getElementById('inboxPanel');
        const chatPanel = document.getElementById('chatPanel');
        const activeChatView = document.getElementById('activeChatView');
        const emptyState = document.getElementById('emptyState');
        const chatContainer = document.getElementById('chatContainer');
        const chatForm = document.getElementById('chatForm');
        
        // --- SPA Navigation ---
        
        window.loadChat = function(userId, fullName, username, avatar) {
            // Update State
            currentChatId = userId;
            document.getElementById('receiverId').value = userId;
            
            // Update UI Header
            document.getElementById('chatName').textContent = fullName;
            document.getElementById('chatStatus').textContent = '@' + username;
            document.getElementById('chatAvatar').src = avatar;
            
            // Show Chat View
            emptyState.classList.add('hidden');
            emptyState.classList.remove('flex');
            activeChatView.classList.remove('hidden');
            activeChatView.classList.add('flex');
            
            // Slide Animations (Mobile)
            inboxPanel.classList.add('-translate-x-full'); // Hide inbox to left
            inboxPanel.classList.remove('translate-x-0');
            chatPanel.classList.remove('translate-x-full'); // Show chat from right
            chatPanel.classList.add('translate-x-0');
            
            // Reset Chat
            chatContainer.innerHTML = '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-slate-200 dark:border-slate-700 border-t-green-500 rounded-full animate-spin"></div></div>';
            lastMsgId = 0;
            
            // URL History
            const url = new URL(window.location);
            url.searchParams.set('to', userId);
            window.history.pushState({chatId: userId}, '', url);
            
            // Fetch
            fetchMessages();
            startPolling();
        };

        window.backToInbox = function() {
            // Slide Animations (Mobile)
            inboxPanel.classList.remove('-translate-x-full');
            inboxPanel.classList.add('translate-x-0');
            chatPanel.classList.add('translate-x-full');
            chatPanel.classList.remove('translate-x-0');
            
            currentChatId = 0;
            stopPolling();
            
            // URL History
            const url = new URL(window.location);
            url.searchParams.delete('to');
            window.history.pushState({chatId: 0}, '', url);
        };

        // Browser Back Button Support
        window.onpopstate = function(event) {
            if (event.state && event.state.chatId) {
                // We'd ideally need fetching user data again properly or storing it in state
                // For now minimal reload to ensure data consistency if complex state lost
                window.location.reload(); 
            } else {
                backToInbox();
            }
        };

        // --- Chat Logic ---

        window.fetchMessages = async function() {
            if(!currentChatId) return;
            try {
                const res = await fetch(`api/chat_fetch.php?partner_id=${currentChatId}&after_id=${lastMsgId}`);
                const data = await res.json();
                
                // Clear spinner on first load
                if(lastMsgId === 0) chatContainer.innerHTML = '';
                
                if (data.length > 0) {
                    data.forEach(msg => {
                        appendMessage(msg);
                        lastMsgId = Math.max(lastMsgId, msg.id);
                    });
                    scrollToBottom();
                } else if (lastMsgId === 0) {
                    chatContainer.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-64 opacity-50 space-y-2 text-slate-600 dark:text-slate-400">
                             <img src="${document.getElementById('chatAvatar').src}" class="w-16 h-16 rounded-full opacity-50 grayscale">
                             <p class="text-sm">Send a message to start chatting.</p>
                        </div>`;
                }
            } catch (e) { console.error(e); }
        };

        function appendMessage(msg) {
            const isMe = msg.is_me;
            const bubbleClass = isMe 
                ? 'bubble-me text-white rounded-2xl rounded-tr-sm' 
                : 'bubble-other text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-sm border border-slate-100 dark:border-slate-800 shadow-sm';
            
            const alignClass = isMe ? 'justify-end' : 'justify-start';
            
            let content = '';
            
            // Double Tap Handler Wrapper
            const div = document.createElement('div');
            div.className = `flex ${alignClass} mb-2 relative group no-select`;
            
            // Reaction Display
            let reactionHtml = '';
            if (msg.my_reaction) {
                const rIcons = { 'like': '❤️', 'love': '❤️', 'haha': '😂', 'wow': '😮', 'sad': '😢', 'angry': '😡' };
                const icon = rIcons[msg.my_reaction] || '❤️';
                reactionHtml = `
                    <div class="reaction-icon absolute -bottom-2 ${isMe ? 'right-2' : 'left-2'} bg-white dark:bg-slate-800 rounded-full border border-slate-100 dark:border-slate-700 shadow-sm w-6 h-6 flex items-center justify-center text-xs animate-[scaleIn_0.2s_ease-out] z-10">
                        ${icon}
                    </div>
                `;
            }

            // Message Content
            let innerContent = '';
            if (msg.is_deleted) {
                innerContent = `<span class="italic opacity-60 text-xs">Message deleted</span>`;
            } else {
                if(msg.attachment_url) {
                    innerContent += `<img src="${msg.attachment_url}" class="rounded-lg mb-1 max-w-full max-h-60 object-cover">`;
                }
                if(msg.message) {
                    innerContent += `<p class="whitespace-pre-wrap leading-relaxed break-words text-[15px]">${msg.message}</p>`;
                }
            }

            const time = `<span class="text-[10px] opacity-60 block mt-1 ${isMe ? 'text-green-100' : 'text-slate-400 dark:text-slate-500'} text-right">${msg.time}</span>`;
            
            div.innerHTML = `
                <div class="max-w-[75%] relative">
                    <div class="${bubbleClass} px-4 py-2 relative" 
                         ondblclick="handleReaction(${msg.id}, 'love', this)">
                         ${innerContent}
                         ${time}
                         
                         <!-- Like Heart Animation Container -->
                         <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                             <i class="fas fa-heart text-white text-4xl opacity-0 transition-transform" id="pop-heart-${msg.id}"></i>
                         </div>
                    </div>
                    ${reactionHtml}
                </div>
            `;
            
            chatContainer.appendChild(div);
        }

        window.handleReaction = async function(msgId, type, bubbleEl) {
            // Optimistic UI - Heart Pop
            const heart = document.getElementById(`pop-heart-${msgId}`);
            if(heart) {
                heart.classList.add('pop-heart');
                setTimeout(() => heart.classList.remove('pop-heart'), 1000);
            }
            
            // API Request
            const formData = new FormData();
            formData.append('action', 'react');
            formData.append('message_id', msgId);
            formData.append('reaction_type', type);
            
            const res = await fetch('api/chat_action.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.status === 'success') {
                 const wrapper = bubbleEl.parentElement;
                 let reactionDiv = wrapper.querySelector('.reaction-icon');
                 
                 if (data.action === 'added' || data.action === 'updated') {
                     const rIcons = { 'like': '❤️', 'love': '❤️', 'haha': '😂', 'wow': '😮', 'sad': '😢', 'angry': '😡' };
                     const iconChar = rIcons[type] || '❤️';
                     
                     if (!reactionDiv) {
                         const isMe = bubbleEl.classList.contains('bubble-me');
                         reactionDiv = document.createElement('div');
                         reactionDiv.className = `reaction-icon absolute -bottom-2 ${isMe ? 'right-2' : 'left-2'} bg-white dark:bg-slate-800 rounded-full border border-slate-100 dark:border-slate-700 shadow-sm w-6 h-6 flex items-center justify-center text-xs animate-[scaleIn_0.2s_ease-out] z-10`;
                         wrapper.appendChild(reactionDiv);
                     }
                     reactionDiv.innerHTML = iconChar;
                 } else if (data.action === 'removed') {
                     if (reactionDiv) reactionDiv.remove();
                 }
            }
        };

        // --- Utilities ---
        
        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        
        function startPolling() {
            if(pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(fetchMessages, 3000);
        }
        
        function stopPolling() {
            if(pollInterval) clearInterval(pollInterval);
        }
        
        window.autoResize = function(el) {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        };

        window.handleFileSelect = function(input) {
            if (input.files.length > 0) {
                document.getElementById('filePreview').classList.remove('hidden');
                document.getElementById('fileName').textContent = input.files[0].name;
                document.getElementById('filePreview').classList.add('flex');
            }
        };

        window.clearFile = function() {
            document.getElementById('fileInput').value = '';
            document.getElementById('filePreview').classList.add('hidden');
            document.getElementById('filePreview').classList.remove('flex');
        };

        // Send Message
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(chatForm);
            
            // Optimistic clear
            const msgInput = document.getElementById('messageInput');
            msgInput.value = '';
            msgInput.style.height = 'auto';
            clearFile();
            
            try {
                await fetch('api/chat_send.php', { method: 'POST', body: formData });
                fetchMessages();
            } catch (e) { console.error(e); }
        });

        // Initialize
        if(currentChatId) {
            startPolling();
            fetchMessages();
            // Scroll bottom initially
            setTimeout(scrollToBottom, 100);
        }
        
        // Emoji Picker
        const picker = new EmojiButton({
            position: 'top-start',
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        });
        const trigger = document.getElementById('emojiBtn');
        const input = document.getElementById('messageInput');

        picker.on('emoji', selection => {
            input.value += selection.emoji;
        });

        trigger.addEventListener('click', () => picker.togglePicker(trigger));
        
        // Modal Logic
        window.openNewChatModal = () => document.getElementById('newChatModal').classList.remove('hidden');
        window.closeNewChatModal = () => document.getElementById('newChatModal').classList.add('hidden');
        
        window.searchFriends = async function(query) {
             const list = document.getElementById('friendsList');
             if(!query) { list.innerHTML = ''; return; }
             const res = await fetch(`api/search_friends.php?q=${query}`);
             const data = await res.json();
             if(data.status === 'success') {
                 list.innerHTML = data.friends.map(f => `
                    <div onclick="closeNewChatModal(); loadChat(${f.id}, '${f.full_name}', '${f.username}', '${f.avatar}')" class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer text-slate-900 dark:text-slate-100">
                        <img src="${f.avatar}" class="w-10 h-10 rounded-full object-cover">
                        <div><div class="font-bold text-sm">${f.full_name}</div><div class="text-xs text-slate-500 dark:text-slate-400">@${f.username}</div></div>
                    </div>
                 `).join('');
             }
        };

    </script>
</body>
</html>
