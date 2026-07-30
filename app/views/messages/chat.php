<?php
// app/views/messages/chat.php
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

$myId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
?>

<style>
/* Chat Interface Premium Custom Styles */
.messages-page {
    min-width: 0;
}

.chat-container-card {
    height: calc(100vh - 170px);
    min-height: 500px;
    border-radius: 16px;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.contacts-pane {
    border-right: 1px solid #eef2f6;
    background-color: #fcfdfe;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}

.contacts-header {
    padding: 1.25rem;
    border-bottom: 1px solid #eef2f6;
    background-color: #ffffff;
}

.contacts-list {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f8fafc;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.contact-item:hover {
    background-color: #f1f5f9;
}

.contact-item.active {
    background-color: #e0e7ff;
    border-left: 4px solid var(--primary-color);
}

.chat-pane {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    background-color: #f8fafc;
}

.chat-header {
    padding: 1.25rem;
    border-bottom: 1px solid #eef2f6;
    background-color: #ffffff;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-height: 0;
}

.message-bubble {
    max-width: 70%;
    padding: 0.85rem 1.15rem;
    border-radius: 16px;
    font-size: 0.95rem;
    line-height: 1.5;
    position: relative;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    overflow-wrap: anywhere;
}

.message-sent {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: #ffffff;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}

.message-received {
    background-color: #ffffff;
    color: var(--text-main);
    align-self: flex-start;
    border-bottom-left-radius: 4px;
    border: 1px solid #eef2f6;
}

.message-time {
    font-size: 0.75rem;
    margin-top: 0.35rem;
    display: block;
    text-align: right;
    opacity: 0.75;
}

.message-received .message-time {
    color: var(--text-muted);
}

.chat-footer {
    padding: 1.25rem;
    background-color: #ffffff;
    border-top: 1px solid #eef2f6;
}

.avatar-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.empty-chat-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
    padding: 3rem;
}

.empty-chat-icon {
    font-size: 4rem;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1.5rem;
    opacity: 0.8;
}

/* Scrollbar tweaks */
.messages-area::-webkit-scrollbar, .contacts-list::-webkit-scrollbar {
    width: 6px;
}
.messages-area::-webkit-scrollbar-thumb, .contacts-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

@media (max-width: 767.98px) {
    .messages-page {
        margin: -1rem;
        height: calc(100dvh - 88px);
        min-height: 420px;
    }

    .messages-page > .row {
        --bs-gutter-x: 0;
    }

    .contacts-pane {
        display: <?php echo $activeContactId ? 'none' : 'flex'; ?>;
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
        border-right: 0;
    }

    .chat-pane {
        display: <?php echo $activeContactId ? 'flex' : 'none'; ?>;
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
    }

    .chat-container-card {
        height: 100%;
        min-height: 0;
        border-radius: 0;
        border-left: 0 !important;
        border-right: 0 !important;
        box-shadow: none;
    }

    .contacts-header {
        padding: 1rem;
    }

    .contact-item {
        padding: 0.9rem 1rem;
    }

    .chat-header {
        padding: 0.85rem 1rem;
        min-height: 72px;
    }

    .chat-header .btn {
        width: 40px;
        height: 40px;
        padding: 0;
        border-radius: 10px;
    }

    .chat-header .btn .back-label {
        display: none;
    }

    .messages-area {
        padding: 1rem;
        gap: 0.75rem;
    }

    .message-bubble {
        max-width: 86%;
        padding: 0.75rem 0.9rem;
        font-size: 0.92rem;
        border-radius: 14px;
    }

    .chat-footer {
        padding: 0.75rem;
    }

    #chatForm {
        align-items: center;
    }

    #messageInput {
        min-width: 0;
        padding: 0.85rem 1rem !important;
    }

    #chatForm .btn {
        width: 46px;
        height: 46px;
        padding: 0 !important;
        flex: 0 0 46px;
        border-radius: 12px;
    }

    .empty-chat-state {
        padding: 1.5rem;
        min-height: 360px;
    }

    .empty-chat-icon {
        font-size: 3rem;
    }
}
</style>

<div class="messages-page container-fluid p-0 p-md-2">
    <div class="row m-0 chat-container-card border">
        
        <!-- Contacts Sidebar List -->
        <div class="col-md-4 col-lg-3 p-0 contacts-pane">
            <div class="contacts-header">
                <h5 class="fw-bold mb-3 text-dark">
                    <i class="bi bi-people me-2 text-primary"></i>Contacts
                </h5>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="contactSearch" class="form-control bg-light border-0" placeholder="Search contacts...">
                </div>
            </div>
            
            <div class="contacts-list">
                <?php if (empty($contacts)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-person-x fs-2 mb-2 d-block"></i>
                        No contacts available.
                    </div>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): 
                        // Generate color hash based on username
                        $hash = md5($contact['Username']);
                        $hue = hexdec(substr($hash, 0, 2)) % 360;
                        $bg = "hsl($hue, 75%, 92%)";
                        $fg = "hsl($hue, 85%, 28%)";
                        $isActive = ($activeContactId == $contact['UserID']) ? 'active' : '';
                        $initial = strtoupper(substr($contact['Username'], 0, 1));
                        $unreadCount = $unreadCounts[$contact['UserID']] ?? 0;
                        $isOnline = !empty($contact['IsOnline']);
                    ?>
                        <a href="?page=messages&contact_id=<?= $contact['UserID'] ?>" class="contact-item <?= $isActive ?>" data-username="<?= htmlspecialchars(strtolower($contact['Username'])) ?>">
                            <div class="avatar-circle me-3" style="background-color: <?= $bg ?>; color: <?= $fg ?>;">
                                <?= $initial ?>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold text-dark text-truncate"><?= htmlspecialchars($contact['Username']) ?></h6>
                                    <span class="badge <?= $isOnline ? 'bg-success' : 'bg-secondary' ?> ms-2"><?= $isOnline ? 'Active' : 'Inactive' ?></span>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted text-truncate d-block"><?= htmlspecialchars($contact['Email']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Conversation Window -->
        <div class="col-md-8 col-lg-9 p-0 chat-pane">
            <?php if ($activeContact): 
                $hash = md5($activeContact['Username']);
                $hue = hexdec(substr($hash, 0, 2)) % 360;
                $activeBg = "hsl($hue, 75%, 92%)";
                $activeFg = "hsl($hue, 85%, 28%)";
                $activeInitial = strtoupper(substr($activeContact['Username'], 0, 1));
                $isOnline = !empty($activeContact['IsOnline']);
            ?>
                <!-- Chat Header -->
                <div class="chat-header">
                    <a href="?page=messages" class="btn btn-sm btn-outline-secondary d-md-none me-3">
                        <i class="bi bi-chevron-left"></i><span class="back-label ms-1">Back</span>
                    </a>
                    <div class="avatar-circle me-3" style="background-color: <?= $activeBg ?>; color: <?= $activeFg ?>;">
                        <?= $activeInitial ?>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($activeContact['Username']) ?></h6>
                        <small class="text-muted d-flex align-items-center gap-2">
                            <span class="badge bg-soft-primary text-primary"><?= $activeContact['UserType'] ?></span>
                            <span class="badge <?= $isOnline ? 'bg-success' : 'bg-secondary' ?>"><?= $isOnline ? 'Active now' : 'Inactive' ?></span>
                        </small>
                    </div>
                </div>

                <!-- Chat Messages Body -->
                <div class="messages-area" id="messagesArea">
                    <!-- Loaded dynamically via JS -->
                </div>

                <!-- Chat Input Footer -->
                <div class="chat-footer">
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="hidden" id="receiverId" value="<?= $activeContact['UserID'] ?>">
                        <input type="text" id="messageInput" class="form-control border-0 bg-light py-3 px-4" placeholder="Type a message..." autocomplete="off" required>
                        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center justify-content-center">
                            <i class="bi bi-send-fill fs-5"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Empty Chat State -->
                <div class="empty-chat-state">
                    <i class="bi bi-chat-text-fill empty-chat-icon"></i>
                    <h4 class="fw-bold text-dark">Your Messages</h4>
                    <p class="text-center max-w-400">
                        <?= ($userType === 'Student') 
                            ? 'Select an instructor from the left list to begin a discussion about your courses.' 
                            : 'Select a student from the left list to communicate with them.'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side contact search filter
    const contactSearch = document.getElementById('contactSearch');
    if (contactSearch) {
        contactSearch.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const contactItems = document.querySelectorAll('.contact-item');
            
            contactItems.forEach(item => {
                const username = item.getAttribute('data-username');
                if (username.includes(query)) {
                    item.style.setProperty('display', 'flex', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        });
    }

    // Realtime polling and message sending when a chat is active
    const messagesArea = document.getElementById('messagesArea');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const receiverId = document.getElementById('receiverId') ? document.getElementById('receiverId').value : null;
    
    if (receiverId && messagesArea) {
        let lastMessageId = 0;
        let isFirstLoad = true;
        let pollTimer = null;

        // Formats database timestamp to human-friendly local time
        function formatTime(timestampString) {
            try {
                // If it is SQL datetime string 'YYYY-MM-DD HH:MM:SS'
                const date = new Date(timestampString.replace(/-/g, '/'));
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                return '';
            }
        }

        // Fetches message updates from the server
        function fetchMessages() {
            const url = `?page=api-get-messages&contact_id=${receiverId}&since_id=${lastMessageId}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.messages.length > 0) {
                        let hasNewMessages = false;
                        
                        data.messages.forEach(msg => {
                            const isMe = msg.SenderID != receiverId;
                            const bubbleClass = isMe ? 'message-sent' : 'message-received';
                            
                            const msgDiv = document.createElement('div');
                            msgDiv.className = `message-bubble ${bubbleClass}`;
                            
                            const textSpan = document.createElement('span');
                            textSpan.innerText = msg.MessageText;
                            msgDiv.appendChild(textSpan);

                            const timeSpan = document.createElement('span');
                            timeSpan.className = 'message-time';
                            timeSpan.innerText = formatTime(msg.SentAt);
                            msgDiv.appendChild(timeSpan);

                            messagesArea.appendChild(msgDiv);
                            
                            if (msg.MessageID > lastMessageId) {
                                lastMessageId = msg.MessageID;
                            }
                            hasNewMessages = true;
                        });

                        // Automatically scroll to the bottom on first load or new message arrivals
                        if (isFirstLoad || hasNewMessages) {
                            messagesArea.scrollTop = messagesArea.scrollHeight;
                            isFirstLoad = false;
                        }
                    } else if (isFirstLoad) {
                        // Empty conversation state
                        messagesArea.innerHTML = `
                            <div class="text-center text-muted my-auto py-5">
                                <i class="bi bi-chat-left-dots fs-1 mb-2 d-block text-primary" style="opacity:0.5;"></i>
                                No messages yet. Say hello to start the conversation!
                            </div>
                        `;
                        isFirstLoad = false;
                    }
                })
                .catch(error => {
                    console.error('Failed to load messages:', error);
                })
                .finally(() => {
                    // Schedule next poll in 2 seconds
                    pollTimer = setTimeout(fetchMessages, 2000);
                });
        }

        // Start polling immediately
        fetchMessages();

        // Submit/Send Message Handler
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const text = messageInput.value.trim();
            if (text === '') return;

            // Clear input box immediately for snappy user experience
            messageInput.value = '';

            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('message_text', text);

            fetch('?page=api-send-message', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Trigger immediate check to render our newly sent message instantly
                    if (pollTimer) clearTimeout(pollTimer);
                    fetchMessages();
                } else {
                    // Show error notification using SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message || 'Failed to send message.'
                    });
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Unable to connect to the server.'
                });
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
