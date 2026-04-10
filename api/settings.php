<?php
/**
 * 网站设置 API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'get';
    
    if ($action === 'get') {
        getSettings();
    } elseif ($action === 'get_single') {
        $key = $_GET['key'] ?? '';
        getSetting($key);
    }
} elseif ($method === 'POST') {
    // 检查是否有action参数
    $action = $_POST['action'] ?? '';
    
    // 如果没有action参数，且是JSON请求，则直接保存设置
    if (empty($action) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        saveSettings();
    } elseif ($action === 'save') {
        saveSettings();
    } elseif ($action === 'save_single') {
        saveSingleSetting();
    }
}

function getSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '获取设置失败: ' . $e->getMessage()
        ]);
    }
}

function getSetting($key) {
    global $pdo;
    
    if (empty($key)) {
        echo json_encode([
            'success' => false,
            'message' => '参数错误'
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $row['setting_value'],
                    'type' => $row['setting_type']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '设置不存在'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '获取设置失败: ' . $e->getMessage()
        ]);
    }
}

function saveSettings() {
    global $pdo;
    
    // 确保数据库连接
    if (!isset($pdo)) {
        $pdo = getDB();
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // 检查JSON解析是否成功
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }
    
    if (!$data || !is_array($data)) {
        $data = $_POST;
    }
    
    if (empty($data)) {
        jsonResponse([
            'success' => false,
            'message' => '没有要保存的数据'
        ]);
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        
        foreach ($data as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $pdo->commit();
        
        jsonResponse([
            'success' => true,
            'message' => '保存成功'
        ]);
    } catch (PDOException $e) {
        if ($pdo) {
            $pdo->rollBack();
        }
        jsonResponse([
            'success' => false,
            'message' => '保存失败: ' . $e->getMessage()
        ], 500);
    }
}

function saveSingleSetting() {
    global $pdo;
    
    // 确保数据库连接
    if (!isset($pdo)) {
        $pdo = getDB();
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $key = $data['key'] ?? '';
    $value = $data['value'] ?? '';
    
    if (empty($key)) {
        jsonResponse([
            'success' => false,
            'message' => '参数错误'
        ]);
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
        $stmt->execute([$key, $value]);
        
        jsonResponse([
            'success' => true,
            'message' => '保存成功'
        ]);
    } catch (PDOException $e) {
        jsonResponse([
            'success' => false,
            'message' => '保存失败: ' . $e->getMessage()
        ], 500);
    }
}
