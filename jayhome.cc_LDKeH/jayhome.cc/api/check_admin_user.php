<?php
/**
 * 检查管理员用户表和数据
 */

require_once 'config.php';

try {
    $db = getDB();
    
    // 检查admin_users表是否存在
    $tableCheckSql = "SHOW TABLES LIKE 'admin_users'";
    $tableCheck = $db->query($tableCheckSql);
    
    if ($tableCheck->rowCount() === 0) {
        echo "错误: admin_users表不存在！\n";
        exit;
    }
    
    echo "admin_users表存在\n";
    
    // 检查表结构
    echo "\n表结构:\n";
    $structureSql = "DESCRIBE admin_users";
    $structure = $db->query($structureSql);
    while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']}\n";
    }
    
    // 检查是否有管理员用户
    echo "\n管理员用户列表:\n";
    $usersSql = "SELECT id, username, password, created_at, last_login FROM admin_users";
    $users = $db->query($usersSql);
    
    if ($users->rowCount() === 0) {
        echo "没有找到管理员用户！\n";
    } else {
        while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
            echo "ID: {$user['id']}, 用户名: {$user['username']}\n";
            echo "密码哈希: {$user['password']}\n";
            echo "创建时间: {$user['created_at']}\n";
            echo "最后登录: " . ($user['last_login'] ?? '从未登录') . "\n";
            echo "-----------------------------------\n";
        }
    }
    
    // 如果没有用户，提供创建管理员用户的选项
    if ($users->rowCount() === 0) {
        echo "\n是否创建默认管理员用户？(admin/admin123)\n";
        echo "运行以下命令来创建: php -r \"file_put_contents('create_admin.php', file_get_contents('https://example.com/create_admin_template.php'));\" && php create_admin.php\n";
    }
    
} catch (Exception $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
}
?>