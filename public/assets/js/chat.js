let currentSessionId = null;
let lastMessageId = 0;
let pollTimerId = null;
let currentStatus = null;
let closedNoticeShown = false;

const chatMessagesEl = document.getElementById('chatMessages');
const chatModeLabelEl = document.getElementById('chatModeLabel');
const startConsultationBtnEl = document.getElementById('startConsultationBtn');
const closeChatBtnEl = document.getElementById('closeChatBtn');
const chatInputRowEl = document.getElementById('chatInputRow');
const chatInputEl = document.getElementById('chatInput');
const chatSendBtnEl = document.getElementById('chatSendBtn');

function setChatModeBadge(status) {
    if (!chatModeLabelEl) return;

    const map = {
        bot: { text: 'Бот', cls: 'badge badge--bot' },
        waiting_for_consultant: { text: 'Ожидание консультанта', cls: 'badge badge--waiting' },
        consultant_connected: { text: 'Живой консультант', cls: 'badge badge--connected' },
        closed: { text: 'Диалог завершён', cls: 'badge badge--closed' }
    };
    const item = map[status] || { text: 'Бот', cls: 'badge badge--bot' };
    chatModeLabelEl.textContent = item.text;
    chatModeLabelEl.className = item.cls;
}

function appendMessage(text, type) {
    if (!chatMessagesEl) return;

    const div = document.createElement('div');
    div.classList.add('chat-message', type);
    div.innerHTML = text;
    chatMessagesEl.appendChild(div);
    chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
}

function setInteractionState(status) {
    if (chatInputRowEl) {
        chatInputRowEl.classList.toggle('is-visible', status === 'waiting_for_consultant' || status === 'consultant_connected');
    }

    if (closeChatBtnEl) {
        closeChatBtnEl.style.display = (status === 'waiting_for_consultant' || status === 'consultant_connected') ? 'inline-flex' : 'none';
    }

    if (startConsultationBtnEl) {
        if (status === 'bot') {
            startConsultationBtnEl.disabled = false;
            startConsultationBtnEl.textContent = 'Проконсультироваться';
        } else {
            startConsultationBtnEl.disabled = true;
            startConsultationBtnEl.textContent = status === 'closed' ? 'Диалог завершён' : 'Запрос отправлен';
        }
    }

    if (chatInputEl) {
        chatInputEl.disabled = status === 'closed' || status === 'bot';
    }
    if (chatSendBtnEl) {
        chatSendBtnEl.disabled = status === 'closed' || status === 'bot';
    }
}

function appendAuthChoiceMessage() {
    appendMessage(
        'Чтобы отправить запрос онлайн‑консультанту, сначала войдите в личный кабинет или зарегистрируйтесь.',
        'system'
    );

    const wrap = document.createElement('div');
    wrap.className = 'chat-message bot';
    wrap.innerHTML =
        '<div class="chat-actions" style="padding:0; border-top:none; background:transparent;">' +
        '<a class="btn btn-outline" href="' + CHAT_LOGIN_URL + '">Войти</a>' +
        '<a class="btn btn-primary" href="' + CHAT_REGISTER_URL + '">Зарегистрироваться</a>' +
        '</div>';
    chatMessagesEl.appendChild(wrap);
    chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
}

async function fetchNewMessages() {
    if (!currentSessionId) return;

    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'fetch_messages',
                session_id: currentSessionId,
                after_id: lastMessageId
            })
        });

        if (!response.ok) return;
        const data = await response.json();
        if (data.error) return;

        const status = data.status || null;
        currentStatus = status;
        if (status) {
            setChatModeBadge(status);
            setInteractionState(status);
        }

        const msgs = Array.isArray(data.messages) ? data.messages : [];
        for (const m of msgs) {
            lastMessageId = Math.max(lastMessageId, parseInt(m.id, 10) || 0);
            appendMessage(m.message_text, m.sender_type);
        }

        if (status === 'closed') {
            if (chatInputEl) chatInputEl.disabled = true;
            if (chatSendBtnEl) chatSendBtnEl.disabled = true;
            if (!closedNoticeShown) {
                closedNoticeShown = true;
                appendMessage('Диалог завершён. Вы можете оставить заявку через раздел «Контакты» или посмотреть услуги.', 'bot');
            }
        }
    } catch (e) {
        // Без всплывающих ошибок, чтобы не засорять окно чата
    }
}

function ensurePolling() {
    if (pollTimerId) return;
    pollTimerId = setInterval(fetchNewMessages, 2000);
}

async function requestConsultant() {
    if (!CHAT_IS_AUTHENTICATED) {
        appendAuthChoiceMessage();
        return;
    }

    if (startConsultationBtnEl) {
        startConsultationBtnEl.disabled = true;
    }

    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'request_consultant',
                session_id: currentSessionId || 0
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            appendMessage(data.error || 'Не удалось отправить запрос консультанту. Попробуйте позже.', 'system');
            if (startConsultationBtnEl) {
                startConsultationBtnEl.disabled = false;
            }
            return;
        }

        currentSessionId = data.session_id || currentSessionId;
        currentStatus = data.mode || 'waiting_for_consultant';
        setChatModeBadge(currentStatus);
        setInteractionState(currentStatus);
        ensurePolling();
        await fetchNewMessages();

        if (data.bot_message) {
            appendMessage(data.bot_message, 'bot');
        }
    } catch (e) {
        appendMessage('Не удалось связаться с сервером. Проверьте работу XAMPP.', 'system');
        if (startConsultationBtnEl) {
            startConsultationBtnEl.disabled = false;
        }
    }
}

async function sendClientMessage() {
    const text = (chatInputEl?.value || '').trim();
    if (!text || !currentSessionId || currentStatus === 'closed' || currentStatus === 'bot') {
        return;
    }

    chatInputEl.value = '';

    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'client_send',
                session_id: currentSessionId,
                message: text
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            appendMessage(data.error || 'Не удалось отправить сообщение. Попробуйте ещё раз.', 'system');
            return;
        }

        appendMessage(text, 'client');
        if (data.message_id) {
            lastMessageId = Math.max(lastMessageId, parseInt(data.message_id, 10) || 0);
        }
        if (data.mode) {
            currentStatus = data.mode;
            setChatModeBadge(currentStatus);
            setInteractionState(currentStatus);
        }
    } catch (e) {
        appendMessage('Сервер недоступен. Проверьте работу XAMPP.', 'system');
    }
}

async function closeClientChat() {
    if (!currentSessionId || currentStatus === 'closed') {
        return;
    }

    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'client_close',
                session_id: currentSessionId
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            appendMessage(data.error || 'Не удалось завершить диалог. Попробуйте позже.', 'system');
            return;
        }

        currentStatus = 'closed';
        setChatModeBadge(currentStatus);
        setInteractionState(currentStatus);
        if (data.bot_message) {
            appendMessage(data.bot_message, 'bot');
        }
        if (data.message_id) {
            lastMessageId = Math.max(lastMessageId, parseInt(data.message_id, 10) || 0);
        }
    } catch (e) {
        appendMessage('Не удалось связаться с сервером. Проверьте работу XAMPP.', 'system');
    }
}

currentSessionId = CHAT_INITIAL_SESSION_ID || null;
if (currentSessionId === 0) {
    currentSessionId = null;
}
currentStatus = CHAT_INITIAL_STATUS || 'bot';
setChatModeBadge(currentStatus);
setInteractionState(currentStatus);

if (currentSessionId) {
    ensurePolling();
    fetchNewMessages();
}

if (startConsultationBtnEl) {
    startConsultationBtnEl.addEventListener('click', requestConsultant);
}

if (closeChatBtnEl) {
    closeChatBtnEl.addEventListener('click', closeClientChat);
}

if (chatSendBtnEl) {
    chatSendBtnEl.addEventListener('click', sendClientMessage);
}

if (chatInputEl) {
    chatInputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            sendClientMessage();
        }
    });
}
