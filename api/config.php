<?php
/**
 * 数据库配置文件
 * 请根据你的宝塔面板数据库信息修改以下配置
 */

// 数据库配置
define('DB_HOST', 'localhost');        // 数据库主机
define('DB_NAME', 'jayhome_comments'); // 数据库名
define('DB_USER', 'root');              // 数据库用户名（请修改为你的数据库用户名）
define('DB_PASS', 'password');                  // 数据库密码（请修改为你的数据库密码）
define('DB_CHARSET', 'utf8mb4');

// API配置
define('API_VERSION', '1.0');
define('MAX_COMMENT_LENGTH', 50);       // 最大评论长度
define('MAX_COMMENTS', 1000);           // 最大保存评论数

// 安全配置
define('ALLOWED_ORIGINS', '*');         // 允许的跨域来源，生产环境建议设置为具体域名
define('SESSION_LIFETIME', 3600);       // Session有效期（秒）

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告（生产环境建议关闭）
error_reporting(E_ALL);
ini_set('display_errors', 1); // 临时启用错误显示以便调试，生产环境建议设为0

/**
 * 数据库连接
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '数据库连接失败: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    return $pdo;
}

/**
 * 设置CORS头
 */
function setCorsHeaders() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
    
    if (ALLOWED_ORIGINS === '*') {
        header('Access-Control-Allow-Origin: *');
    } else {
        $allowed = explode(',', ALLOWED_ORIGINS);
        if (in_array($origin, $allowed)) {
            header("Access-Control-Allow-Origin: $origin");
        }
    }
    
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    // 处理预检请求
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * 返回JSON响应
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取客户端IP
 */
function getClientIP() {
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip ? explode(',', $ip)[0] : '0.0.0.0';
}

// 设置CORS
setCorsHeaders();

