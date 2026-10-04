export const auth = {
    async getCsrf() {
        return window.API.getCsrfToken(true);
    },
    async login(email, password) {
        const token = await window.API.getCsrfToken();
        const res = await window.API.post('/api/auth/login', { email, password, csrf_token: token });
        if (res && res.success && res.user) {
            window._currentUser = res.user;
            localStorage.setItem('akilli_user', JSON.stringify(res.user));
        }
        return res;
    },
    async register(formData) {
        const token = await window.API.getCsrfToken();
        const payload = Object.assign({}, formData, { csrf_token: token });
        const res = await window.API.post('/api/auth/register', payload);
        if (res && res.success && res.user) {
            window._currentUser = res.user;
            localStorage.setItem('akilli_user', JSON.stringify(res.user));
        }
        return res;
    },
    async logout() {
        try { await window.API.post('/api/auth/logout', {}); } catch (e) {}
        localStorage.removeItem('akilli_user');
        localStorage.removeItem('akilli_csrf_token');
        window._currentUser = null;
        window.location.href = '/index.html';
    },
    async me(redirectOnFail = true) {
        try {
            const res = await window.API.get('/api/auth/me');
            if (res && res.success && res.user) {
                window._currentUser = res.user;
                localStorage.setItem('akilli_user', JSON.stringify(res.user));
                return res.user;
            }
        } catch (err) {
            if (redirectOnFail) {
                const path = window.location.pathname;
                if (!path.includes('index.html') && !path.includes('register.html')) {
                    window.location.href = '/index.html';
                }
            }
        }
        return null;
    },
    getCurrentUser() {
        if (window._currentUser) return window._currentUser;
        try {
            const cached = localStorage.getItem('akilli_user');
            if (cached) {
                window._currentUser = JSON.parse(cached);
                return window._currentUser;
            }
        } catch (_) {}
        return null;
    }
};
