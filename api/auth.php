<?php
/**
 * 管理员认证API
 */

require_once 'config.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();
    
    switch ($method) {
        case 'POST':
            if ($action === 'login') {
                handleLogin($db);
            } elseif ($action === 'logout') {
                handleLogout();
            } else {
                jsonResponse(['success' => false, 'message' => '无效的操作'], 400);
            }
            break;
            
        case 'GET':
            if ($action === 'check') {
                checkAuth();
            } else {
                jsonResponse(['success' => false, 'message' => '无效的操作'], 400);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => '服务器错误: ' . $e->getMessage()
    ], 500);
}

/**
 * 处理登录
 */
function handleLogin($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['username']) || !isset($data['password'])) {
        jsonResponse([
            'success' => false,
            'message' => '用户名和密码不能为空'
        ], 400);
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    // 查询用户
    $sql = "SELECT id, username, password FROM admin_users WHERE username = :username";
    $stmt = $db->prepare($sql);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        jsonResponse([
            'success' => false,
            'message' => '用户名或密码错误'
        ], 401);
    }
    
    // 验证密码
    if (empty($user['password'])) {
        jsonResponse([
            'success' => false,
            'message' => '用户密码未设置，请联系管理员'
        ], 401);
    }
    
    // 验证密码
    if (!password_verify($password, $user['password'])) {
        jsonResponse([
            'success' => false,
            'message' => '用户名或密码错误'
        ], 401);
    }
    
    // 如果密码验证通过，但需要重新哈希（使用新的算法），则更新
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $updateHashStmt = $db->prepare("UPDATE admin_users SET password = :password WHERE id = :id");
        $updateHashStmt->execute([':password' => $newHash, ':id' => $user['id']]);
    }
    
    // 更新最后登录时间
    $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->execute([':id' => $user['id']]);
    
    // 设置Session
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_login_time'] = time();
    
    jsonResponse([
        'success' => true,
        'message' => '登录成功',
        'data' => [
            'username' => $user['username']
        ]
    ]);
}

/**
 * 处理登出
 */
function handleLogout() {
    session_destroy();
    jsonResponse([
        'success' => true,
        'message' => '已退出登录'
    ]);
}

/**
 * 检查认证状态
 */
function checkAuth() {
    if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
        // 检查Session是否过期
        if (isset($_SESSION['admin_login_time']) && 
            (time() - $_SESSION['admin_login_time']) < SESSION_LIFETIME) {
            jsonResponse([
                'success' => true,
                'authenticated' => true,
                'data' => [
                    'username' => $_SESSION['admin_username']
                ]
            ]);
        } else {
            session_destroy();
            jsonResponse([
                'success' => true,
                'authenticated' => false,
                'message' => 'Session已过期'
            ]);
        }
    } else {
        jsonResponse([
            'success' => true,
            'authenticated' => false
        ]);
    }
}

/**
 * 验证管理员权限（用于需要认证的接口）
 */
function requireAuth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        jsonResponse([
            'success' => false,
            'message' => '未登录或登录已过期',
            'code' => 'UNAUTHORIZED'
        ], 401);
    }
    
    // 检查Session是否过期
    if (isset($_SESSION['admin_login_time']) && 
        (time() - $_SESSION['admin_login_time']) >= SESSION_LIFETIME) {
        session_destroy();
        jsonResponse([
            'success' => false,
            'message' => '登录已过期，请重新登录',
            'code' => 'SESSION_EXPIRED'
        ], 401);
    }
}


