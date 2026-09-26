const fs = require('fs');

const html = fs.readFileSync(__dirname + '/live_user32_output.html', 'utf8');

// Extract liveWorkout function
const funcMatch = html.match(/function liveWorkout\(config\)\s*\{([\s\S]*?)\}\s*<\/script>/);
if (!funcMatch) {
    console.error("COULD NOT FIND liveWorkout function!");
    process.exit(1);
}

// Extract x-data expression
const xdataMatch = html.match(/x-data="liveWorkout\((\{[\s\S]*?\})\)"/);
if (!xdataMatch) {
    console.error("COULD NOT FIND x-data expression!");
    process.exit(1);
}

console.log("Found function and x-data!");

try {
    const fn = new Function('config', 'return (' + 'function(config) {' + funcMatch[1] + '}' + ')(config);');
    const configStr = xdataMatch[1];
    console.log("Config string length:", configStr.length);
    const config = eval('(' + configStr + ')');
    console.log("Config evaluated successfully! SessionId:", config.sessionId, "Exercises count:", config.exercises?.length);
    
    const component = fn(config);
    console.log("Component created successfully!");
    component.init();
    console.log("Component initialized! currentExercise:", component.currentExercise?.exercise_name);
    console.log("targetSetsArray:", component.getTargetSetsArray());
} catch (err) {
    console.error("JAVASCRIPT EXECUTION ERROR:", err);
}
