<?php
session_start();

class SessionManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->cleanExpiredSessions();
    }
    
    public function createSession($userType, $userId, $data = []) {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 days
        
        $query = "INSERT INTO sessions (id, user_type, user_id, data, expires_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$sessionId, $userType, $userId, json_encode($data), $expiresAt]);
        
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_data'] = $data;
        
        return $sessionId;
    }
    
    public function getSession() {
        if (!isset($_SESSION['session_id'])) {
            return null;
        }
        
        $query = "SELECT * FROM sessions WHERE id = ? AND expires_at > NOW()";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$_SESSION['session_id']]);
        
        if ($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'user_type' => $session['user_type'],
                'user_id' => $session['user_id'],
                'data' => json_decode($session['data'], true)
            ];
        }
        
        return null;
    }
    
    public function destroySession() {
        if (isset($_SESSION['session_id'])) {
            $query = "DELETE FROM sessions WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$_SESSION['session_id']]);
        }
        
        session_destroy();
    }
    
    private function cleanExpiredSessions() {
        $query = "DELETE FROM sessions WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
    }
}
?>
