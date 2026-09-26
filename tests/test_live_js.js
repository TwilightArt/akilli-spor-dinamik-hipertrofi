const fs = require('fs');
const html = fs.readFileSync('tests/live_user32_output.html', 'utf8');

// Extract config
const configMatch = html.match(/window\.__LIVE_WORKOUT_CONFIG__\s*=\s*(\{[\s\S]*?\});/);
const config = eval('(' + configMatch[1] + ')');

// Extract liveWorkout function
const funcMatch = html.match(/(function liveWorkout\(config\)\s*\{[\s\S]*?\n\})/);
if (!funcMatch) {
    console.error('Could not find liveWorkout function');
    process.exit(1);
}

eval(funcMatch[1]);
const instance = liveWorkout(config);
instance.init();

console.log('--- TEST RESULTS ---');
console.log('Exercise 1:', instance.currentExercise.exercise_name);
console.log('Sets array:', instance.getTargetSetsArray());
console.log('Set 1 card class:', instance.getSetCardClass(1));
console.log('Set 2 card class:', instance.getSetCardClass(2));
console.log('Actual reps:', instance.actualReps);
console.log('SUCCESS: All functions evaluated with 0 errors!');
