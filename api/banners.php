<?php
/**
 * Banner内容管理API接口
 * 提供Banner的增删查改功能
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            handleGet($db, $action);
            break;
            
        case 'POST':
            handlePost($db);
            break;
            
        case 'PUT':
            handlePut($db);
            break;
            
        case 'DELETE':
            handleDelete($db);
            break;
            
        default:
            jsonResponse([
                'success' => false,
                'message' => '不支持的请求方法'
            ], 405);
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => '服务器错误: ' . $e->getMessage()
    ], 500);
}

/**
 * 处理GET请求
 */
function handleGet($db, $action) {
    // 检查是否是获取单个Banner详情
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        // 获取单个Banner详情
        $sql = "SELECT id, title, content, image_url, link_url, status, sort_order, created_at, updated_at 
                FROM banners 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $banner = $stmt->fetch();
        
        if ($banner) {
            jsonResponse([
                'success' => true,
                'data' => $banner
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'message' => 'Banner不存在'
            ], 404);
        }
    } else if ($action === 'list') {
        // 获取Banner列表（用于后台管理）
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $offset = ($page - 1) * $limit;
        
        // 构建查询
        $where = "1=1";
        $params = [];
        
        if ($status !== 'all') {
            $where .= " AND status = :status";
            $params[':status'] = (int)$status;
        }
        
        if ($search) {
            $where .= " AND (title LIKE :search OR content LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM banners WHERE $where";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // 获取列表
        $sql = "SELECT id, title, content, image_url, link_url, status, sort_order, created_at, updated_at 
                FROM banners 
                WHERE $where 
                ORDER BY sort_order ASC, created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $banners = $stmt->fetchAll();
        
        jsonResponse([
            'success' => true,
            'data' => $banners,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } else {
        // 获取所有显示的Banner（用于前端展示）
        $sql = "SELECT id, title, content, image_url, link_url, created_at 
                FROM banners 
                WHERE status = 1 
                ORDER BY sort_order ASC, created_at DESC";
        $stmt = $db->query($sql);
        $banners = $stmt->fetchAll();
        
        jsonResponse([
            'success' => true,
            'data' => $banners,
            'count' => count($banners)
        ]);
    }
}

/**
 * 处理POST请求（添加Banner）
 */
function handlePost($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['title']) || !isset($data['content'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Banner标题和内容不能为空'
        ], 400);
    }
    
    $title = trim($data['title']);
    $content = trim($data['content']);
    $imageUrl = trim($data['image_url'] ?? '');
    $linkUrl = trim($data['link_url'] ?? '');
    $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
    
    // 验证内容
    if (empty($title)) {
        jsonResponse([
            'success' => false,
            'message' => 'Banner标题不能为空'
        ], 400);
    }
    
    if (empty($content)) {
        jsonResponse([
            'success' => false,
            'message' => 'Banner内容不能为空'
        ], 400);
    }
    
    // 插入新Banner
    $sql = "INSERT INTO banners (title, content, image_url, link_url, sort_order, status) 
            VALUES (:title, :content, :image_url, :link_url, :sort_order, 1)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':content' => $content,
        ':image_url' => $imageUrl,
        ':link_url' => $linkUrl,
        ':sort_order' => $sortOrder
    ]);
    
    $bannerId = $db->lastInsertId();
    
    // 获取刚插入的Banner
    $selectSql = "SELECT id, title, content, image_url, link_url, status, sort_order, created_at, updated_at FROM banners WHERE id = :id";
    $selectStmt = $db->prepare($selectSql);
    $selectStmt->execute([':id' => $bannerId]);
    $banner = $selectStmt->fetch();
    
    jsonResponse([
        'success' => true,
        'data' => $banner,
        'message' => 'Banner添加成功'
    ]);
}

/**
 * 处理PUT请求（更新Banner）
 */
function handlePut($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        jsonResponse([
            'success' => false,
            'message' => '无效的Banner ID'
        ], 400);
    }
    
    $id = (int)$data['id'];
    
    // 检查Banner是否存在
    $checkSql = "SELECT id FROM banners WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':id' => $id]);
    if (!$checkStmt->fetch()) {
        jsonResponse([
            'success' => false,
            'message' => 'Banner不存在'
        ], 404);
    }
    
    // 构建更新语句
    $updates = [];
    $params = [':id' => $id];
    
    if (isset($data['title'])) {
        $updates[] = "title = :title";
        $params[':title'] = trim($data['title']);
    }
    
    if (isset($data['content'])) {
        $updates[] = "content = :content";
        $params[':content'] = trim($data['content']);
    }
    
    if (isset($data['image_url'])) {
        $updates[] = "image_url = :image_url";
        $params[':image_url'] = trim($data['image_url']);
    }
    
    if (isset($data['link_url'])) {
        $updates[] = "link_url = :link_url";
        $params[':link_url'] = trim($data['link_url']);
    }
    
    if (isset($data['status'])) {
        $updates[] = "status = :status";
        $params[':status'] = (int)$data['status'];
    }
    
    if (isset($data['sort_order'])) {
        $updates[] = "sort_order = :sort_order";
        $params[':sort_order'] = (int)$data['sort_order'];
    }
    
    if (empty($updates)) {
        jsonResponse([
            'success' => false,
            'message' => '没有需要更新的内容'
        ], 400);
    }
    
    // 执行更新
    $sql = "UPDATE banners SET " . implode(", ", $updates) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // 获取更新后的Banner
    $selectSql = "SELECT id, title, content, image_url, link_url, status, sort_order, created_at, updated_at FROM banners WHERE id = :id";
    $selectStmt = $db->prepare($selectSql);
    $selectStmt->execute([':id' => $id]);
    $banner = $selectStmt->fetch();
    
    jsonResponse([
        'success' => true,
        'data' => $banner,
        'message' => 'Banner更新成功'
    ]);
}

/**
 * 处理DELETE请求
 */
function handleDelete($db) {
    $bannerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($bannerId <= 0) {
        jsonResponse([
            'success' => false,
            'message' => '无效的Banner ID'
        ], 400);
    }
    
    // 软删除（修改状态）或硬删除
    $action = $_GET['type'] ?? 'soft'; // soft=软删除，hard=硬删除
    
    if ($action === 'hard') {
        $sql = "DELETE FROM banners WHERE id = :id";
    } else {
        $sql = "UPDATE banners SET status = 0 WHERE id = :id";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $bannerId]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse([
            'success' => true,
            'message' => 'Banner删除成功'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => 'Banner不存在或已被删除'
        ], 404);
    }
}

