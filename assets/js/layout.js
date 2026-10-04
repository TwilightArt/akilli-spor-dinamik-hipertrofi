/**
 * ============================================================================
 * AkÄ±llÄ± Spor & Dinamik Hipertrofi Platformu (v2.1)
 * Shared Layout & Mobile Navigation Bar Component (assets/js/layout.js)
 * Automatically injects header & mobile bottom bar into HTML pages
 * ============================================================================
 */

(function (window) {
    'use strict';

    const Layout = {
        init() {
            this.renderHeader();
            this.renderBottomNav();
            this.setupOfflineListener();
            this.setupServiceWorker();
        },

        getCurrentPage() {
            const path = window.location.pathname.toLowerCase();
            if (path.includes('live')) return 'live';
            if (path.includes('analytics') || path.includes('progress')) return 'analytics';
            if (path.includes('profile')) return 'profile';
            if (path.includes('weigh-in') || path.includes('weigh_in')) return 'weigh-in';
            if (path.includes('calculator') || path.includes('1rm') || path.includes('tdee')) return 'calculators';
            if (path.includes('dashboard')) return 'dashboard';
            return 'dashboard';
        },

        /**
         * Render Top Header into <div id="app-header"></div>
         */
        renderHeader(user = null) {
            const headerContainer = document.getElementById('app-header');
            if (!headerContainer) return;

            const currentUser = user || (window.API && window.API.auth.getCurrentUser()) || null;
            const userName = currentUser ? (currentUser.name || 'Sporcu') : 'Sporcu';
            const currentPage = this.getCurrentPage();

            
            // DOM API ile güvenli Header oluşturma
            headerContainer.textContent = '';
            const header = document.createElement('header');
            header.className = 'sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80';
            
            const div1 = document.createElement('div');
            div1.className = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
            header.appendChild(div1);
            
            const div2 = document.createElement('div');
            div2.className = 'flex items-center justify-between h-16';
            div1.appendChild(div2);
            
            const logo = document.createElement('a');
            logo.href = '/dashboard.html';
            logo.className = 'flex items-center gap-3 group';
            
            const logoIcon = document.createElement('div');
            logoIcon.className = 'w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-orange-500 flex items-center justify-center text-white shadow-lg shadow-rose-600/30 group-hover:scale-105 transition-transform';
            const icon = document.createElement('i');
            icon.className = 'fa-solid fa-bolt-lightning text-lg';
            logoIcon.appendChild(icon);
            logo.appendChild(logoIcon);
            
            const logoText = document.createElement('div');
            logoText.className = 'flex flex-col';
            const logoTextTop = document.createElement('div');
            logoTextTop.className = 'flex items-center gap-1.5';
            const span1 = document.createElement('span');
            span1.className = 'font-extrabold tracking-tight text-white font-display text-base';
            span1.textContent = 'AKILLI SPOR';
            const span2 = document.createElement('span');
            span2.className = 'text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-400 border border-rose-500/30 uppercase';
            span2.textContent = 'v2.1';
            logoTextTop.appendChild(span1);
            logoTextTop.appendChild(span2);
            logoText.appendChild(logoTextTop);
            
            const span3 = document.createElement('span');
            span3.className = 'text-[10px] text-zinc-400 font-medium tracking-wide uppercase';
            span3.textContent = 'Dinamik Hipertrofi';
            logoText.appendChild(span3);
            logo.appendChild(logoText);
            div2.appendChild(logo);
            
            const nav = document.createElement('nav');
            nav.className = 'hidden lg:flex items-center gap-1';
            
            const links = [
                { href: '/dashboard.html', icon: 'fa-house', text: 'Panel', id: 'dashboard' },
                { href: '/live.html', icon: 'fa-dumbbell', text: 'Antrenman', id: 'live' },
                { href: '/analytics.html', icon: 'fa-chart-simple', text: 'Analiz', id: 'analytics' },
                { href: '/weigh-in.html', icon: 'fa-weight-scale', text: 'Tartı', id: 'weigh-in' },
                { href: '/calculators/tdee.html', icon: 'fa-calculator', text: 'Hesaplayıcı', id: 'calculators' },
                { href: '/profile.html', icon: 'fa-user', text: 'Profil', id: 'profile' }
            ];
            
            links.forEach(l => {
                const a = document.createElement('a');
                a.href = l.href;
                a.className = 'px-3 py-2 rounded-xl text-sm font-medium transition-all ' + (currentPage === l.id ? 'bg-rose-500/10 text-rose-400 font-semibold' : 'text-zinc-400 hover:text-white hover:bg-zinc-900');
                const i = document.createElement('i');
                i.className = 'fa-solid ' + l.icon + ' mr-1.5 text-xs';
                a.appendChild(i);
                a.appendChild(document.createTextNode(' ' + l.text));
                nav.appendChild(a);
            });
            div2.appendChild(nav);
            
            const userDiv = document.createElement('div');
            userDiv.className = 'flex items-center gap-3';
            
            const userInfo = document.createElement('div');
            userInfo.className = 'hidden sm:flex items-center gap-2 pl-3 border-l border-zinc-800';
            const userInitial = document.createElement('div');
            userInitial.id = 'header-user-initial';
            userInitial.className = 'w-8 h-8 rounded-full bg-zinc-800 text-rose-400 flex items-center justify-center font-bold text-xs border border-zinc-700';
            userInfo.appendChild(userInitial);
            const userNameSpan = document.createElement('span');
            userNameSpan.id = 'header-user-name';
            userNameSpan.className = 'text-xs font-medium text-zinc-300 max-w-[120px] truncate';
            userInfo.appendChild(userNameSpan);
            userDiv.appendChild(userInfo);
            
            const logoutBtn = document.createElement('button');
            logoutBtn.id = 'btn-logout';
            logoutBtn.title = 'Çıkış Yap';
            logoutBtn.className = 'w-9 h-9 rounded-xl bg-zinc-900 hover:bg-rose-500/10 text-zinc-400 hover:text-rose-400 border border-zinc-800 flex items-center justify-center transition-all';
            const logoutIcon = document.createElement('i');
            logoutIcon.className = 'fa-solid fa-right-from-bracket text-xs';
            logoutBtn.appendChild(logoutIcon);
            userDiv.appendChild(logoutBtn);
            
            div2.appendChild(userDiv);
            headerContainer.appendChild(header);

            
            const userInitialEl = document.getElementById('header-user-initial');
            if (userInitialEl) userInitialEl.textContent = userName.charAt(0).toUpperCase();
            const userNameEl = document.getElementById('header-user-name');
            if (userNameEl) userNameEl.textContent = userName;

            // Logout click listener
            const btnLogout = document.getElementById('btn-logout');
            if (btnLogout) {
                btnLogout.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (confirm('Oturumunuz kapatÄ±lacak. Emin misiniz?')) {
                        if (window.API && window.API.auth) {
                            window.API.auth.logout();
                        } else {
                            window.location.href = '/index.html';
                        }
                    }
                });
            }
        },

        /**
         * Render Bottom Mobile Navigation into <div id="app-nav"></div>
         */
        renderBottomNav() {
            const navContainer = document.getElementById('app-nav');
            if (!navContainer) return;

            const currentPage = this.getCurrentPage();

            const items = [
                {
                    id: 'dashboard',
                    title: 'Panel',
                    href: '/dashboard.html',
                    icon: 'fa-house'
                },
                {
                    id: 'live',
                    title: 'Antrenman',
                    href: '/live.html',
                    icon: 'fa-dumbbell',
                    isCenter: true
                },
                {
                    id: 'analytics',
                    title: 'Analiz',
                    href: '/analytics.html',
                    icon: 'fa-chart-simple'
                },
                {
                    id: 'profile',
                    title: 'Profil',
                    href: '/profile.html',
                    icon: 'fa-user'
                }
            ];

            
            navContainer.textContent = '';
            
            const bottomNavWrapper = document.createElement('div');
            bottomNavWrapper.className = 'block lg:hidden fixed bottom-0 left-0 w-full bg-zinc-950/95 backdrop-blur-2xl border-t border-zinc-800/90 z-50 pb-safe';
            
            const nav = document.createElement('nav');
            nav.className = 'flex items-center justify-around px-2 py-1.5';
            
            items.forEach(item => {
                const isActive = (currentPage === item.id);
                const a = document.createElement('a');
                a.href = item.href;
                
                if (item.isCenter) {
                    a.className = 'flex flex-col items-center group -mt-6 focus:outline-none';
                    const iconWrap = document.createElement('div');
                    iconWrap.className = 'w-13 h-13 p-3 rounded-full bg-gradient-to-tr from-rose-600 via-rose-500 to-orange-500 text-white flex items-center justify-center shadow-lg shadow-rose-600/40 border-4 border-zinc-950 group-active:scale-95 transition-transform';
                    const i = document.createElement('i');
                    i.className = 'fa-solid ' + item.icon + ' text-lg';
                    iconWrap.appendChild(i);
                    a.appendChild(iconWrap);
                    
                    const span = document.createElement('span');
                    span.className = 'text-[10px] font-bold mt-1 ' + (isActive ? 'text-rose-400' : 'text-zinc-400');
                    span.textContent = item.title;
                    a.appendChild(span);
                } else {
                    a.className = 'flex flex-col items-center gap-1 py-1.5 px-3 rounded-xl transition-all focus:outline-none ' + (isActive ? 'text-rose-500' : 'text-zinc-400 hover:text-zinc-200');
                    const i = document.createElement('i');
                    i.className = 'fa-solid ' + item.icon + ' text-lg transition-transform ' + (isActive ? 'scale-110' : '');
                    a.appendChild(i);
                    
                    const span = document.createElement('span');
                    span.className = 'text-[10px] font-medium tracking-tight ' + (isActive ? 'font-bold text-rose-400' : '');
                    span.textContent = item.title;
                    a.appendChild(span);
                    
                    if (isActive) {
                        const dot = document.createElement('span');
                        dot.className = 'w-1 h-1 rounded-full bg-rose-500 -mt-0.5';
                        a.appendChild(dot);
                    }
                }
                nav.appendChild(a);
            });
            
            bottomNavWrapper.appendChild(nav);
            navContainer.appendChild(bottomNavWrapper);
            
            const spacer = document.createElement('div');
            spacer.className = 'h-20 lg:hidden pointer-events-none';
            navContainer.appendChild(spacer);

        },

        /**
         * Global Offline / Online Toast Banner
         */
        setupOfflineListener() {
            window.addEventListener('offline', () => {
                if (window.API && window.API.toast) {
                    window.API.toast('Ä°nternet baÄŸlantÄ±sÄ± kesildi. Ã‡evrimdÄ±ÅŸÄ± Ã¶nbellek devrede.', 'warning', 5000);
                }
            });

            window.addEventListener('online', () => {
                if (window.API && window.API.toast) {
                    window.API.toast('Yeniden Ã§evrimiÃ§isiniz! Veriler senkronize ediliyor.', 'success', 3000);
                }
            });
        },

        /**
         * PWA Service Worker Registration
         */
        setupServiceWorker() {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').then(reg => {
                        console.log('[PWA] Service Worker registered:', reg.scope);
                    }).catch(err => {
                        console.log('[PWA] Service Worker registration failed:', err);
                    });
                });
            }
        }
    };

    // Auto-initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Layout.init());
    } else {
        Layout.init();
    }

    window.AppLayout = Layout;

})(window);


