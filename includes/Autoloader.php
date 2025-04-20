<?php
class Autoloader {
    private static $instance = null;
    private $namespaceMap = [];
    private $classMap = [];

    private function __construct() {
        // Register the autoloader
        spl_autoload_register([$this, 'loadClass']);

        // Set up initial class mappings
        $this->setupClassMap();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function register() {
        self::getInstance();
    }

    private function setupClassMap() {
        // Core classes
        $this->addClassMapping('Database', 'includes/Database.php');
        $this->addClassMapping('Logger', 'includes/Logger.php');
        $this->addClassMapping('Validator', 'includes/Validator.php');
        $this->addClassMapping('Auth', 'includes/Auth.php');
        $this->addClassMapping('User', 'includes/User.php');
        $this->addClassMapping('Book', 'includes/Book.php');
        $this->addClassMapping('Payment', 'includes/Payment.php');
        $this->addClassMapping('ReadingList', 'includes/ReadingList.php');
        $this->addClassMapping('Statistics', 'includes/Statistics.php');
        $this->addClassMapping('Notification', 'includes/Notification.php');

        // Add any third-party class mappings
        $this->addNamespaceMapping('PHPMailer\\PHPMailer\\', 'vendor/phpmailer/phpmailer/src/');
        $this->addNamespaceMapping('PhpOffice\\PhpSpreadsheet\\', 'vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/');
    }

    public function loadClass($class) {
        // First check direct class mappings
        if (isset($this->classMap[$class])) {
            $file = $this->getBasePath() . '/' . $this->classMap[$class];
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        // Then check namespace mappings
        foreach ($this->namespaceMap as $namespace => $dir) {
            if (strpos($class, $namespace) === 0) {
                $path = str_replace('\\', '/', substr($class, strlen($namespace)));
                $file = $this->getBasePath() . '/' . $dir . $path . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }

        // Finally, try PSR-4 autoloading
        $file = $this->getBasePath() . '/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }

    public function addClassMapping($class, $path) {
        $this->classMap[$class] = $path;
    }

    public function addNamespaceMapping($namespace, $dir) {
        $this->namespaceMap[trim($namespace, '\\')] = trim($dir, '/');
    }

    private function getBasePath() {
        return dirname(__DIR__);
    }

    public function registerComposerAutoloader() {
        $composerAutoload = $this->getBasePath() . '/vendor/autoload.php';
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
            return true;
        }
        return false;
    }

    public function dumpAutoloadMap() {
        $map = [
            'classMap' => $this->classMap,
            'namespaceMap' => $this->namespaceMap
        ];
        
        $content = '<?php return ' . var_export($map, true) . ';';
        $cacheFile = $this->getBasePath() . '/cache/autoload_map.php';
        
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0777, true);
        }
        
        file_put_contents($cacheFile, $content);
    }

    public function loadAutoloadMap() {
        $cacheFile = $this->getBasePath() . '/cache/autoload_map.php';
        if (file_exists($cacheFile)) {
            $map = require $cacheFile;
            $this->classMap = $map['classMap'];
            $this->namespaceMap = $map['namespaceMap'];
            return true;
        }
        return false;
    }

    public function clearAutoloadCache() {
        $cacheFile = $this->getBasePath() . '/cache/autoload_map.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            return true;
        }
        return false;
    }

    public function getLoadedClasses() {
        return array_merge(
            array_keys($this->classMap),
            get_declared_classes()
        );
    }

    public function getClassMap() {
        return $this->classMap;
    }

    public function getNamespaceMap() {
        return $this->namespaceMap;
    }

    private function __clone() {}
    private function __wakeup() {}
}

// Initialize configuration
require_once __DIR__ . '/../config/config.php';

// Register autoloader
Autoloader::register();

// Register Composer's autoloader if available
$autoloader = Autoloader::getInstance();
$autoloader->registerComposerAutoloader();

// Load cached autoload map in production
if (APP_ENV === 'production') {
    $autoloader->loadAutoloadMap();
}

// Initialize error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $logger = Logger::getInstance();
    $logger->logError(
        isset($_SESSION['user']) ? $_SESSION['user']['id'] : null,
        'php_error',
        [
            'errno' => $errno,
            'error' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    );
    
    if (APP_ENV === 'development') {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    } else {
        header('Location: /error.php');
        exit;
    }
});

// Initialize exception handling
set_exception_handler(function($exception) {
    $logger = Logger::getInstance();
    $logger->logError(
        isset($_SESSION['user']) ? $_SESSION['user']['id'] : null,
        'uncaught_exception',
        [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]
    );
    
    if (APP_ENV === 'development') {
        echo "<h1>Error</h1>";
        echo "<p>" . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        header('Location: /error.php');
    }
    exit;
});

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set(APP_TIMEZONE);

// Set character encoding
mb_internal_encoding('UTF-8');
?>
