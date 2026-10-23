<?php
$page_title = 'AI Teacher';
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar_v2.php';

$h = function ($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
};

$aiStatusText = 'Built-in Pedagogical Mode';
$aiStatusClass = 'bg-light text-muted border';
if (defined('AI_TEACHER_API_KEY') && AI_TEACHER_API_KEY !== '') {
    $aiStatusText = 'OpenAI AI Connected';
    $aiStatusClass = 'bg-soft-success text-success border-success';
} elseif (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
    $aiStatusText = 'Gemini AI Connected';
    $aiStatusClass = 'bg-soft-success text-success border-success';
}
?>

<style>
.ai-teacher-chat-card {
    height: 720px;
    max-height: calc(100vh - 180px);
    display: flex;
    flex-direction: column;
    border-radius: 16px;
}
.ai-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: #f8fafc;
    border-radius: 12px;
}
.ai-message {
    display: flex;
    gap: 0.75rem;
    max-width: 85%;
    animation: fadeIn 0.25s ease-out;
}
.ai-message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
}
.ai-message.assistant {
    align-self: flex-start;
}
.ai-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}
.ai-message.assistant .ai-avatar {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
}
.ai-message.user .ai-avatar {
    background: #e2e8f0;
    color: #475569;
}
.ai-bubble {
    padding: 0.85rem 1.15rem;
    border-radius: 16px;
    line-height: 1.55;
    position: relative;
    font-size: 0.95rem;
    word-break: break-word;
}
.ai-message.assistant .ai-bubble {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #1e293b;
    border-top-left-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}
.ai-message.user .ai-bubble {
    background: var(--primary-color, #4f46e5);
    color: #ffffff;
    border-top-right-radius: 4px;
}
.ai-bubble p:last-child {
    margin-bottom: 0;
}
.ai-bubble pre {
    background: #1e293b;
    color: #f8fafc;
    padding: 0.75rem;
    border-radius: 8px;
    overflow-x: auto;
    margin: 0.5rem 0;
    font-size: 0.85rem;
}
.ai-bubble code {
    background: rgba(0, 0, 0, 0.06);
    padding: 0.15rem 0.35rem;
    border-radius: 4px;
    font-size: 0.85em;
}
.ai-message.user .ai-bubble code {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}
.ai-typing-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.5rem 0.85rem;
}
.ai-typing-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #94a3b8;
    animation: typingPulse 1.4s infinite ease-in-out;
}
.ai-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.ai-typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingPulse {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-4px); opacity: 1; }
}
.ai-quick-btn {
    font-size: 0.8rem;
    border-radius: 9999px;
    padding: 0.35rem 0.75rem;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.ai-quick-btn:hover {
    background: #f1f5f9;
    color: var(--primary-color, #4f46e5);
    border-color: var(--primary-color, #4f46e5);
}
.ai-lesson-item {
    cursor: pointer;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    transition: all 0.15s ease;
    font-size: 0.88rem;
}
.ai-lesson-item:hover {
    background: #f1f5f9;
}
.ai-lesson-item.active {
    background: #e0e7ff;
    color: #3730a3;
    font-weight: 600;
}
</style>

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-soft-primary text-primary">Interactive 1-on-1 Academic Tutor</span>
                <span class="badge <?php echo $aiStatusClass; ?>">
                    <i class="bi bi-cpu me-1"></i><?php echo $aiStatusText; ?>
                </span>
            </div>
            <h1 class="fw-bold mb-1">AI Teacher</h1>
            <p class="text-muted mb-0">Ask questions, get step-by-step explanations, generate practice problems, and master your courses.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="aiClearChatBtn" title="Clear current chat">
                <i class="bi bi-trash3 me-1"></i> Clear Chat
            </button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar: Course Context & Lessons -->
        <div class="col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm p-3 mb-4">
                <label for="aiCourseSelect" class="form-label fw-bold text-dark mb-2">
                    <i class="bi bi-mortarboard me-1 text-primary"></i> Course Context
                </label>
                <select class="form-select shadow-none mb-3" id="aiCourseSelect">
                    <option value="">General Academic Support</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $h($c['CourseID']); ?>" <?php echo (string)$selectedCourseId === (string)$c['CourseID'] ? 'selected' : ''; ?>>
                            <?php echo $h($c['CourseName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-semibold text-muted small mb-0 text-uppercase" style="letter-spacing: 0.5px;">
                        Course Lessons
                    </label>
                    <small class="text-muted">Click to focus</small>
                </div>
                <div class="ai-lessons-list" id="aiLessonsList" style="max-height: 280px; overflow-y: auto;">
                    <?php if (!empty($lessons)): ?>
                        <?php foreach ($lessons as $l): ?>
                            <div class="ai-lesson-item d-flex align-items-center justify-content-between mb-1 <?php echo strcasecmp($selectedLesson, $l['title']) === 0 ? 'active' : ''; ?>"
                                 data-lesson-title="<?php echo $h($l['title']); ?>">
                                <span class="text-truncate me-2"><i class="bi bi-journal-bookmark me-1 text-primary"></i> <?php echo $h($l['title']); ?></span>
                                <i class="bi bi-arrow-right-short text-muted"></i>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted small p-2">Select a course to view lessons.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teaching Modes Quick Info -->
            <div class="card border-0 shadow-sm p-3 bg-light">
                <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-1"></i> Tutor Modes</h6>
                <p class="small text-muted mb-2">Switch teaching styles depending on what you need:</p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><strong>Explain:</strong> In-depth concept breakdown</li>
                    <li class="mb-1"><strong>Simplify:</strong> ELI5 analogies and plain language</li>
                    <li class="mb-1"><strong>Example:</strong> Real-world code and case studies</li>
                    <li class="mb-1"><strong>Quiz Me:</strong> Interactive Socratic Q&A</li>
                    <li class="mb-1"><strong>Study Plan:</strong> Day-by-day mastery roadmap</li>
                </ul>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="col-lg-8 col-xl-9">
            <div class="card border-0 shadow-sm ai-teacher-chat-card p-3">
                <!-- Top controls: Mode Pills & Focus Lesson Pill -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-2 border-bottom">
                    <!-- Mode Buttons -->
                    <div class="d-flex flex-wrap gap-1" role="group" aria-label="Tutor mode">
                        <button type="button" class="btn btn-sm btn-primary js-mode-btn" data-mode="explain">Explain</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-mode-btn" data-mode="simplify">Simplify (ELI5)</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-mode-btn" data-mode="example">Real Example</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-mode-btn" data-mode="quiz_me">Quiz Me 🎯</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-mode-btn" data-mode="practice">Practice</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-mode-btn" data-mode="plan">Study Plan</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-mode-btn" data-mode="summary">Summary</button>
                    </div>

                    <!-- Active Focus Lesson Pill -->
                    <div id="aiFocusLessonPill" class="<?php echo $selectedLesson !== '' ? '' : 'd-none'; ?>">
                        <span class="badge bg-soft-primary text-primary px-2 py-1 d-inline-flex align-items-center gap-1">
                            <i class="bi bi-bookmark-fill"></i>
                            <span id="aiFocusLessonText" data-lesson="<?php echo $h($selectedLesson); ?>"><?php echo $h($selectedLesson); ?></span>
                            <button type="button" class="btn-close btn-close-xs ms-1" id="aiClearLessonBtn" aria-label="Remove lesson focus" style="font-size: 0.6rem;"></button>
                        </span>
                    </div>
                </div>

                <!-- Messages Container -->
                <div class="ai-chat-messages" id="aiChatMessages">
                    <!-- Default Starter Content when empty -->
                    <div class="js-starter-wrapper text-center my-auto py-5" id="aiStarterPrompts">
                        <div class="ai-avatar mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.75rem;">
                            <i class="bi bi-stars"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Hello! How can I help you learn today?</h4>
                        <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                            I can break down tough concepts, guide you with real-world examples, test your knowledge with interactive questions, or build a study plan.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-2" style="max-width: 650px; margin: 0 auto;">
                            <button type="button" class="btn ai-quick-btn js-quick-action" data-mode="explain" data-prompt="Explain the most important core concept of this course with a clear breakdown.">
                                💡 Explain core concept
                            </button>
                            <button type="button" class="btn ai-quick-btn js-quick-action" data-mode="quiz_me" data-prompt="Quiz me! Give me a multiple choice question to test my understanding.">
                                🎯 Test my knowledge (Quiz me)
                            </button>
                            <button type="button" class="btn ai-quick-btn js-quick-action" data-mode="example" data-prompt="Give me a real-world project example showing how this topic is applied.">
                                🔍 Show a real-world example
                            </button>
                            <button type="button" class="btn ai-quick-btn js-quick-action" data-mode="simplify" data-prompt="Explain this course to me like I am 10 years old.">
                                🧸 Explain like I'm 5
                            </button>
                            <button type="button" class="btn ai-quick-btn js-quick-action" data-mode="plan" data-prompt="Create a practical 5-day study plan to master this material.">
                                📅 5-day study plan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Input Box -->
                <div class="pt-3 border-top mt-2">
                    <div class="input-group">
                        <textarea id="aiMessageInput" class="form-control border-end-0 shadow-none" rows="1"
                                  placeholder="Ask anything, describe what you're stuck on, or answer the quiz question... (Enter to send, Shift+Enter for new line)"
                                  style="resize: none;"></textarea>
                        <button class="btn btn-primary px-4 fw-bold" type="button" id="aiSendBtn">
                            <i class="bi bi-send-fill me-1"></i> Send
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted" style="font-size: 0.75rem;">
                        <span><i class="bi bi-shield-check me-1"></i> Academic Tutor · Safe & educational guidance</span>
                        <span>Press <kbd style="font-size: 0.7rem;">Enter</kbd> to send</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/ai-teacher.js?v=2"></script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
