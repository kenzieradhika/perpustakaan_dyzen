<?php
/**
 * PERPUSTAKAAN DYZEN - Enhanced Logger System with Archive Management
 * Version: 2.0 (2026)
 */

class Logger {
    private static $instance = null;
    private $log_dir;
    private $archive_dir;
    private $max_file_size = 5242880; // 5MB
    private $retention_days = 365; // Keep logs for 1 year
    private $current_year;
    private $current_month;
    
    private function __construct() {
        $this->log_dir = __DIR__ . '/../logs/';
        $this->current_year = date('Y');
        $this->current_month = date('m');
        $this->archive_dir = $this->log_dir . 'archive/' . $this->current_year . '/' . $this->current_month . '-M/';
        
        // Create directory structure
        $this->createDirectoryStructure();
        
        // Auto cleanup old logs (run occasionally)
        if (rand(1, 100) === 1) {
            $this->cleanupOldLogs();
        }
        
        // Rotate logs if needed
        $this->rotateIfNeeded();
    }
    
    /**
     * Create complete directory structure
     */
    private function createDirectoryStructure() {
        // Main logs directory
        if (!file_exists($this->log_dir)) {
            mkdir($this->log_dir, 0755, true);
        }
        
        // Create .htaccess for security
        $htaccess = $this->log_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# PERPUSTAKAAN DYZEN - Protect logs directory
Order Deny,Allow
Deny from all

# Only allow access from localhost for debugging
Allow from 127.0.0.1
Allow from ::1

<FilesMatch \"\.(log|txt|json)$\">
    Require all denied
</FilesMatch>
";
            file_put_contents($htaccess, $content);
        }
        
        // Create archive directory structure for current year
        $year_archive = $this->log_dir . 'archive/' . $this->current_year;
        if (!file_exists($year_archive)) {
            mkdir($year_archive, 0755, true);
        }
        
        // Create current month directory
        $month_names = [
            '01' => '01-Jan', '02' => '02-Feb', '03' => '03-Mar', '04' => '04-Apr',
            '05' => '05-May', '06' => '06-Jun', '07' => '07-Jul', '08' => '08-Aug',
            '09' => '09-Sep', '10' => '10-Oct', '11' => '11-Nov', '12' => '12-Dec'
        ];
        $month_name = $month_names[$this->current_month];
        $this->archive_dir = $year_archive . '/' . $month_name . '/';
        
        if (!file_exists($this->archive_dir)) {
            mkdir($this->archive_dir, 0755, true);
        }
        
        // Create archive index if not exists
        $index_file = $this->log_dir . 'archive/index.json';
        if (!file_exists($index_file)) {
            $this->createArchiveIndex();
        }
    }
    
    /**
     * Create archive index JSON
     */
    private function createArchiveIndex() {
        $index = [
            'created_at' => date('Y-m-d H:i:s'),
            'version' => '2.0',
            'years' => []
        ];
        
        $archive_path = $this->log_dir . 'archive/';
        $years = glob($archive_path . '*', GLOB_ONLYDIR);
        
        foreach ($years as $year_path) {
            $year = basename($year_path);
            if (is_numeric($year)) {
                $months = glob($year_path . '/*', GLOB_ONLYDIR);
                $index['years'][$year] = [];
                foreach ($months as $month_path) {
                    $month = basename($month_path);
                    $files = glob($month_path . '/*.log*');
                    $index['years'][$year][$month] = [
                        'count' => count($files),
                        'size' => $this->getDirectorySize($month_path)
                    ];
                }
            }
        }
        
        file_put_contents($this->log_dir . 'archive/index.json', json_encode($index, JSON_PRETTY_PRINT));
    }
    
    /**
     * Calculate directory size
     */
    private function getDirectorySize($dir) {
        $size = 0;
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->getDirectorySize($each);
        }
        return $size;
    }
    
    /**
     * Rotate log if too large
     */
    private function rotateIfNeeded() {
        $log_files = ['php-error.log', 'database.log', 'security.log', 'access.log', 'backup.log'];
        foreach ($log_files as $file) {
            $filepath = $this->log_dir . $file;
            if (file_exists($filepath) && filesize($filepath) > $this->max_file_size) {
                $this->rotateLog($file);
            }
        }
    }
    
    /**
     * Rotate log file to archive
     */
    private function rotateLog($filename) {
        $filepath = $this->log_dir . $filename;
        if (!file_exists($filepath)) return;
        
        $timestamp = date('Y-m-d_H-i-s');
        $archive_name = pathinfo($filename, PATHINFO_FILENAME) . "_{$timestamp}.log";
        $archive_path = $this->archive_dir . $archive_name;
        
        // Move current log to archive
        rename($filepath, $archive_path);
        
        // Create new empty log file
        file_put_contents($filepath, "");
        chmod($filepath, 0644);
        
        // Compress archived log
        $this->compressLog($archive_path);
        
        // Log rotation event
        $this->writeLog('backup.log', "LOG ROTATION: {$filename} rotated to archive", [
            'size' => filesize($archive_path),
            'archive' => $archive_name
        ]);
        
        // Update archive index
        $this->createArchiveIndex();
    }
    
    /**
     * Compress log file with gzip
     */
    private function compressLog($filepath) {
        if (!file_exists($filepath)) return;
        
        if (function_exists('gzopen')) {
            $gz_path = $filepath . '.gz';
            $gz = gzopen($gz_path, 'wb9');
            gzwrite($gz, file_get_contents($filepath));
            gzclose($gz);
            
            // Delete original if compression successful
            if (filesize($gz_path) > 0) {
                unlink($filepath);
            }
        }
    }
    
    /**
     * Write log entry
     */
    private function writeLog($filename, $message, $context = []) {
        $filepath = $this->log_dir . '/' . $filename;
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_id = $_SESSION['user_id'] ?? 'guest';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $context_str = !empty($context) ? ' | ' . json_encode($context) : '';
        $log_entry = "[{$timestamp}] [User: {$user_id}] [IP: {$ip}] {$message}{$context_str}\n";
        
        error_log($log_entry, 3, $filepath);
        
        // Also log to database for critical events
        if ($filename === 'security.log' || $filename === 'access.log') {
            $this->logToDatabase($filename, $message, $context);
        }
    }
    
    /**
     * Log to database for critical events
     */
    private function logToDatabase($type, $message, $context) {
        global $pdo;
        if (!isset($pdo)) return;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO laporan (tipe, keterangan, generated_by, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $user_id = $_SESSION['user_id'] ?? null;
            $stmt->execute([$type, $message, $user_id]);
        } catch (Exception $e) {
            // Silent fail - don't let logging break the app
        }
    }
    
    /**
     * Clean up old logs
     */
    private function cleanupOldLogs() {
        $archive_dir = $this->log_dir . 'archive/';
        if (!file_exists($archive_dir)) return;
        
        $cutoff_date = strtotime("-{$this->retention_days} days");
        
        $years = glob($archive_dir . '*', GLOB_ONLYDIR);
        foreach ($years as $year_path) {
            $year = basename($year_path);
            if (is_numeric($year) && $year < date('Y') - 1) {
                // Delete entire year folder if older than 1 year
                $this->deleteDirectory($year_path);
                Logger::backup("Deleted old archive year", ['year' => $year]);
            } else {
                // Check month folders
                $months = glob($year_path . '/*', GLOB_ONLYDIR);
                foreach ($months as $month_path) {
                    if (filemtime($month_path) < $cutoff_date) {
                        $this->deleteDirectory($month_path);
                        Logger::backup("Deleted old archive month", ['path' => $month_path]);
                    }
                }
            }
        }
        
        // Update archive index after cleanup
        $this->createArchiveIndex();
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    // ============================================
    // PUBLIC LOGGING METHODS
    // ============================================
    
    public static function error($message, $context = []) {
        $logger = self::getInstance();
        $logger->writeLog('php-error.log', "ERROR: {$message}", $context);
    }
    
    public static function database($message, $context = []) {
        $logger = self::getInstance();
        $logger->writeLog('database.log', "DB: {$message}", $context);
    }
    
    public static function security($message, $context = []) {
        $logger = self::getInstance();
        $logger->writeLog('security.log', "SECURITY: {$message}", $context);
        error_log("[SECURITY] {$message} " . json_encode($context));
    }
    
    public static function access($message, $context = []) {
        $logger = self::getInstance();
        $logger->writeLog('access.log', "ACCESS: {$message}", $context);
    }
    
    public static function backup($message, $context = []) {
        $logger = self::getInstance();
        $logger->writeLog('backup.log', "BACKUP: {$message}", $context);
    }
    
    public static function activity($user_id, $action, $details = []) {
        $logger = self::getInstance();
        $logger->writeLog('access.log', "ACTIVITY: User {$user_id} - {$action}", $details);
        
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO laporan (tipe, keterangan, generated_by, created_at) 
                VALUES ('user_activity', ?, ?, NOW())
            ");
            $description = "User {$user_id}: {$action} - " . json_encode($details);
            $stmt->execute([$description, $user_id]);
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    /**
     * Get logs with archive support
     */
    public static function getLogs($type = 'access', $lines = 100, $search = '', $include_archive = false) {
        $logger = self::getInstance();
        $logs = [];
        
        // Get current logs
        $current_file = $logger->log_dir . $type . '.log';
        if (file_exists($current_file)) {
            $current_logs = $logger->readLogFile($current_file, $lines, $search);
            $logs = array_merge($logs, $current_logs);
        }
        
        // Include archived logs if requested
        if ($include_archive) {
            $archive_logs = $logger->getArchivedLogs($type, $lines, $search);
            $logs = array_merge($logs, $archive_logs);
        }
        
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($logs, 0, $lines);
    }
    
    /**
     * Read log file
     */
    private function readLogFile($filepath, $lines, $search) {
        if (!file_exists($filepath)) return [];
        
        $logs = [];
        $handle = fopen($filepath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (empty($search) || stripos($line, $search) !== false) {
                    // Parse timestamp from log
                    preg_match('/\[(.*?)\]/', $line, $matches);
                    $timestamp = $matches[1] ?? date('Y-m-d H:i:s');
                    
                    $logs[] = [
                        'timestamp' => $timestamp,
                        'content' => trim($line),
                        'source' => basename($filepath)
                    ];
                }
            }
            fclose($handle);
        }
        
        return array_reverse($logs);
    }
    
    /**
     * Get archived logs
     */
    private function getArchivedLogs($type, $lines, $search) {
        $all_logs = [];
        $archive_base = $this->log_dir . 'archive/';
        
        if (!file_exists($archive_base)) return [];
        
        $years = glob($archive_base . '*', GLOB_ONLYDIR);
        foreach ($years as $year_path) {
            $months = glob($year_path . '/*', GLOB_ONLYDIR);
            foreach ($months as $month_path) {
                $files = glob($month_path . '/' . $type . '_*.log*');
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) == 'gz') {
                        // Read gzip compressed file
                        $content = gzopen($file, 'rb');
                        $lines_array = [];
                        while (!gzeof($content)) {
                            $line = gzgets($content);
                            if (empty($search) || stripos($line, $search) !== false) {
                                preg_match('/\[(.*?)\]/', $line, $matches);
                                $timestamp = $matches[1] ?? date('Y-m-d H:i:s');
                                $lines_array[] = [
                                    'timestamp' => $timestamp,
                                    'content' => trim($line),
                                    'source' => basename($file)
                                ];
                            }
                        }
                        gzclose($content);
                        $all_logs = array_merge($all_logs, $lines_array);
                    } else {
                        $logs = $this->readLogFile($file, $lines, $search);
                        $all_logs = array_merge($all_logs, $logs);
                    }
                }
            }
        }
        
        return $all_logs;
    }
    
    /**
     * Get archive statistics
     */
    public static function getArchiveStats() {
        $logger = self::getInstance();
        $index_file = $logger->log_dir . 'archive/index.json';
        
        if (file_exists($index_file)) {
            return json_decode(file_get_contents($index_file), true);
        }
        
        return ['error' => 'Archive index not found'];
    }
    
    /**
     * Get archive directory listing
     */
    public static function getArchiveListing() {
        $logger = self::getInstance();
        $archive_base = $logger->log_dir . 'archive/';
        
        if (!file_exists($archive_base)) return [];
        
        $listing = [];
        $years = glob($archive_base . '*', GLOB_ONLYDIR);
        sort($years);
        
        foreach ($years as $year_path) {
            $year = basename($year_path);
            $listing[$year] = [];
            
            $months = glob($year_path . '/*', GLOB_ONLYDIR);
            sort($months);
            
            foreach ($months as $month_path) {
                $month = basename($month_path);
                $files = glob($month_path . '/*.log*');
                $listing[$year][$month] = [];
                
                foreach ($files as $file) {
                    $listing[$year][$month][] = [
                        'name' => basename($file),
                        'size' => filesize($file),
                        'modified' => date('Y-m-d H:i:s', filemtime($file)),
                        'type' => pathinfo($file, PATHINFO_EXTENSION)
                    ];
                }
            }
        }
        
        return $listing;
    }
    
    /**
     * Restore log from archive
     */
    public static function restoreLog($archive_file) {
        $logger = self::getInstance();
        $archive_path = $logger->archive_dir . $archive_file;
        
        if (!file_exists($archive_path)) {
            return false;
        }
        
        // Determine log type from filename
        $type = explode('_', $archive_file)[0];
        $target_path = $logger->log_dir . $type . '.log';
        
        if (pathinfo($archive_path, PATHINFO_EXTENSION) == 'gz') {
            // Decompress gz file
            $content = gzopen($archive_path, 'rb');
            $data = '';
            while (!gzeof($content)) {
                $data .= gzgets($content);
            }
            gzclose($content);
            file_put_contents($target_path, $data, FILE_APPEND);
        } else {
            file_put_contents($target_path, file_get_contents($archive_path), FILE_APPEND);
        }
        
        return true;
    }
    
    /**
     * Clear logs
     */
    public static function clearLogs($type = null) {
        $logger = self::getInstance();
        
        if ($type && in_array($type, ['php-error', 'database', 'security', 'access', 'backup'])) {
            $filepath = $logger->log_dir . $type . '.log';
            if (file_exists($filepath)) {
                // Rotate before clearing
                $logger->rotateLog($type . '.log');
                file_put_contents($filepath, "");
                self::security("Logs cleared for type: {$type}", ['admin' => $_SESSION['user_id'] ?? 'unknown']);
            }
        } else {
            $log_files = ['php-error.log', 'database.log', 'security.log', 'access.log', 'backup.log'];
            foreach ($log_files as $file) {
                $filepath = $logger->log_dir . $file;
                if (file_exists($filepath)) {
                    $logger->rotateLog($file);
                    file_put_contents($filepath, "");
                }
            }
            self::security("All logs cleared and rotated", ['admin' => $_SESSION['user_id'] ?? 'unknown']);
        }
        
        $logger->createArchiveIndex();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $message = "Error {$errno}: {$errstr} in {$errfile} on line {$errline}";
    Logger::error($message);
    return false;
}

// Custom exception handler
function customExceptionHandler($exception) {
    $message = "Uncaught Exception: " . $exception->getMessage() . 
               " in " . $exception->getFile() . " on line " . $exception->getLine();
    Logger::error($message);
    
    if (ini_get('display_errors') == 0) {
        http_response_code(500);
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
            die("Terjadi kesalahan pada server. Tim teknis telah diberitahu. Lihat logs untuk detail.");
        }
    }
}

set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
?>