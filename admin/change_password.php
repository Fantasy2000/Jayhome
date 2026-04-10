<?php
/**
 * 修改管理员密码工具
 * 只有登录的管理员才能访问
 */

require_once '../api/config.php';

// 启动会话
session_start();

// 检查是否已登录
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>访问被拒绝</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#fff;} .error{color:#f44336;}</style></head><body>";
    echo "<h1>访问被拒绝</h1>";
    echo "<p class='error'>您需要先登录才能访问此页面</p>";
    echo "<p><a href='login.php' style='color:#4caf50;'>返回登录页面</a></p>";
    echo "</body></html>";
    exit;
}

// 处理登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 如果通过POST提交，则修改密码
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_password'])) {
        $newPassword = trim($_POST['new_password']);
        
        if (empty($newPassword)) {
            $error = '密码不能为空';
        } elseif (strlen($newPassword) < 6) {
            $error = '密码长度至少6位';
        } else {
            try {
                $db = getDB();
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $sql = "UPDATE admin_users SET password = :password WHERE username = 'admin'";
                $stmt = $db->prepare($sql);
                $stmt->execute([':password' => $hashedPassword]);
                
                if ($stmt->rowCount() > 0) {
                    $success = '密码修改成功！新密码：' . htmlspecialchars($newPassword);
                } else {
                    $error = '修改失败，请检查数据库连接';
                }
            } catch (Exception $e) {
                $error = '修改失败：' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        // 重置密码为默认值 admin123
        try {
            $db = getDB();
            $defaultPassword = 'admin123';
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE admin_users SET password = :password WHERE username = 'admin'";
            $stmt = $db->prepare($sql);
            $stmt->execute([':password' => $hashedPassword]);
            
            if ($stmt->rowCount() > 0) {
                $success = '密码已重置为默认值：admin123';
            } else {
                $error = '重置失败，请检查数据库连接';
            }
        } catch (Exception $e) {
            $error = '重置失败：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改管理员密码</title>
    <link rel="stylesheet" href="../static/css/style.css">
    <link rel="stylesheet" href="../static/css/root.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: var(--main_bg_color);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
        }
        
        .password-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            backdrop-filter: blur(var(--card_filter));
            -webkit-backdrop-filter: blur(var(--card_filter));
            background: var(--item_bg_color);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .title {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 30px;
            color: var(--main_text_color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--item_left_text_color);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--main_text_color);
            font-size: 14px;
            font-family: "b", "a", sans-serif;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            border-color: var(--purple_text_color);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: var(--purple_text_color);
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: "b", "a", sans-serif;
        }
        
        .form-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .message.success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.3);
            color: #00ff00;
        }
        
        .message.error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            color: #ff4444;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--purple_text_color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <h1 class="title">修改管理员密码</h1>
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="new_password">新密码</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form-input" 
                    placeholder="请输入新密码（至少6位）"
                    required
                    minlength="6"
                >
            </div>
            <button type="submit" class="form-button">修改密码</button>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <h3 style="margin-bottom: 15px; font-size: 16px; color: var(--main_text_color);">重置密码</h3>
                <p style="margin-bottom: 15px; font-size: 14px; color: rgba(255,255,255,0.6);">将密码重置为默认值：admin123</p>
                <button type="submit" name="reset_password" class="form-button" style="background: #ff9800;">重置密码</button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <a href="index.php" class="back-link">返回管理后台</a>
        </form>
    </div>
</body>
</html>


