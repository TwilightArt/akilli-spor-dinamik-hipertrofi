/**
 * Akıllı Hipertrofi Platform Client Audio & Haptic Helper
 */
window.HipertrofiApp = {
    // Web Audio Synthesizer Beep (Zero external mp3 asset dependency)
    playTimerChime: function() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5

            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.4);
        } catch (e) {
            console.log('Audio chime not supported or muted');
        }
    }
};


// Dynamic Session Coach Module
document.addEventListener('DOMContentLoaded', () => {
    const btnOpenChat = document.getElementById('btn-open-chat');
    const btnCloseChat = document.getElementById('btn-close-chat');
    const chatContainer = document.getElementById('coach-chat-container');
    const chatMessages = document.getElementById('coach-chat-messages');
    const chatInput = document.getElementById('coach-chat-input');
    const btnSendChat = document.getElementById('btn-send-chat');

    if (!chatContainer || !btnOpenChat) return; // Only init if elements exist on page

    let chatHistory = [];

    btnOpenChat.addEventListener('click', () => {
        chatContainer.classList.remove('hidden');
        btnOpenChat.classList.add('hidden');
    });

    btnCloseChat.addEventListener('click', () => {
        chatContainer.classList.add('hidden');
        btnOpenChat.classList.remove('hidden');
    });

    async function sendChatMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        // Add user message to UI
        addMessageToUI('user', text);
        chatInput.value = '';
        btnSendChat.disabled = true;

        // Extract session context (if available globally in live.html)
        const sessionId = window.liveData?.session?.id || new URLSearchParams(window.location.search).get('session_id') || 0;
        const currentExerciseName = window.liveData?.exercises?.[window.liveData?.currentExerciseIndex]?.exercise_name || null;

        try {
            const response = await fetch('/api/workout/coach', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: sessionId,
                    current_exercise: currentExerciseName,
                    message: text,
                    history: chatHistory
                })
            });
            const data = await response.json();
            
            if (data.success && data.data) {
                // Add model response to history
                chatHistory.push({ role: 'user', parts: [{ text: text }] });
                chatHistory.push({ role: 'model', parts: [{ text: data.data.response }] });
                
                // Keep history limited to last 10 messages to avoid token bloat
                if (chatHistory.length > 10) {
                    chatHistory = chatHistory.slice(chatHistory.length - 10);
                }

                // Add to UI
                addMessageToUI('model', data.data.response);

                // Handle actions (like decreasing weight in the live view)
                if (data.data.action && data.data.action.type !== 'none') {
                    handleCoachAction(data.data.action);
                }
            } else {
                addMessageToUI('model', 'Bağlantı hatası oluştu. Lütfen tekrar dene.');
            }
        } catch (err) {
            addMessageToUI('model', 'Sunucu ile iletişim kurulamadı.');
            console.error('Coach API error:', err);
        } finally {
            btnSendChat.disabled = false;
        }
    }

    function addMessageToUI(role, text) {
        const div = document.createElement('div');
        div.className = role === 'user' 
            ? 'bg-zinc-800 text-zinc-100 p-2 rounded-lg self-end max-w-[85%] border border-zinc-700'
            : 'bg-indigo-900/40 text-indigo-200 p-2 rounded-lg self-start max-w-[85%] border border-indigo-500/20';
        div.textContent = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function handleCoachAction(action) {
        // Find input elements if they exist in live.html
        const weightInput = document.getElementById('input-weight');
        const rirRadios = document.getElementsByName('rir_bucket');
        
        if (action.type === 'decrease_weight' && weightInput) {
            let currentWeight = parseFloat(weightInput.value) || 0;
            let reduction = (action.value || 10) / 100;
            let newWeight = Math.max(0, Math.round((currentWeight * (1 - reduction)) / 2.5) * 2.5); // Round to nearest 2.5
            weightInput.value = newWeight;
            
            const alertDiv = document.createElement('div');
            alertDiv.className = 'text-rose-400 text-[10px] mt-1 text-center font-bold italic';
            alertDiv.textContent = `Koç müdahalesi: Ağırlık %${action.value} düşürüldü (${newWeight}kg)`;
            chatMessages.appendChild(alertDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        } 
        else if (action.type === 'increase_rir' && rirRadios.length) {
            // Set RIR to 3+ (Easier)
            for(let radio of rirRadios) {
                if(radio.value === '3+') radio.checked = true;
            }
            const alertDiv = document.createElement('div');
            alertDiv.className = 'text-amber-400 text-[10px] mt-1 text-center font-bold italic';
            alertDiv.textContent = `Koç müdahalesi: Hedef RIR yükseltildi (daha hafif tempo)`;
            chatMessages.appendChild(alertDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    btnSendChat.addEventListener('click', sendChatMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendChatMessage();
    });
});
