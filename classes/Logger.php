<?php
/**
 * Logger Class
 * 
 * Handles application logging with different log levels
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */
class Logger {
    private static $logDir = __DIR__ . '/../logs';
    private static $logFile = 'application.log';
    private static $errorLogFile = 'error.log';
    
    /**
     * Initialize logger - create log directory if it doesn't exist
     */
    public static function init() {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    /**
     * Log info message
     * 
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log warning message
     * 
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log error message
     * 
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
        // Also log to error log file
        self::writeToFile(self::$errorLogFile, self::formatMessage('ERROR', $message, $context));
    }
    
    /**
     * Log debug message (only in development)
     * 
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public static function debug(string $message, array $context = []): void {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            self::log('DEBUG', $message, $context);
        }
    }
    
    /**
     * Write log entry
     * 
     * @param string $level Log level (INFO, WARNING, ERROR, DEBUG)
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    private static function log(string $level, string $message, array $context = []): void {
        self::init();
        $formattedMessage = self::formatMessage($level, $message, $context);
        self::writeToFile(self::$logFile, $formattedMessage);
    }
    
    /**
     * Format log message with timestamp and context
     * 
     * @param string $level Log level
     * @param string $message The message
     * @param array $context Additional context
     * @return string Formatted log message
     */
    private static function formatMessage(string $level, string $message, array $context = []): string {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = $_SESSION['username'] ?? 'guest';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logEntry = "[{$timestamp}] [{$level}] [IP: {$ip}] [User: {$user}] [URI: {$requestUri}] {$message}";
        
        if (!empty($context)) {
            $logEntry .= " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        return $logEntry . PHP_EOL;
    }
    
    /**
     * Write message to log file
     * 
     * @param string $filename Log filename
     * @param string $message Formatted message
     */
    private static function writeToFile(string $filename, string $message): void {
        $filepath = self::$logDir . '/' . $filename;
        file_put_contents($filepath, $message, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Clean old log files (older than specified days)
     * 
     * @param int $days Number of days to keep logs
     */
    public static function cleanOldLogs(int $days = 30): void {
        self::init();
        $files = glob(self::$logDir . '/*.log');
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}


