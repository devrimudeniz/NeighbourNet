document.addEventListener('DOMContentLoaded', () => {
    // Shared Logic
    const initSearch = (inputId, dropdownId, resultsId) => {
        const searchInput = document.getElementById(inputId);
        const searchDropdown = document.getElementById(dropdownId);
        const searchResults = document.getElementById(resultsId);

        if (!searchInput || !searchDropdown) return;

        let debounceTimer;
        // Icon is usually a sibling in the same relative container
        const searchIcon = searchInput.parentElement.querySelector('.fa-search');

        const toggleIcon = () => {
            if (searchIcon) {
                const hasValue = searchInput.value.trim().length > 0;
                if (hasValue) {
                    searchIcon.classList.add('hidden');
                    searchIcon.style.display = 'none';
                    searchIcon.style.opacity = '0';
                    searchIcon.style.visibility = 'hidden';
                } else {
                    searchIcon.classList.remove('hidden');
                    searchIcon.style.display = '';
                    searchIcon.style.opacity = '';
                    searchIcon.style.visibility = '';
                }
            }
        };

        // Listen for all possible change events
        ['focus', 'input', 'keyup', 'change'].forEach(evt => {
            searchInput.addEventListener(evt, toggleIcon);
        });

        // Show dropdown on focus
        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim() === '') {
                loadDefaultSearch(searchDropdown, searchResults);
            } else {
                searchDropdown.classList.remove('hidden');
            }
        });

        // Hide delayed
        searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                searchDropdown.classList.add('hidden');
            }, 200);
        });

        // Typing logic (existing)
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();
            if (query.length === 0) {
                loadDefaultSearch(searchDropdown, searchResults);
                return;
            }
            debounceTimer = setTimeout(() => {
                performSearch(query, searchDropdown, searchResults);
            }, 300);
        });

        // Initial check
        toggleIcon();
    };


    // Initialize Desktop
    initSearch('global-search', 'search-dropdown', 'search-results');
    // Initialize Mobile
    initSearch('mobile-search', 'mobile-search-dropdown', 'mobile-search-results');


    async function loadDefaultSearch(dropdown, resultsContainer) {
        try {
            const res = await fetch('api/smart_search.php');
            const data = await res.json();
            renderDefault(data, resultsContainer);
            dropdown.classList.remove('hidden');
        } catch (e) { console.error(e); }
    }

    async function performSearch(query, dropdown, resultsContainer) {
        try {
            const res = await fetch(`api/smart_search.php?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            renderResults(data.results, query, resultsContainer);
            dropdown.classList.remove('hidden');
        } catch (e) { console.error(e); }
    }

    function renderDefault(data, container) {
        let html = '';

        // Recent History
        if (data.history && data.history.length > 0) {
            html += `<div class="p-3 pb-0"><h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Son Aramalar</h4></div>`;
            data.history.forEach(u => {
                html += `
                <div class="flex items-center justify-between px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer group">
                    <a href="profile?uid=${u.id}" class="flex items-center gap-3 flex-1" onclick="logSearch(${u.id})">
                        <img src="${u.avatar}" class="w-8 h-8 rounded-full object-cover">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">${u.full_name}</span>
                            <span class="text-xs text-slate-400">@${u.username}</span>
                        </div>
                    </a>
                    <button onclick="deleteHistory(${u.history_id}, event)" class="text-slate-300 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
            });
        }

        // Suggestions
        if (data.suggestions && data.suggestions.length > 0) {
            html += `<div class="p-3 pb-0 mt-2 border-t border-slate-100 dark:border-slate-700"><h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Bunları Tanıyor Olabilirsin</h4></div>`;
            data.suggestions.forEach(u => {
                html += `
                <a href="profile?uid=${u.id}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors" onclick="logSearch(${u.id})">
                    <img src="${u.avatar}" class="w-10 h-10 rounded-full object-cover ring-2 ring-white dark:ring-slate-800">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200">${u.full_name}</span>
                            ${u.badge ? '<i class="fas fa-check-circle text-blue-500 text-xs"></i>' : ''}
                        </div>
                        <span class="text-xs text-slate-400">@${u.username}</span>
                    </div>
                </a>`;
            });
        }

        if (!html) html = '<div class="p-4 text-center text-sm text-slate-400">Arama yapmak için yazmaya başla...</div>';
        container.innerHTML = html;
    }

    function renderResults(results, query, container) {
        let html = '';
        if (results && results.length > 0) {
            results.forEach(item => {
                if (item.type === 'post') {
                    // Render Post Result
                    html += `
                    <a href="post_detail.php?id=${item.id}" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer border-b border-slate-50 dark:border-slate-800 last:border-0 group">
                        <div class="w-10 h-10 shrink-0">
                             <img src="${item.user.avatar}" class="w-full h-full rounded-full object-cover">
                        </div>
                        <div class="flex flex-col flex-1 min-w-0">
                            <div class="flex items-center gap-1 mb-0.5">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">${item.user.full_name}</span>
                                <span class="text-[10px] text-slate-400">@${item.user.username} · Gönderi</span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-200 line-clamp-2 highlightable font-medium">
                                ${item.content}
                            </p>
                        </div>
                    </a>`;
                } else {
                    // Render User Result (Default)
                    html += `
                    <a href="profile?uid=${item.id}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer border-b border-slate-50 dark:border-slate-800 last:border-0" onclick="logSearch(${item.id})">
                        <img src="${item.avatar}" class="w-10 h-10 rounded-full object-cover">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200 highlightable">${item.full_name}</span>
                            <span class="text-xs text-slate-400">@${item.username}</span>
                        </div>
                    </a>`;
                }
            });
        } else {
            html = `<div class="p-4 text-center text-sm text-slate-400">"${query}" için sonuç bulunamadı.</div>`;
        }
        container.innerHTML = html;

        // Highlight logic (Visual enhancement)
        const regex = new RegExp(`(${query})`, 'gi');
        container.querySelectorAll('.highlightable').forEach(el => {
            el.innerHTML = el.innerText.replace(regex, '<span class="text-pink-500 bg-pink-50 dark:bg-pink-900/30">$1</span>');
        });
    }

    window.logSearch = async function (searchedId) {
        // Optimistic, don't wait
        try {
            const formData = new FormData();
            formData.append('action', 'log');
            formData.append('searched_user_id', searchedId);
            fetch('api/smart_search.php', { method: 'POST', body: formData });
        } catch (e) { }
    };

    window.deleteHistory = async function (historyId, e) {
        e.stopPropagation(); // prevent navigation
        if (!confirm('Gecmişten silinsin mi?')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', historyId);
            const res = await fetch('api/smart_search.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                // Refresh both views if open
                // Simply re-trigger focus logic or let next focus handle it
                // Ideally reloadDefaultSearch for active one, but strict sync not critical
            }
        } catch (e) { }
    };
});
