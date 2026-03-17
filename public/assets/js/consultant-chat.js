let lastMessageId = 0;

const chatMessagesEl = document.getElementById('chatMessages');
const chatInputEl = document.getElementById('chatInput');
const chatSendBtnEl = document.getElementById('chatSendBtn');
const connectBtnEl = document.getElementById('connectBtn');
const closeBtnEl = document.getElementById('closeBtn');
const statusLabelEl = document.getElementById('statusLabel');

function appendMessage(text, type) {
    const div = document.createElement('div');
    div.classList.add('chat-message', type);
    div.textContent = text;
    chatMessagesEl.appendChild(div);
    chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
}

async function fetchNewMessages() {
    if (!CONSULTANT_SESSION_ID) return;

    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'fetch_messages',
                session_id: CONSULTANT_SESSION_ID,
                after_id: lastMessageId
            })
        });

        if (!response.ok) return;
        const data = await response.json();
        if (data.error) return;

        if (statusLabelEl && data.status) {
            statusLabelEl.textContent = 'Статус: ' + data.status;
        }

        const msgs = Array.isArray(data.messages) ? data.messages : [];
        for (const m of msgs) {
            lastMessageId = Math.max(lastMessageId, parseInt(m.id, 10) || 0);
            appendMessage(m.message_text, m.sender_type);
        }
    } catch (e) {
        // молча, чтобы не спамить чат
    }
}

async function connectToSession() {
    if (!CONSULTANT_SESSION_ID) return;
    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'consultant_connect',
                session_id: CONSULTANT_SESSION_ID
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            appendMessage('Не удалось подключиться к сессии.', 'bot');
            return;
        }

        if (statusLabelEl) statusLabelEl.textContent = 'Статус: consultant_connected';
        if (data.bot_message) appendMessage(data.bot_message, 'bot');
    } catch (e) {
        appendMessage('Сервер недоступен. Проверьте XAMPP.', 'bot');
    }
}

async function closeSession() {
    if (!CONSULTANT_SESSION_ID) return;
    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'consultant_close',
                session_id: CONSULTANT_SESSION_ID
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            appendMessage('Не удалось закрыть диалог.', 'bot');
            return;
        }

        if (statusLabelEl) statusLabelEl.textContent = 'Статус: closed';
        if (data.bot_message) appendMessage(data.bot_message, 'bot');
    } catch (e) {
        appendMessage('Сервер недоступен. Проверьте XAMPP.', 'bot');
    }
}

async function sendConsultantMessage() {
    const text = (chatInputEl?.value || '').trim();
    if (!text) return;

    chatInputEl.value = '';

    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'consultant_send',
                session_id: CONSULTANT_SESSION_ID,
                message: text
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            appendMessage('Ошибка отправки: ' + (data.error || 'неизвестно'), 'bot');
            return;
        }

        appendMessage(text, 'consultant');
        if (data.message_id) {
            lastMessageId = Math.max(lastMessageId, parseInt(data.message_id, 10) || 0);
        }
    } catch (e) {
        appendMessage('Не удалось отправить сообщение.', 'bot');
    }
}

if (connectBtnEl) connectBtnEl.addEventListener('click', connectToSession);
if (closeBtnEl) closeBtnEl.addEventListener('click', closeSession);
if (chatSendBtnEl) chatSendBtnEl.addEventListener('click', sendConsultantMessage);
if (chatInputEl) {
    chatInputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') sendConsultantMessage();
    });
}

// Периодически подтягиваем новые сообщения
setInterval(fetchNewMessages, 2000);
fetchNewMessages();

