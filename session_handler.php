<?php
class MySQLSessionHandler implements SessionHandlerInterface {
    private $conn;
    private $table = 'sessions';

    public function open($savePath, $sessionName): bool {
        $this->conn = new mysqli('basedatosprueba.cx4442k4w78b.us-east-1.rds.amazonaws.com', 'admin', 'admin123***', 'blog_personal');
        return $this->conn ? true : false;
    }

    public function close() : bool {
        return $this->conn->close();
    }

    public function read($session_id): string|false  {
        $stmt = $this->conn->prepare("SELECT data FROM $this->table WHERE id = ? LIMIT 1");
        $stmt->bind_param('s', $session_id);
        $stmt->execute();
        $stmt->bind_result($data);
        $stmt->fetch();
        return $data ? $data : '';
    }

    public function write($session_id, $data): bool  {
        $stmt = $this->conn->prepare("REPLACE INTO $this->table (id, data, timestamp) VALUES (?, ?, ?)");
        $time = time();
        $stmt->bind_param('ssi', $session_id, $data, $time);
        return $stmt->execute();
    }

    public function destroy($session_id): bool  {
        $stmt = $this->conn->prepare("DELETE FROM $this->table WHERE id = ?");
        $stmt->bind_param('s', $session_id);
        return $stmt->execute();
    }

    public function gc($maxlifetime): int|false {
        $stmt = $this->conn->prepare("DELETE FROM $this->table WHERE timestamp < ?");
        $old = time() - $maxlifetime;
        $stmt->bind_param('i', $old);
        return $stmt->execute();
    }
}
