<?php
// includes/Logger.php

class Logger {
    /**
     * Records a system activity log.
     *
     * @param mysqli $conn The database connection.
     * @param int $user_id The ID of the user performing the action.
     * @param string $action A brief description of the action.
     * @param string|null $details More detailed information about the action.
     * @return bool True on success, false on failure.
     */
    public static function log(mysqli $conn, int $user_id, string $action, ?string $details = null): bool {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $sql = "INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    /**
     * Fetches recent system logs.
     *
     * @param mysqli $conn The database connection.
     * @param int $limit Number of logs to fetch.
     * @return array Array of log entries.
     */
    public static function getRecentLogs(mysqli $conn, int $limit = 20): array {
        $sql = "SELECT l.*, u.first_name, u.last_name, u.role 
                FROM system_logs l 
                JOIN users u ON l.user_id = u.id 
                ORDER BY l.created_at DESC 
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = dfps_fetch_all($result);
        $stmt->close();
        
        return $logs;
    }
}
?>
