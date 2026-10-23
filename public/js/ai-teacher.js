/**
 * AI Teacher Interactive Chat Client
 * Univ E-Learning Platform
 */

(function () {
    'use strict';

    const elements = {
        chatContainer: document.getElementById('aiChatMessages'),
        chatInput: document.getElementById('aiMessageInput'),
        sendBtn: document.getElementById('aiSendBtn'),
        courseSelect: document.getElementById('aiCourseSelect'),
        modeSelect: document.getElementById('aiModeSelect'),
        focusLessonPill: document.getElementById('aiFocusLessonPill'),
        focusLessonText: document.getElementById('aiFocusLessonText'),
        clearLessonBtn: document.getElementById('aiClearLessonBtn'),
        clearChatBtn: document.getElementById('aiClearChatBtn'),
        lessonsList: document.getElementById('aiLessonsList'),
        modeBtns: document.querySelectorAll('.js-mode-btn'),
        quickActionBtns: document.querySelectorAll('.js-quick-action'),
        starterPrompts: document.getElementById('aiStarterPrompts'),
    };

    if (!elements.chatContainer || !elements.chatInput) {
        return;
    }

    let state = {
        courseId: elements.courseSelect ? elements.courseSelect.value : '',
        lesson: elements.focusLessonText ? elements.focusLessonText.getAttribute('data-lesson') || '' : '',
        mode: 'explain',
        isSubmitting: false,
        history: [], // [{ role: 'user'|'assistant', content: '...' }]
    };

    const STORAGE_KEY_PREFIX = 'univ_ai_chat_history_v1_';

    function getStorageKey() {
        return STORAGE_KEY_PREFIX + (state.courseId || 'general');
    }

    function loadHistory() {
        try {
            const raw = localStorage.getItem(getStorageKey());
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    state.history = parsed;
                    renderHistory();
                    return;
                }
            }
        } catch (e) {
            console.warn('Failed to load chat history:', e);
        }
        state.history = [];
        renderHistory();
    }

    function saveHistory() {
        try {
            // Keep last 30 messages in localStorage
            const toSave = state.history.slice(-30);
            localStorage.setItem(getStorageKey(), JSON.stringify(toSave));
        } catch (e) {
            console.warn('Failed to save chat history:', e);
        }
    }

    function renderHistory() {
        elements.chatContainer.innerHTML = '';
        if (state.history.length === 0) {
            if (elements.starterPrompts) {
                elements.chatContainer.appendChild(elements.starterPrompts.cloneNode(true));
                bindQuickActions(elements.chatContainer);
            }
            return;
        }

        state.history.forEach(function (msg) {
            appendMessageBubble(msg.role, msg.content, false);
        });
        scrollToBottom();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatMarkdown(text) {
        if (!text) return '';
        let escaped = escapeHtml(text);

        // Code blocks: ```lang\ncode\n```
        escaped = escaped.replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, function (match, lang, code) {
            return '<pre><code>' + code + '</code></pre>';
        });

        // Inline code: `code`
        escaped = escaped.replace(/`([^`\n]+)`/g, '<code>$1</code>');

        // Headers: ### Header
        escaped = escaped.replace(/^### (.*$)/gim, '<h6 class="fw-bold mt-2 mb-1">$1</h6>');
        escaped = escaped.replace(/^## (.*$)/gim, '<h5 class="fw-bold mt-2 mb-1">$1</h5>');
        escaped = escaped.replace(/^# (.*$)/gim, '<h5 class="fw-bold mt-2 mb-1 text-primary">$1</h5>');

        // Bold: **text**
        escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

        // Italic: *text*
        escaped = escaped.replace(/\*([^*]+)\*/g, '<em>$1</em>');

        // Blockquotes: > quote
        escaped = escaped.replace(/^\> (.*$)/gim, '<blockquote class="border-start border-3 border-primary ps-2 text-muted my-2">$1</blockquote>');

        // Unordered lists: - item or * item
        escaped = escaped.replace(/^\s*[-*]\s+(.*$)/gim, '<li class="ms-3">$1</li>');

        // Ordered lists: 1. item
        escaped = escaped.replace(/^\s*(\d+)\.\s+(.*$)/gim, '<li class="ms-3"><strong>$1.</strong> $2</li>');

        // Newlines to <br> (avoid breaking inside <pre>)
        const parts = escaped.split(/(<pre>[\s\S]*?<\/pre>)/g);
        for (let i = 0; i < parts.length; i++) {
            if (!parts[i].startsWith('<pre>')) {
                parts[i] = parts[i].replace(/\n/g, '<br>');
            }
        }
        return parts.join('');
    }

    function appendMessageBubble(role, content, animate) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'ai-message ' + (role === 'user' ? 'user' : 'assistant');
        if (animate) {
            msgDiv.classList.add('animate__animated', 'animate__fadeIn');
        }

        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'ai-avatar';
        avatarDiv.innerHTML = role === 'user' ? '<i class="bi bi-person"></i>' : '<i class="bi bi-stars"></i>';

        const bubbleDiv = document.createElement('div');
        bubbleDiv.className = 'ai-bubble';

        if (role === 'user') {
            bubbleDiv.textContent = content;
        } else {
            bubbleDiv.innerHTML = formatMarkdown(content);

            // Add copy button
            const copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.className = 'btn btn-sm text-muted p-0 ms-2 float-end border-0 bg-transparent';
            copyBtn.title = 'Copy response';
            copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
            copyBtn.addEventListener('click', function () {
                navigator.clipboard.writeText(content).then(function () {
                    copyBtn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
                    setTimeout(function () { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
                });
            });
            bubbleDiv.prepend(copyBtn);
        }

        msgDiv.appendChild(avatarDiv);
        msgDiv.appendChild(bubbleDiv);

        // If starter prompts currently in view, remove them
        const existingStarter = elements.chatContainer.querySelector('.js-starter-wrapper');
        if (existingStarter) {
            existingStarter.remove();
        }

        elements.chatContainer.appendChild(msgDiv);
        scrollToBottom();
        return msgDiv;
    }

    function showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.id = 'aiTypingIndicator';
        typingDiv.className = 'ai-message assistant';
        typingDiv.innerHTML = `
            <div class="ai-avatar"><i class="bi bi-stars"></i></div>
            <div class="ai-bubble">
                <div class="ai-typing-indicator">
                    <span class="ai-typing-dot"></span>
                    <span class="ai-typing-dot"></span>
                    <span class="ai-typing-dot"></span>
                </div>
            </div>
        `;
        elements.chatContainer.appendChild(typingDiv);
        scrollToBottom();
    }

    function hideTypingIndicator() {
        const typingDiv = document.getElementById('aiTypingIndicator');
        if (typingDiv) {
            typingDiv.remove();
        }
    }

    function scrollToBottom() {
        elements.chatContainer.scrollTop = elements.chatContainer.scrollHeight;
    }

    async function sendMessage(text) {
        const question = (text || elements.chatInput.value).trim();
        if (!question || state.isSubmitting) {
            return;
        }

        state.isSubmitting = true;
        elements.sendBtn.disabled = true;
        elements.chatInput.value = '';
        elements.chatInput.style.height = 'auto';

        // Add user message to UI and history
        appendMessageBubble('user', question, true);
        state.history.push({ role: 'user', content: question });
        saveHistory();

        showTypingIndicator();

        try {
            const payload = {
                message: question,
                course_id: state.courseId,
                lesson: state.lesson,
                mode: state.mode,
                history: state.history.slice(-8), // Send recent context
            };

            const response = await fetch('index.php?page=ai-teacher-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            hideTypingIndicator();

            if (!response.ok) {
                const errText = await response.text();
                let errMsg = 'The AI Teacher encountered a server error.';
                try {
                    const errJson = JSON.parse(errText);
                    if (errJson.error) errMsg = errJson.error;
                } catch (_) {}
                appendMessageBubble('assistant', '⚠️ ' + errMsg, true);
                return;
            }

            const data = await response.json();
            if (data.reply) {
                appendMessageBubble('assistant', data.reply, true);
                state.history.push({ role: 'assistant', content: data.reply });
                saveHistory();
            } else if (data.error) {
                appendMessageBubble('assistant', '⚠️ ' + data.error, true);
            }
        } catch (err) {
            hideTypingIndicator();
            appendMessageBubble('assistant', '⚠️ Network error: could not connect to the AI Teacher. Please check your connection and try again.', true);
        } finally {
            state.isSubmitting = false;
            elements.sendBtn.disabled = false;
            elements.chatInput.focus();
        }
    }

    function setFocusLesson(title) {
        state.lesson = title || '';
        if (elements.focusLessonPill && elements.focusLessonText) {
            if (state.lesson) {
                elements.focusLessonText.textContent = state.lesson;
                elements.focusLessonText.setAttribute('data-lesson', state.lesson);
                elements.focusLessonPill.classList.remove('d-none');
            } else {
                elements.focusLessonPill.classList.add('d-none');
                elements.focusLessonText.textContent = '';
                elements.focusLessonText.removeAttribute('data-lesson');
            }
        }

        // Highlight active lesson in sidebar
        if (elements.lessonsList) {
            elements.lessonsList.querySelectorAll('.ai-lesson-item').forEach(function (item) {
                if (item.getAttribute('data-lesson-title') === state.lesson) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }
    }

    async function loadCourseLessons(courseId) {
        if (!elements.lessonsList) return;
        elements.lessonsList.innerHTML = '<div class="text-center py-3 text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Loading lessons...</div>';

        try {
            const res = await fetch(`index.php?page=ai-teacher-lessons&course_id=${encodeURIComponent(courseId)}`);
            if (!res.ok) throw new Error('Failed to load lessons');
            const data = await res.json();
            const lessons = data.lessons || [];

            elements.lessonsList.innerHTML = '';
            if (lessons.length === 0) {
                elements.lessonsList.innerHTML = '<div class="text-muted small p-2">No specific lesson modules found for this course. You can still ask any questions!</div>';
                return;
            }

            lessons.forEach(function (l) {
                const item = document.createElement('div');
                item.className = 'ai-lesson-item d-flex align-items-center justify-content-between mb-1';
                item.setAttribute('data-lesson-title', l.title);
                item.innerHTML = `
                    <span class="text-truncate me-2"><i class="bi bi-journal-bookmark me-1 text-primary"></i> ${escapeHtml(l.title)}</span>
                    <i class="bi bi-arrow-right-short text-muted"></i>
                `;
                item.addEventListener('click', function () {
                    setFocusLesson(l.title);
                    elements.chatInput.value = `Explain the key concepts of the lesson "${l.title}".`;
                    elements.chatInput.focus();
                });
                elements.lessonsList.appendChild(item);
            });

            // Re-apply focus lesson highlight if set
            if (state.lesson) {
                setFocusLesson(state.lesson);
            }
        } catch (e) {
            elements.lessonsList.innerHTML = '<div class="text-muted small p-2">Lessons ready. Click any prompt below.</div>';
        }
    }

    function setMode(newMode) {
        state.mode = newMode;
        elements.modeBtns.forEach(function (btn) {
            if (btn.getAttribute('data-mode') === newMode) {
                btn.classList.add('active', 'btn-primary');
                btn.classList.remove('btn-outline-secondary');
            } else {
                btn.classList.remove('active', 'btn-primary');
                btn.classList.add('btn-outline-secondary');
            }
        });
    }

    function bindQuickActions(parent) {
        parent.querySelectorAll('.js-quick-action').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const prompt = btn.getAttribute('data-prompt') || btn.textContent.trim();
                const mode = btn.getAttribute('data-mode');
                if (mode) setMode(mode);
                sendMessage(prompt);
            });
        });
    }

    // --- EVENT LISTENERS ---

    // Send button click
    elements.sendBtn.addEventListener('click', function () {
        sendMessage();
    });

    // Enter to send (Shift+Enter for newline)
    elements.chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-expanding textarea
    elements.chatInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 140) + 'px';
    });

    // Mode buttons
    elements.modeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setMode(this.getAttribute('data-mode'));
        });
    });

    // Course selector change
    if (elements.courseSelect) {
        elements.courseSelect.addEventListener('change', function () {
            state.courseId = this.value;
            setFocusLesson('');
            loadCourseLessons(state.courseId);
            loadHistory();
        });
    }

    // Clear focus lesson
    if (elements.clearLessonBtn) {
        elements.clearLessonBtn.addEventListener('click', function () {
            setFocusLesson('');
        });
    }

    // Clear chat history
    if (elements.clearChatBtn) {
        elements.clearChatBtn.addEventListener('click', function () {
            if (confirm('Clear chat history for this course?')) {
                state.history = [];
                localStorage.removeItem(getStorageKey());
                renderHistory();
            }
        });
    }

    // Initial setup
    const initialLesson = elements.focusLessonText ? elements.focusLessonText.getAttribute('data-lesson') : '';
    if (initialLesson) {
        setFocusLesson(initialLesson);
    }
    loadHistory();
    if (state.courseId) {
        loadCourseLessons(state.courseId);
    }
    bindQuickActions(document);
})();
