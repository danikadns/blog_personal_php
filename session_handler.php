<?php
class MySQLSessionHandler implements SessionHandlerInterface {
    private $conn;
    private $table = 'sessions';

    public function open($savePath, $sessionName): bool {
        include 'db.php';
        $this->conn = $conn;
        return true;
    }

    public function close(): bool {
        return $this->conn->close();
    }

    public function read($session_id): string|false {
        $stmt = $this->conn->prepare("SELECT data FROM $this->table WHERE id = ? LIMIT 1");
        $stmt->bind_param('s', $session_id);
        $stmt->execute();
        $stmt->bind_result($data);
        $stmt->fetch();
        $stmt->close();
        return $data ?: '';
    }

    public function write($session_id, $data): bool {
        $stmt = $this->conn->prepare("REPLACE INTO $this->table (id, data, timestamp) VALUES (?, ?, ?)");
        $timestamp = time();
        $stmt->bind_param('ssi', $session_id, $data, $timestamp);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function destroy($session_id): bool {
        $stmt = $this->conn->prepare("DELETE FROM $this->table WHERE id = ?");
        $stmt->bind_param('s', $session_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function gc($maxlifetime): int|false {
        $stmt = $this->conn->prepare("DELETE FROM $this->table WHERE timestamp < ?");
        $old = time() - $maxlifetime;
        $stmt->bind_param('i', $old);
        $stmt->execute();
        $stmt->close();
        return $this->conn->affected_rows;
    }
}
?>
