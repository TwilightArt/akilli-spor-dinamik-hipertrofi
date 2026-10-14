import { auth } from './auth.js';
import { workout } from './workout.js';
import { analytics } from './analytics.js';

window._csrfToken = localStorage.getItem('akilli_csrf_token') || '';
window._currentUser = null;

const API = {
    baseUrl: '',
    async getCsrfToken(forceRefresh = false) {
        if (window._csrfToken && !forceRefresh) return window._csrfToken;
        try {
            const res = await fetch('/api/auth/csrf', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data && data.csrf_token) {
                window._csrfToken = data.csrf_token;
                localStorage.setItem('akilli_csrf_token', window._csrfToken);
                return window._csrfToken;
            }
        } catch (e) {
            console.warn('Could not fetch CSRF token:', e);
        }
        return window._csrfToken;
    },
    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : (this.baseUrl + endpoint);
        const method = (options.method || 'GET').toUpperCase();
        const headers = Object.assign({ 'Accept': 'application/json' }, options.headers || {});
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            if (!headers['Content-Type'] && !(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json; charset=UTF-8';
            }
            const token = await this.getCsrfToken();
            if (token && !headers['X-CSRF-Token']) headers['X-CSRF-Token'] = token;
        }
        const fetchOptions = { method: method, headers: headers, credentials: 'same-origin' };
        if (options.body) {
            if (typeof options.body === 'object' && !(options.body instanceof FormData)) {
                fetchOptions.body = JSON.stringify(options.body);
            } else {
                fetchOptions.body = options.body;
            }
        }
        try {
            const response = await fetch(url, fetchOptions);
            const contentType = response.headers.get('content-type') || '';
            let data = null;
            if (contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                try { data = JSON.parse(text); } catch (_) { data = { raw: text }; }
            }
            if (data && data.csrf_token) {
                window._csrfToken = data.csrf_token;
                localStorage.setItem('akilli_csrf_token', window._csrfToken);
            }
            if (response.status === 401) {
                localStorage.removeItem('akilli_user');
                const currentPath = window.location.pathname;
                const isAuthPage = currentPath.includes('index.html') || currentPath.includes('login') || currentPath.includes('register.html') || currentPath.includes('forgot-password');
                if (!isAuthPage) {
                    API.toast('Oturum süreniz doldu. Giriş sayfasına yönlendiriliyorsunuz...', 'warning');
                    setTimeout(() => { window.location.href = '/index.html'; }, 1200);
                }
                return data || { success: false, error: 'Oturum açılmamış.' };
            }
            if (!response.ok) {
                const errorMsg = (data && (data.error || data.message)) || `HTTP Hatası: ${response.status}`;
                throw new Error(errorMsg);
            }
            return data;
        } catch (err) {
            console.error(`[API Error] ${method} ${endpoint}:`, err);
            throw err;
        }
    },
    get(endpoint, params = {}) {
        let url = endpoint;
        const query = new URLSearchParams(params).toString();
        if (query) url += (url.includes('?') ? '&' : '?') + query;
        return this.request(url, { method: 'GET' });
    },
    post(endpoint, body = {}) {
        return this.request(endpoint, { method: 'POST', body: body });
    },
    profile: {
        get() { return API.get('/api/profile'); },
        update(profileData) { return API.post('/api/profile', profileData); },
        updatePassword(currentPassword, newPassword, confirmPassword) {
            return API.post('/api/profile/password', { current_password: currentPassword, new_password: newPassword, confirm_password: confirmPassword });
        }
    },
    weighIn: {
        getHistory() { return API.get('/api/weigh-in'); },
        save(weightKg, loggedDate = null, notes = null) {
            return API.post('/api/weigh-in', { weight_kg: weightKg, logged_date: loggedDate || new Date().toISOString().slice(0, 10), notes: notes });
        }
    },
    calculators: {
        calculate1rm(weight, reps) { return API.get('/api/calculators/1rm', { weight, reps }); },
        calculateTdee(params) { return API.get('/api/calculators/tdee', params); },
        calculateWater(params) { return API.get('/api/calculators/water', params); }
    },
    onboarding: {
        getAssessment() { return API.get('/api/onboarding/assessment'); },
        saveAssessment(data) { return API.post('/api/onboarding/assessment', data); },
        getEquipment() { return API.get('/api/onboarding/equipment'); },
        saveEquipment(data) { return API.post('/api/onboarding/equipment', data); }
    },
    toast(message, type = 'info', duration = 3000) {
        let container = document.getElementById('api-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'api-toast-container';
            container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2 max-w-sm pointer-events-none px-4 w-full sm:w-auto';
            document.body.appendChild(container);
        }
        const colors = {
            success: 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-500/20',
            error: 'bg-rose-600 text-white border-rose-500 shadow-rose-500/20',
            warning: 'bg-amber-600 text-white border-amber-500 shadow-amber-500/20',
            info: 'bg-indigo-600 text-white border-indigo-500 shadow-indigo-500/20'
        };
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-triangle-exclamation',
            warning: 'fa-circle-exclamation',
            info: 'fa-circle-info'
        };
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-sm font-medium transition-all duration-300 transform translate-y-2 opacity-0 ${colors[type] || colors.info}`;
        
        const icon = document.createElement('i');
        icon.className = `fa-solid ${icons[type] || icons.info} text-base shrink-0`;
        toast.appendChild(icon);
        
        const textDiv = document.createElement('div');
        textDiv.className = 'flex-1 leading-snug break-words toast-message-content';
        textDiv.textContent = message;
        toast.appendChild(textDiv);
        
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.remove('translate-y-2', 'opacity-0'));
        setTimeout(() => {
            toast.classList.add('opacity-0', '-translate-y-2');
            setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
        }, duration);
    }
};

API.auth = auth;
API.workout = workout;
API.analytics = analytics;

window.API = API;
export default API;
