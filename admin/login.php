<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>评论管理 - 登录</title>
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
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            backdrop-filter: blur(var(--card_filter));
            -webkit-backdrop-filter: blur(var(--card_filter));
            background: var(--item_bg_color);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .login-title {
            text-align: center;
            font-size: 28px;
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
            box-shadow: 0 4px 12px rgba(116, 123, 255, 0.4);
        }
        
        .form-button:active {
            transform: translateY(0);
        }
        
        .error-message {
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            border-radius: 6px;
            color: #ff4444;
            font-size: 14px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">评论管理</h1>
        <form id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">用户名</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    placeholder="请输入用户名"
                    required
                    autocomplete="username"
                >
            </div>
            <div class="form-group">
                <label class="form-label" for="password">密码</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    placeholder="请输入密码"
                    required
                    autocomplete="current-password"
                >
            </div>
            <button type="submit" class="form-button">登录</button>
            <div id="errorMessage" class="error-message"></div>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('errorMessage');
            
            // 隐藏错误信息
            errorDiv.classList.remove('show');
            errorDiv.textContent = '';
            
            try {
                const response = await fetch('../api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 登录成功，跳转到管理页面
                    window.location.href = 'index.php';
                } else {
                    // 显示错误信息
                    errorDiv.textContent = result.message || '登录失败';
                    errorDiv.classList.add('show');
                }
            } catch (error) {
                errorDiv.textContent = '网络错误，请稍后重试';
                errorDiv.classList.add('show');
            }
        });
    </script>
</body>
</html>


