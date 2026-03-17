// Логика фронтенда для онлайн-чата

let currentSessionId = null;
let lastMessageId = 0;
let pollTimerId = null;
let currentStatus = null;
let closedNoticeShown = false;

const chatMessagesEl = document.getElementById('chatMessages');
const chatInputEl = document.getElementById('chatInput');
const chatSendBtnEl = document.getElementById('chatSendBtn');
const quickActionsEl = document.getElementById('quickActions');
const requestConsultantBtnEl = document.getElementById('requestConsultantBtn');
const chatModeLabelEl = document.getElementById('chatModeLabel');

function setChatModeBadge(status) {
    if (!chatModeLabelEl) return;
    const map = {
        bot: { text: 'Бот', cls: 'badge badge--bot' },
        waiting_for_consultant: { text: 'Ожидание консультанта', cls: 'badge badge--waiting' },
        consultant_connected: { text: 'Живой консультант', cls: 'badge badge--connected' },
        closed: { text: 'Диалог завершён', cls: 'badge badge--closed' }
    };
    const item = map[status] || { text: 'Режим', cls: 'badge' };
    chatModeLabelEl.textContent = item.text;
    chatModeLabelEl.className = item.cls;
}

// Начальный режим до первого ответа сервера
setChatModeBadge('bot');

function appendMessage(text, type, options = {}) {
    const div = document.createElement('div');
    div.classList.add('chat-message', type);
    div.textContent = text;

    if (type === 'bot') {
        const actions = document.createElement('div');
        actions.className = 'chat-inline-actions';

        const btnConsultant = document.createElement('button');
        btnConsultant.type = 'button';
        btnConsultant.className = 'btn btn-outline';
        btnConsultant.textContent = 'Позвать консультанта';
        btnConsultant.addEventListener('click', () => requestConsultant());
        actions.appendChild(btnConsultant);

        const btnServices = document.createElement('a');
        btnServices.className = 'btn btn-secondary';
        btnServices.textContent = 'Услуги';
        btnServices.href = 'services.php';
        actions.appendChild(btnServices);

        const btnContacts = document.createElement('a');
        btnContacts.className = 'btn btn-secondary';
        btnContacts.textContent = 'Оставить заявку';
        btnContacts.href = 'contacts.php';
        actions.appendChild(btnContacts);

        if (options.serviceLink) {
            const btnService = document.createElement('a');
            btnService.className = 'btn btn-primary';
            btnService.textContent = 'Открыть услугу';
            btnService.href = options.serviceLink;
            actions.appendChild(btnService);
        }

        div.appendChild(actions);
    }

    chatMessagesEl.appendChild(div);
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
        if (status) setChatModeBadge(status);

        const msgs = Array.isArray(data.messages) ? data.messages : [];
        for (const m of msgs) {
            lastMessageId = Math.max(lastMessageId, parseInt(m.id, 10) || 0);
            appendMessage(m.message_text, m.sender_type);
        }

        if (status === 'closed') {
            if (chatInputEl) chatInputEl.disabled = true;
            if (chatSendBtnEl) chatSendBtnEl.disabled = true;
            if (quickActionsEl) {
                const btns = quickActionsEl.querySelectorAll('button');
                btns.forEach((b) => {
                    b.disabled = true;
                });
            }
            if (!closedNoticeShown) {
                closedNoticeShown = true;
                appendMessage('Диалог завершён. Вы можете оставить заявку через раздел «Контакты» или посмотреть услуги.', 'bot');
            }
        }
    } catch (e) {
        // молча
    }
}

function ensurePolling() {
    if (pollTimerId) return;
    pollTimerId = setInterval(fetchNewMessages, 2000);
}

async function requestConsultant() {
    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json; charset=utf-8' },
            body: JSON.stringify({
                action: 'request_consultant',
                session_id: currentSessionId
            })
        });

        if (!response.ok) {
            appendMessage('Не удалось отправить запрос консультанту. Попробуйте позже.', 'bot');
            return;
        }

        const data = await response.json();
        if (data.error) {
            appendMessage('Ошибка: ' + data.error, 'bot');
            return;
        }

        currentSessionId = data.session_id || currentSessionId;
        ensurePolling();
        setChatModeBadge('waiting_for_consultant');
        appendMessage(data.bot_message || 'Запрос консультанту отправлен.', 'bot');
    } catch (e) {
        appendMessage('Не удалось связаться с сервером. Проверьте работу Apache/PHP.', 'bot');
    }
}

async function sendMessage() {
    if (currentStatus === 'closed') {
        appendMessage('Диалог уже завершён. Вы можете оставить заявку на консультацию на странице "Контакты".', 'bot');
        return;
    }
    const text = chatInputEl.value.trim();
    if (!text) {
        return;
    }

    appendMessage(text, 'client');
    chatInputEl.value = '';

    try {
        const response = await fetch(CHAT_HANDLER_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8'
            },
            body: JSON.stringify({
                message: text,
                session_id: currentSessionId
            })
        });

        if (!response.ok) {
            appendMessage('Произошла ошибка при отправке сообщения. Попробуйте позже.', 'bot');
            return;
        }

        const data = await response.json();

        if (data.error) {
            appendMessage('Ошибка: ' + data.error, 'bot');
            return;
        }

        currentSessionId = data.session_id || currentSessionId;
        lastMessageId = 0; // после первой инициализации начнём подтягивать всё, что могло прийти
        ensurePolling();
        if (data.mode === 'waiting_for_consultant') setChatModeBadge('waiting_for_consultant');

        let botText = data.bot_message || 'Ответ не получен.';
        appendMessage(botText, 'bot', { serviceLink: data.service_link || null });
    } catch (e) {
        appendMessage('Не удалось связаться с сервером. Проверьте подключение к интернету или настройку сервера.', 'bot');
    }
}

if (chatSendBtnEl && chatInputEl) {
    chatSendBtnEl.addEventListener('click', sendMessage);
    chatInputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
}

if (quickActionsEl) {
    quickActionsEl.addEventListener('click', (e) => {
        /** @type {any} */
        let el = e.target;
        // На всякий случай: если клик пришёлся в текстовый узел, поднимаемся к родителю
        if (el && el.nodeType === 3) {
            el = el.parentElement;
        }
        if (!el || typeof el.closest !== 'function') return;

        const btn = el.closest('button');
        if (!btn) return;

        if (btn.id === 'requestConsultantBtn') {
            requestConsultant();
            return;
        }

        const quickText = btn.getAttribute('data-quick');
        if (quickText) {
            chatInputEl.value = quickText;
            sendMessage();
        }
    });
}

if (requestConsultantBtnEl) {
    requestConsultantBtnEl.addEventListener('click', requestConsultant);
}

