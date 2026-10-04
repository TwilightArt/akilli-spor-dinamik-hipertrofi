export const workout = {
    getDashboard() { return window.API.get('/api/workout/dashboard'); },
    getLive(dayId = null) {
        const endpoint = dayId ? `/api/workout/live/${dayId}` : '/api/workout/live';
        return window.API.get(endpoint);
    },
    logSet(setData) { return window.API.post('/api/workout/log-set', setData); },
    end(sessionId) { return window.API.post('/api/workout/end', { session_id: sessionId }); },
    getAiCoach(sessionId) { return window.API.get('/api/workout/ai-coach', { session_id: sessionId }); },
    retryProgram() { return window.API.post('/api/workout/retry-program', {}); },
    customProgram(configData) { return window.API.post('/api/workout/custom-program', configData); }
};
