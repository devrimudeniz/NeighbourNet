/**
 * Kalkan Social - SPA Engine
 * Lightweight Single Page Application using Vanilla JavaScript & HTML5 History API
 * PWA Compatible - Works with Service Workers
 * 
 * @version 1.0
 * @author Kalkan Social Team
 */

(function () {
    'use strict';

    // ============================================
    // CONFIGURATION
    // ============================================
    const SPA = {
        contentSelector: '#spa-content',      // Main content container
        loadingBarId: 'spa-loading-bar',      // Loading bar element ID
        excludePatterns: [                     // Links to exclude from SPA
            /^https?:\/\//,                   // External links (absolute URLs)
            /\.(pdf|zip|doc|docx|xls|xlsx)$/i, // File downloads
            /^mailto:/,                        // Email links
            /^tel:/,                           // Phone links
            /^#/,                              // Anchor links
            /\/api\//,                         // API calls
            /\/admin\//,                       // Admin pages (optional)
            /logout/,                          // Logout (needs full reload)
            /login/,                           // Login page
            /register/                         // Register page
        ],
        transitionDuration: 300,               // Transition duration in ms
        scrollToTop: true,                     // Scroll to top on navigation
        cacheEnabled: true,                    // Enable response caching
        cacheExpiry: 60000,                    // Cache expiry in ms (1 minute)
        debug: false                           // Debug mode
    };

    // Page cache
    const pageCache = new Map();

    // Current page state
    let currentPath = window.location.pathname;
    let isNavigating = false;

    // ============================================
    // LOADING BAR
    // ============================================
    function createLoadingBar() {
        if (document.getElementById(SPA.loadingBarId)) return;

        const bar = document.createElement('div');
        bar.id = SPA.loadingBarId;
        bar.innerHTML = `
            <style>
                #${SPA.loadingBarId} {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 0%;
                    height: 3px;
                    background: linear-gradient(90deg, #ec4899, #8b5cf6, #06b6d4);
                    z-index: 99999;
                    transition: width 0.3s ease;
                    box-shadow: 0 0 10px rgba(236, 72, 153, 0.5);
                }
                #${SPA.loadingBarId}.loading {
                    animation: spa-loading 1.5s ease-in-out infinite;
                }
                #${SPA.loadingBarId}.complete {
                    width: 100% !important;
                    opacity: 0;
                    transition: width 0.2s ease, opacity 0.3s ease 0.2s;
                }
                @keyframes spa-loading {
                    0% { width: 0%; }
                    20% { width: 25%; }
                    50% { width: 60%; }
                    80% { width: 85%; }
                    100% { width: 90%; }
                }
            </style>
        `;
        document.body.appendChild(bar);
    }

    function showLoading() {
        const bar = document.getElementById(SPA.loadingBarId);
        if (bar) {
            bar.classList.remove('complete');
            bar.classList.add('loading');
            bar.style.width = '0%';
            bar.style.opacity = '1';
        }
    }

    function hideLoading() {
        const bar = document.getElementById(SPA.loadingBarId);
        if (bar) {
            bar.classList.remove('loading');
            bar.classList.add('complete');
            setTimeout(() => {
                bar.style.width = '0%';
                bar.classList.remove('complete');
            }, 500);
        }
    }

    // ============================================
    // NAVIGATION HELPERS
    // ============================================
    function shouldHandleLink(link) {
        // Check if it's a valid anchor element
        if (!link || !link.href) return false;

        // Check for data-spa-ignore attribute
        if (link.hasAttribute('data-spa-ignore')) return false;

        // Check for target="_blank"
        if (link.target === '_blank') return false;

        // Check for download attribute
        if (link.hasAttribute('download')) return false;

        const href = link.getAttribute('href');
        if (!href) return false;

        // Check against exclude patterns
        for (const pattern of SPA.excludePatterns) {
            if (pattern.test(href)) return false;
        }

        // Check if same origin
        try {
            const url = new URL(link.href);
            if (url.origin !== window.location.origin) return false;
        } catch (e) {
            return false;
        }

        return true;
    }

    function getPathFromUrl(url) {
        try {
            const urlObj = new URL(url, window.location.origin);
            return urlObj.pathname + urlObj.search;
        } catch (e) {
            return url;
        }
    }

    // ============================================
    // CONTENT LOADING
    // ============================================
    async function loadContent(path, pushState = true) {
        if (isNavigating) return;
        isNavigating = true;

        const contentContainer = document.querySelector(SPA.contentSelector);
        if (!contentContainer) {
            console.error('[SPA] Content container not found:', SPA.contentSelector);
            window.location.href = path;
            return;
        }

        // Check cache first
        if (SPA.cacheEnabled && pageCache.has(path)) {
            const cached = pageCache.get(path);
            if (Date.now() - cached.timestamp < SPA.cacheExpiry) {
                applyContent(cached.html, cached.title, path, pushState);
                isNavigating = false;
                return;
            } else {
                pageCache.delete(path);
            }
        }

        showLoading();

        try {
            // Fade out current content
            contentContainer.style.opacity = '0.5';
            contentContainer.style.transform = 'translateY(10px)';
            contentContainer.style.transition = `opacity ${SPA.transitionDuration / 2}ms ease, transform ${SPA.transitionDuration / 2}ms ease`;

            // Fetch new content with SPA header
            const response = await fetch(path, {
                method: 'GET',
                headers: {
                    'X-SPA-Request': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const html = await response.text();

            // Extract title from response if available
            let title = document.title;
            const titleMatch = html.match(/<title[^>]*>([^<]+)<\/title>/i);
            if (titleMatch) {
                title = titleMatch[1];
            }

            // Check for redirect (PHP might return a redirect header or meta)
            const redirectMatch = html.match(/window\.location\s*=\s*['"]([^'"]+)['"]/);
            if (redirectMatch) {
                window.location.href = redirectMatch[1];
                return;
            }

            // Cache the response
            if (SPA.cacheEnabled) {
                pageCache.set(path, {
                    html: html,
                    title: title,
                    timestamp: Date.now()
                });
            }

            applyContent(html, title, path, pushState);

        } catch (error) {
            console.error('[SPA] Navigation error:', error);

            // Fallback to traditional navigation
            window.location.href = path;

        } finally {
            hideLoading();
            isNavigating = false;
        }
    }

    function applyContent(html, title, path, pushState) {
        const contentContainer = document.querySelector(SPA.contentSelector);
        if (!contentContainer) return;

        // Parse HTML and extract only the main content
        // This prevents duplicate headers when response includes full page
        let contentHtml = extractMainContent(html);

        // Update content
        contentContainer.innerHTML = contentHtml;

        // Fade in new content
        requestAnimationFrame(() => {
            contentContainer.style.opacity = '1';
            contentContainer.style.transform = 'translateY(0)';
        });

        // Update title
        if (title) {
            document.title = title;
        }

        // Update browser history
        if (pushState && path !== currentPath) {
            history.pushState({ path: path, title: title }, title, path);
        }
        currentPath = path;

        // Scroll to top
        if (SPA.scrollToTop) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Re-initialize scripts in new content
        reinitializeScripts();

        // Dispatch custom event for other scripts to listen
        window.dispatchEvent(new CustomEvent('spa:loaded', {
            detail: { path: path, title: title }
        }));

        // Update active nav links
        updateActiveNavLinks(path);

        if (SPA.debug) {
            console.log('[SPA] Navigated to:', path);
        }
    }

    /**
     * Extract main content from HTML response
     * Handles both SPA-only responses and full page responses
     */
    function extractMainContent(html) {
        // If response is already clean (no DOCTYPE), return as-is
        if (!html.includes('<!DOCTYPE') && !html.includes('<html')) {
            return html;
        }

        // Create a temporary DOM to parse the HTML
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Try to find #spa-content first
        let content = doc.querySelector('#spa-content');
        if (content) {
            return content.innerHTML;
        }

        // Try to find main element
        content = doc.querySelector('main');
        if (content) {
            return content.outerHTML;
        }

        // Try to find .container as fallback
        content = doc.querySelector('.container');
        if (content) {
            return content.outerHTML;
        }

        // Last resort: return body content without header/footer
        const body = doc.body;
        if (body) {
            // Remove header, nav, and footer elements
            const header = body.querySelector('header');
            const footer = body.querySelector('footer');
            const nav = body.querySelector('nav.fixed, nav[class*="bottom"]');

            if (header) header.remove();
            if (footer) footer.remove();
            if (nav) nav.remove();

            // Also remove the loading bar if present
            const loadingBar = body.querySelector('#spa-loading-bar');
            if (loadingBar) loadingBar.remove();

            return body.innerHTML;
        }

        // Absolute fallback
        return html;
    }

    // ============================================
    // SCRIPT RE-INITIALIZATION
    // ============================================
    function reinitializeScripts() {
        const contentContainer = document.querySelector(SPA.contentSelector);
        if (!contentContainer) return;

        // Find and execute inline scripts
        const scripts = contentContainer.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');

            // Copy attributes
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });

            // Copy content
            newScript.textContent = oldScript.textContent;

            // Replace old script with new one to execute it
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        // Re-initialize lazy loading images
        if ('loading' in HTMLImageElement.prototype) {
            // Native lazy loading supported
        } else if (typeof LazyLoad !== 'undefined') {
            new LazyLoad();
        }
    }

    // ============================================
    // ACTIVE NAV LINKS
    // ============================================
    function updateActiveNavLinks(path) {
        // Remove .php extension for comparison
        const cleanPath = path.replace(/\.php$/, '').replace(/\?.*$/, '');

        document.querySelectorAll('nav a, .nav-link, [data-nav]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;

            const cleanHref = href.replace(/\.php$/, '').replace(/\?.*$/, '');

            if (cleanHref === cleanPath || (cleanPath === '/' && cleanHref === '/index')) {
                link.classList.add('active', 'text-pink-500');
            } else {
                link.classList.remove('active', 'text-pink-500');
            }
        });
    }

    // ============================================
    // EVENT HANDLERS
    // ============================================
    function handleClick(event) {
        // Find the closest anchor element
        const link = event.target.closest('a');

        if (!link || !shouldHandleLink(link)) return;

        event.preventDefault();

        const path = getPathFromUrl(link.href);

        // Don't navigate to same page
        if (path === currentPath) {
            if (SPA.scrollToTop) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            return;
        }

        loadContent(path);
    }

    function handlePopState(event) {
        const path = event.state?.path || window.location.pathname;
        loadContent(path, false);
    }

    // ============================================
    // FORM HANDLING (Optional)
    // ============================================
    function handleFormSubmit(event) {
        const form = event.target;

        // Only handle GET forms with data-spa attribute
        if (form.method.toUpperCase() !== 'GET' || !form.hasAttribute('data-spa')) {
            return;
        }

        event.preventDefault();

        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        const path = form.action + (params ? '?' + params : '');

        loadContent(getPathFromUrl(path));
    }

    // ============================================
    // PWA INTEGRATION
    // ============================================
    function setupPWA() {
        // Listen for service worker messages
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.addEventListener('message', event => {
                if (event.data.type === 'CACHE_UPDATED') {
                    // Optionally refresh content
                    if (SPA.debug) {
                        console.log('[SPA] Cache updated by service worker');
                    }
                }
            });
        }

        // Handle online/offline events
        window.addEventListener('online', () => {
            document.body.classList.remove('is-offline');
            // Clear stale cache on reconnection
            pageCache.clear();
        });

        window.addEventListener('offline', () => {
            document.body.classList.add('is-offline');
        });
    }

    // ============================================
    // PREFETCHING (Performance Optimization)
    // ============================================
    function setupPrefetching() {
        // Prefetch on hover for faster navigation
        document.addEventListener('mouseover', event => {
            const link = event.target.closest('a');
            if (!link || !shouldHandleLink(link)) return;

            const path = getPathFromUrl(link.href);

            // Don't prefetch if already cached
            if (pageCache.has(path)) return;

            // Prefetch with low priority
            const prefetchLink = document.createElement('link');
            prefetchLink.rel = 'prefetch';
            prefetchLink.href = path;
            document.head.appendChild(prefetchLink);
        }, { passive: true });
    }

    // ============================================
    // INITIALIZATION
    // ============================================
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        // Check if content container exists
        const contentContainer = document.querySelector(SPA.contentSelector);
        if (!contentContainer) {
            console.warn('[SPA] Content container not found. SPA disabled.');
            return;
        }

        // Create loading bar
        createLoadingBar();

        // Set up event listeners
        document.addEventListener('click', handleClick);
        window.addEventListener('popstate', handlePopState);
        document.addEventListener('submit', handleFormSubmit);

        // Set initial history state
        history.replaceState({ path: currentPath, title: document.title }, document.title, currentPath);

        // Update active nav links on init
        updateActiveNavLinks(currentPath);

        // PWA setup
        setupPWA();

        // Prefetching setup
        setupPrefetching();

        if (SPA.debug) {
            console.log('[SPA] Initialized successfully');
        }

        // Dispatch ready event
        window.dispatchEvent(new CustomEvent('spa:ready'));
    }

    // ============================================
    // PUBLIC API
    // ============================================
    window.SPA = {
        navigate: (path) => loadContent(path),
        refresh: () => loadContent(currentPath, false),
        clearCache: () => pageCache.clear(),
        getCache: () => pageCache,
        config: SPA
    };

    // Start
    init();

})();
