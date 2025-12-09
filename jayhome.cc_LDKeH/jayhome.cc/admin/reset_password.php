<?php
/**
 * 重置管理员密码工具
 * 访问此文件可以重置管理员密码
 */

require_once '../api/config.php';

// 启用错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>重置密码</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#fff;}";
echo ".success{color:#4caf50;}.error{color:#f44336;}.warning{color:#ff9800;}";
echo "input,button{padding:10px;margin:5px;border-radius:5px;border:1px solid #555;background:#333;color:#fff;}";
echo "button{background:#4caf50;cursor:pointer;}button:hover{opacity:0.8;}</style></head><body>";
echo "<h1>重置管理员密码</h1>";

try {
    $db = getDB();
    
    // 检查是否有管理员用户
    $stmt = $db->query("SELECT id, username FROM admin_users WHERE username = 'admin'");
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p class='error'>管理员用户不存在！</p>";
        echo "<p>请先执行 database/comments.sql 创建数据库表</p>";
        exit;
    }
    
    // 处理密码重置
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
        $newPassword = $_POST['new_password'];
        
        if (empty($newPassword)) {
            echo "<p class='error'>密码不能为空</p>";
        } else {
            // 生成新的密码哈希
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // 更新密码
            $updateStmt = $db->prepare("UPDATE admin_users SET password = :password WHERE username = 'admin'");
            $updateStmt->execute([':password' => $newHash]);
            
            echo "<p class='success'>✓ 密码已成功重置！</p>";
            echo "<p>新密码: <strong>" . htmlspecialchars($newPassword) . "</strong></p>";
            echo "<p><a href='login.php' style='color:#4caf50;'>返回登录页面</a></p>";
            exit;
        }
    }
    
    // 显示重置表单
    echo "<form method='POST'>";
    echo "<p>用户名: <strong>admin</strong></p>";
    echo "<p>新密码: <input type='password' name='new_password' placeholder='请输入新密码' required></p>";
    echo "<button type='submit'>重置密码</button>";
    echo "</form>";
    
    // 显示当前密码哈希信息（用于调试）
    $stmt = $db->query("SELECT password FROM admin_users WHERE username = 'admin'");
    $currentUser = $stmt->fetch();
    if ($currentUser) {
        echo "<hr>";
        echo "<h3>调试信息</h3>";
        echo "<p>当前密码哈希: <code>" . htmlspecialchars(substr($currentUser['password'], 0, 50)) . "...</code></p>";
        
        // 测试默认密码
        $testPassword = 'admin123';
        $verifyResult = password_verify($testPassword, $currentUser['password']);
        if ($verifyResult) {
            echo "<p class='success'>✓ 当前密码是: admin123</p>";
        } else {
            echo "<p class='error'>✗ 当前密码不是: admin123</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>错误: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='debug.php' style='color:#4caf50;'>返回诊断页面</a></p>";
echo "</body></html>";


