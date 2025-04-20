<?php
class Logger {
    private $db;
    private static $instance = null;
    private $logPath;
    private $errorLogPath;
    private $accessLogPath;
    private $activityLogPath;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->logPath = LOG_PATH;
        $this->errorLogPath = ERROR_LOG_FILE;
        $this->accessLogPath = ACCESS_LOG_FILE;
        $this->activityLogPath = ACTIVITY_LOG_FILE;

        // Ensure log directory exists
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }

        // Rotate logs if needed
        $this->rotateLogsIfNeeded();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function logActivity($userId, $action, $details = '') {
        try {
            // Log to database
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'details' => is_array($details) ? json_encode($details) : $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'level' => 'info'
            ];

            $this->db->insert('activity_logs', $data);

            // Log to file
            $logEntry = sprintf(
                "[%s] [%s] [User: %s] [IP: %s] %s - %s\n",
                date('Y-m-d H:i:s'),
                strtoupper($data['level']),
                $userId ?? 'Guest',
                $data['ip_address'],
                $action,
                $data['details']
            );

            file_put_contents($this->activityLogPath, $logEntry, FILE_APPEND);

            return true;
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }

    public function logError($userId, $message, $context = []) {
        try {
            // Log to database
            $data = [
                'user_id' => $userId,
                'action' => 'error',
                'details' => json_encode([
                    'message' => $message,
                    'context' => $context
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'level' => 'error'
            ];

            $this->db->insert('activity_logs', $data);

            // Log to error file
            $logEntry = sprintf(
                "[%s] [ERROR] [User: %s] [IP: %s] %s - %s\n",
                date('Y-m-d H:i:s'),
                $userId ?? 'Guest',
                $data['ip_address'],
                $message,
                json_encode($context)
            );

            file_put_contents($this->errorLogPath, $logEntry, FILE_APPEND);

            return true;
        } catch (Exception $e) {
            error_log("Error logging error: " . $e->getMessage());
            return false;
        }
    }

    public function logAccess($userId, $route, $method, $status) {
        try {
            $logEntry = sprintf(
                "[%s] [ACCESS] [User: %s] [IP: %s] [%s] %s - %s\n",
                date('Y-m-d H:i:s'),
                $userId ?? 'Guest',
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $method,
                $route,
                $status
            );

            file_put_contents($this->accessLogPath, $logEntry, FILE_APPEND);

            return true;
        } catch (Exception $e) {
            error_log("Error logging access: " . $e->getMessage());
            return false;
        }
    }

    public function logWarning($userId, $message, $context = []) {
        try {
            // Log to database
            $data = [
                'user_id' => $userId,
                'action' => 'warning',
                'details' => json_encode([
                    'message' => $message,
                    'context' => $context
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'level' => 'warning'
            ];

            $this->db->insert('activity_logs', $data);

            // Log to file
            $logEntry = sprintf(
                "[%s] [WARNING] [User: %s] [IP: %s] %s - %s\n",
                date('Y-m-d H:i:s'),
                $userId ?? 'Guest',
                $data['ip_address'],
                $message,
                json_encode($context)
            );

            file_put_contents($this->errorLogPath, $logEntry, FILE_APPEND);

            return true;
        } catch (Exception $e) {
            error_log("Error logging warning: " . $e->getMessage());
            return false;
        }
    }

    public function getActivityLogs($userId = null, $limit = 100, $offset = 0) {
        try {
            $where = $userId ? 'user_id = ?' : '1';
            $params = $userId ? [$userId] : [];
            
            return $this->db->fetchAll(
                "SELECT * FROM activity_logs 
                WHERE $where 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            );
        } catch (Exception $e) {
            error_log("Error getting activity logs: " . $e->getMessage());
            return [];
        }
    }

    public function getErrorLogs($limit = 100, $offset = 0) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM activity_logs 
                WHERE level = 'error' 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?",
                [$limit, $offset]
            );
        } catch (Exception $e) {
            error_log("Error getting error logs: " . $e->getMessage());
            return [];
        }
    }

    public function clearOldLogs($days = 30) {
        try {
            // Clear database logs
            $this->db->delete(
                'activity_logs',
                'created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
                [$days]
            );

            // Clear file logs
            $this->rotateLogsIfNeeded(true);

            return true;
        } catch (Exception $e) {
            error_log("Error clearing old logs: " . $e->getMessage());
            return false;
        }
    }

    private function rotateLogsIfNeeded($force = false) {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $logFiles = [
            $this->errorLogPath,
            $this->accessLogPath,
            $this->activityLogPath
        ];

        foreach ($logFiles as $logFile) {
            if (!file_exists($logFile)) {
                continue;
            }

            if ($force || filesize($logFile) > $maxSize) {
                $backupFile = $logFile . '.' . date('Y-m-d-H-i-s') . '.bak';
                rename($logFile, $backupFile);
                
                // Compress old log
                if (file_exists($backupFile)) {
                    $zip = new ZipArchive();
                    $zipFile = $backupFile . '.zip';
                    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                        $zip->addFile($backupFile, basename($backupFile));
                        $zip->close();
                        unlink($backupFile);
                    }
                }

                // Delete old zipped logs (keep last 5)
                $oldLogs = glob($logFile . '.*.zip');
                rsort($oldLogs);
                foreach (array_slice($oldLogs, 5) as $oldLog) {
                    unlink($oldLog);
                }
            }
        }
    }

    public function exportLogs($type = 'activity', $format = 'csv') {
        try {
            $logs = [];
            switch ($type) {
                case 'activity':
                    $logs = $this->getActivityLogs(null, 1000);
                    break;
                case 'error':
                    $logs = $this->getErrorLogs(1000);
                    break;
                default:
                    throw new Exception("Invalid log type");
            }

            if (empty($logs)) {
                return false;
            }

            $filename = sprintf(
                '%s_logs_%s.%s',
                $type,
                date('Y-m-d_H-i-s'),
                $format
            );

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($logs, $filename);
                case 'json':
                    return $this->exportToJson($logs, $filename);
                default:
                    throw new Exception("Invalid export format");
            }
        } catch (Exception $e) {
            error_log("Error exporting logs: " . $e->getMessage());
            return false;
        }
    }

    private function exportToCsv($logs, $filename) {
        $filepath = $this->logPath . '/' . $filename;
        $fp = fopen($filepath, 'w');
        
        // Write headers
        fputcsv($fp, array_keys($logs[0]));
        
        // Write data
        foreach ($logs as $log) {
            fputcsv($fp, $log);
        }
        
        fclose($fp);
        return $filepath;
    }

    private function exportToJson($logs, $filename) {
        $filepath = $this->logPath . '/' . $filename;
        file_put_contents($filepath, json_encode($logs, JSON_PRETTY_PRINT));
        return $filepath;
    }

    private function __clone() {}
    private function __wakeup() {}
}
?>
