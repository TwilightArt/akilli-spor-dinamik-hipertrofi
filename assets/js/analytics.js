export const analytics = {
    getData(exerciseId = null) {
        const params = exerciseId ? { exercise_id: exerciseId } : {};
        return window.API.get('/api/analytics', params);
    },
    getExerciseProgress(exerciseId) {
        return window.API.get('/api/exercise_progress', { exercise_id: exerciseId });
    }
};
