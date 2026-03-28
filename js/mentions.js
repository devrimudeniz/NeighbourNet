/**
 * Kalkan Social - Mention Autocomplete System
 * Handles @username suggestions in comment inputs
 */

let mentionState = {
    active: false,
    query: '',
    startIndex: 0,
    input: null
};

// Debug logger
const debugLog = (msg, data) => {
    console.log(`[MentionSystem] ${msg}`, data || '');
};

document.addEventListener('input', function (e) {
    // Matches feed inputs AND post_detail input (which is input type=text, not textarea)
    // feed.php IDs: comment-input-{postId}
    // post_detail.php ID: comment-input
    if (e.target.matches('input[id^="comment-input-"]') || e.target.matches('input[id="comment-input"]')) {
        handleMentionInput(e.target);
    }
});

document.addEventListener('keydown', function (e) {
    if ((e.target.matches('input[id^="comment-input-"]') || e.target.matches('input[id="comment-input"]')) && mentionState.active) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === 'Escape') {
            handleMentionKeydown(e);
        }
    }
});

document.addEventListener('click', function (e) {
    if (!e.target.closest('#mention-dropdown')) {
        closeMentionDropdown();
    }
});

function handleMentionInput(input) {
    const cursor = input.selectionStart;
    const text = input.value;
    const textBeforeCursor = text.slice(0, cursor);

    // Regex to find @mention being typed (at end of string or after space)
    // Allows Turkish characters and underscores
    const match = textBeforeCursor.match(/(?:\s|^)@([a-zA-Z0-9_ğüşıöçĞÜŞİÖÇ]*)$/);

    if (match) {
        debugLog('Mention detected:', match[1]);
        mentionState.active = true;
        // match[1] checks the capture group which is the username part
        mentionState.query = match[1];

        // Accurate start index (position of @)
        const atIndex = textBeforeCursor.lastIndexOf('@');
        mentionState.startIndex = atIndex;
        mentionState.input = input;

        fetchMentions(mentionState.query);
    } else {
        closeMentionDropdown();
    }
}

async function fetchMentions(query) {
    // debugLog('Fetching for:', query);

    try {
        const res = await fetch(`api/search_mentions.php?q=${query}`);
        const data = await res.json();

        if (data.status === 'success' && data.users.length > 0) {
            showMentionDropdown(data.users);
        } else {
            closeMentionDropdown();
        }
    } catch (e) {
        console.error('Mention search error:', e);
    }
}

function showMentionDropdown(users) {
    const dropdown = document.getElementById('mention-dropdown');
    if (!dropdown) {
        debugLog('Dropdown element not found!');
        return;
    }

    const input = mentionState.input;
    const rect = input.getBoundingClientRect();

    dropdown.innerHTML = users.map(u => `
        <div class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer transition-colors border-b border-slate-50 dark:border-slate-700 last:border-0" 
             onclick="selectMention('${u.username}')">
            <img src="${u.avatar}" class="w-8 h-8 rounded-full object-cover">
            <div class="flex flex-col">
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">${u.full_name}</span>
                <span class="text-xs text-slate-400">@${u.username}</span>
            </div>
            ${u.badge === 'business' ? '<i class="fas fa-check-circle text-green-500 text-xs ml-auto"></i>' : ''}
        </div>
    `).join('');

    // Improved Positioning
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    // Mobile friendly: if near bottom, show above
    const spaceBelow = window.innerHeight - rect.bottom;

    dropdown.style.left = `${rect.left}px`;
    dropdown.style.width = Math.max(rect.width, 250) + 'px'; // At least input width or 250px

    if (spaceBelow < 300) {
        // Show above
        dropdown.style.top = 'auto';
        dropdown.style.bottom = `${window.innerHeight - rect.top - scrollTop + 5}px`;
    } else {
        // Show below
        // For fixed positioning, we use rect.bottom relative to viewport if no parent transform
        // But wait, if page scrolls? Fixed position stays on screen.
        // Absolute position with body relative?
        // The dropdown is "fixed" in CSS. So it follows viewport.
        dropdown.style.top = `${rect.bottom + 5}px`;
        dropdown.style.bottom = 'auto';
    }

    dropdown.classList.remove('hidden');
}

function closeMentionDropdown() {
    const dropdown = document.getElementById('mention-dropdown');
    if (!dropdown) return;
    dropdown.classList.add('hidden');
    dropdown.innerHTML = '';
    mentionState.active = false;
}

function selectMention(username) {
    const input = mentionState.input;
    const text = input.value;

    // We used lastIndexOf to find @, so we replace from there to cursor
    const before = text.slice(0, mentionState.startIndex);
    const after = text.slice(input.selectionStart);

    const newText = `${before}@${username} ${after}`;
    input.value = newText;
    input.focus();
    closeMentionDropdown();
}

function handleMentionKeydown(e) {
    if (e.key === 'Escape') closeMentionDropdown();
    // Future: Arrow keys navigation
}
