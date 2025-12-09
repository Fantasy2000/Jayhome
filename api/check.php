<?php
/**
 * 系统检查工具
 * 检查数据库连接、表结构等
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$checks = [
    'database_connection' => false,
    'database_exists' => false,
    'tables_exist' => false,
    'admin_user_exists' => false,
    'php_version' => false
];

$messages = [];

// 检查PHP版本
$phpVersion = phpversion();
$checks['php_version'] = version_compare($phpVersion, '7.0.0', '>=');
$messages['php_version'] = $checks['php_version'] 
    ? "PHP版本: $phpVersion ✓" 
    : "PHP版本过低: $phpVersion (需要7.0+) ✗";

// 检查数据库连接
try {
    $db = getDB();
    $checks['database_connection'] = true;
    $messages['database_connection'] = "数据库连接成功 ✓";
    
    // 检查数据库是否存在
    $dbName = DB_NAME;
    $stmt = $db->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();
    $checks['database_exists'] = ($currentDb === $dbName);
    $messages['database_exists'] = $checks['database_exists'] 
        ? "数据库 '$dbName' 存在 ✓" 
        : "数据库 '$dbName' 不存在 ✗";
    
    // 检查表是否存在
    $tables = ['comments', 'admin_users'];
    $allTablesExist = true;
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $allTablesExist = false;
            break;
        }
    }
    $checks['tables_exist'] = $allTablesExist;
    $messages['tables_exist'] = $allTablesExist 
        ? "所有数据表存在 ✓" 
        : "部分数据表不存在，请执行 database/comments.sql ✗";
    
    // 检查管理员用户
    if ($allTablesExist) {
        $stmt = $db->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
        $adminExists = $stmt->fetchColumn() > 0;
        $checks['admin_user_exists'] = $adminExists;
        $messages['admin_user_exists'] = $adminExists 
            ? "管理员用户存在 ✓" 
            : "管理员用户不存在，请执行 database/comments.sql ✗";
    }
    
} catch (Exception $e) {
    $messages['database_connection'] = "数据库连接失败: " . $e->getMessage() . " ✗";
}

$allPassed = array_reduce($checks, function($carry, $item) {
    return $carry && $item;
}, true);

echo json_encode([
    'success' => $allPassed,
    'checks' => $checks,
    'messages' => $messages,
    'summary' => $allPassed 
        ? '所有检查通过，系统配置正确！' 
        : '部分检查未通过，请查看详细信息'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


