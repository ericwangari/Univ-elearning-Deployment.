<?php
// app/controllers/MessageController.php

class MessageController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function tableHasColumn($table, $column) {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            return (bool) $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    private function updateLastActive($userId) {
        if (!$this->tableHasColumn('users', 'LastActiveAt')) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE users SET LastActiveAt = NOW() WHERE UserID = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log('Presence update failed: ' . $e->getMessage());
        }
    }

    private function isUserActive($lastActiveAt) {
        if (empty($lastActiveAt)) {
            return false;
        }

        try {
            $lastSeen = new DateTime($lastActiveAt);
            $threshold = (new DateTime())->modify('-5 minutes');
            return $lastSeen >= $threshold;
        } catch (Exception $e) {
            return false;
        }
    }

    // Renders the main chat box page
    public function chat() {
        if (!isLoggedIn()) {
            redirect('?page=login');
        }

        $myId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'];
        
        $this->updateLastActive($myId);

        // Load potential contacts list
        if ($userType === 'Student') {
            // Students can message any approved instructor
            $stmt = $this->pdo->prepare("SELECT UserID, Username, Email, LastActiveAt FROM users WHERE UserType = 'Instructor' AND Status = 'Approved' ORDER BY Username ASC");
            $stmt->execute();
            $contacts = $stmt->fetchAll();
        } else if ($userType === 'Instructor') {
            // Instructors can message any student
            $stmt = $this->pdo->prepare("SELECT UserID, Username, Email, LastActiveAt FROM users WHERE UserType = 'Student' ORDER BY Username ASC");
            $stmt->execute();
            $contacts = $stmt->fetchAll();
        } else {
            // Admins are not part of the instructor-student messaging system
            die("Unauthorized user role for chat.");
        }

        // Get unread message counts for each contact
        $unreadCounts = [];
        foreach ($contacts as $contact) {
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) as unread FROM messages WHERE SenderID = ? AND ReceiverID = ? AND IsRead = 0");
            $countStmt->execute([$contact['UserID'], $myId]);
            $result = $countStmt->fetch();
            $unreadCounts[$contact['UserID']] = $result['unread'];
        }

        foreach ($contacts as &$contact) {
            $contact['IsOnline'] = $this->isUserActive($contact['LastActiveAt'] ?? null);
        }
        unset($contact);

        // Get details of active contact if any
        $activeContact = null;
        $activeContactId = $_GET['contact_id'] ?? null;
        
        if ($activeContactId) {
            $stmt = $this->pdo->prepare("SELECT UserID, Username, Email, UserType, LastActiveAt FROM users WHERE UserID = ?");
            $stmt->execute([$activeContactId]);
            $activeContact = $stmt->fetch();
            
            // Check if active contact is of opposite role (Student/Instructor separation)
            if (!$activeContact || $activeContact['UserType'] === $userType || $activeContact['UserID'] == $myId) {
                $activeContact = null;
                $activeContactId = null;
            } else {
                $activeContact['IsOnline'] = $this->isUserActive($activeContact['LastActiveAt'] ?? null);
            }
        }

        require __DIR__ . '/../views/messages/chat.php';
    }

    // JSON API Endpoint to get message history
    public function getMessages() {
        header('Content-Type: application/json');
        
        if (!isLoggedIn()) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $myId = $_SESSION['user_id'];
        $this->updateLastActive($myId);
        $contactId = $_GET['contact_id'] ?? null;
        $sinceId = $_GET['since_id'] ?? 0;

        if (!$contactId) {
            echo json_encode(['status' => 'error', 'message' => 'Missing contact ID']);
            exit;
        }

        // Mark incoming messages as read
        $updateStmt = $this->pdo->prepare("UPDATE messages SET IsRead = 1 WHERE SenderID = ? AND ReceiverID = ? AND IsRead = 0");
        $updateStmt->execute([$contactId, $myId]);

        // Fetch messages between current user and contact
        $stmt = $this->pdo->prepare("
            SELECT MessageID, SenderID, ReceiverID, MessageText, SentAt, IsRead
            FROM messages 
            WHERE (
                (SenderID = ? AND ReceiverID = ?) OR 
                (SenderID = ? AND ReceiverID = ?)
            ) 
            AND MessageID > ?
            ORDER BY SentAt ASC, MessageID ASC
        ");
        $stmt->execute([$myId, $contactId, $contactId, $myId, $sinceId]);
        $messages = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'messages' => $messages]);
        exit;
    }

    // JSON API Endpoint to send a message
    public function sendMessage() {
        header('Content-Type: application/json');

        if (!isLoggedIn()) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'POST request required']);
            exit;
        }

        $myId = $_SESSION['user_id'];
        $this->updateLastActive($myId);
        $receiverId = $_POST['receiver_id'] ?? null;
        $messageText = trim($_POST['message_text'] ?? '');

        if (!$receiverId || $messageText === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data provided']);
            exit;
        }

        // Verify receiver exists and is of opposite role
        $stmt = $this->pdo->prepare("SELECT UserID, UserType FROM users WHERE UserID = ?");
        $stmt->execute([$receiverId]);
        $receiver = $stmt->fetch();

        if (!$receiver || $receiver['UserType'] === $_SESSION['user_type']) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid receiver']);
            exit;
        }

        // Insert message
        $insertStmt = $this->pdo->prepare("
            INSERT INTO messages (SenderID, ReceiverID, MessageText, IsRead)
            VALUES (?, ?, ?, 0)
        ");
        $insertStmt->execute([$myId, $receiverId, $messageText]);
        $messageId = $this->pdo->lastInsertId();

        // Get inserted message details
        $msgStmt = $this->pdo->prepare("SELECT * FROM messages WHERE MessageID = ?");
        $msgStmt->execute([$messageId]);
        $message = $msgStmt->fetch();

        echo json_encode(['status' => 'success', 'message' => $message]);
        exit;
    }
}
