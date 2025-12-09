<?php
/**
 * 评论API接口
 * 提供评论的增删查功能
 */

require_once 'config.php';

session_start();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();
    
    // 确保存在 location 列（用于显示城市），若不存在则自动添加
    try {
        $checkCol = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'location'");
        $checkCol->execute();
        if (!$checkCol->fetch()) {
            $db->exec("ALTER TABLE comments ADD COLUMN location VARCHAR(100) NULL COMMENT '城市/地区' AFTER user_agent");
        }
        // 确保存在 parent_id 列
        $checkParent = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'parent_id'");
        $checkParent->execute();
        if (!$checkParent->fetch()) {
            $db->exec("ALTER TABLE comments ADD COLUMN parent_id INT NULL DEFAULT NULL COMMENT '父评论ID' AFTER id");
            $db->exec("CREATE INDEX idx_parent_id ON comments(parent_id)");
        }
    } catch (Exception $e) {
        // 忽略自动迁移失败
    }
    
    switch ($method) {
        case 'GET':
            if ($action === 'like') {
                handleLike($db);
            } else if ($action === 'threads') {
                handleThreads($db);
            } else {
                handleGet($db, $action);
            }
            break;
            
        case 'POST':
            if ($action === 'like') {
                handleLike($db);
            } else if ($action === 'clear') {
                handleClear($db);
            } else {
                handlePost($db);
            }
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
 * 线程式获取：顶层评论+回复
 */
function handleThreads($db) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($limit <= 0 || $limit > 200) $limit = 50;

    // 检查是否存在 parent_id 列
    $hasParent = false; $hasLikes = false;
    try {
        $checkParent = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'parent_id'");
        $checkParent->execute();
        if ($checkParent->fetch()) { $hasParent = true; }
    } catch (Exception $e) {}
    try {
        $checkLikes = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'likes'");
        $checkLikes->execute();
        if ($checkLikes->fetch()) { $hasLikes = true; }
    } catch (Exception $e) {}

    $fields = 'id, text, location, created_at';
    if ($hasLikes) $fields .= ', COALESCE(likes, 0) as likes';

    // 顶层评论
    $whereTop = 'status = 1';
    if ($hasParent) { $whereTop .= ' AND (parent_id IS NULL OR parent_id = 0)'; }
    $sqlTop = "SELECT $fields FROM comments WHERE $whereTop ORDER BY created_at DESC, id DESC LIMIT :limit";
    $stmtTop = $db->prepare($sqlTop);
    $stmtTop->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtTop->execute();
    $tops = $stmtTop->fetchAll();

    if (!$tops) {
        jsonResponse(['success'=>true,'data'=>[],'count'=>0]);
    }

    // 获取所有顶层ID
    $topIds = array_map(function($r){ return (int)$r['id']; }, $tops);

    // 获取这些顶层的所有回复
    $replies = [];
    if ($hasParent) {
        $in = implode(',', array_fill(0, count($topIds), '?'));
        $sqlRep = "SELECT id, text, location, created_at" . ($hasLikes ? ", COALESCE(likes,0) as likes" : "") . ", parent_id FROM comments WHERE status = 1 AND parent_id IN ($in) ORDER BY created_at ASC, id ASC";
        $stmtRep = $db->prepare($sqlRep);
        foreach ($topIds as $i=>$id) { $stmtRep->bindValue($i+1, $id, PDO::PARAM_INT); }
        $stmtRep->execute();
        $rows = $stmtRep->fetchAll();
        foreach ($rows as $row) {
            $pid = (int)$row['parent_id'];
            if (!isset($replies[$pid])) $replies[$pid] = [];
            $replies[$pid][] = $row;
        }
    }

    // 组装
    foreach ($tops as &$t) {
        $tid = (int)$t['id'];
        $t['replies'] = $replies[$tid] ?? [];
    }

    jsonResponse([
        'success' => true,
        'data' => $tops,
        'count' => count($tops)
    ]);
}

/**
 * 处理GET请求
 */
function handleGet($db, $action) {
    if ($action === 'list') {
        // 获取评论列表（用于后台管理）
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $status = isset($_GET['status']) ? (int)$_GET['status'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $offset = ($page - 1) * $limit;
        
        // 构建查询
        $where = "status = :status";
        $params = [':status' => $status];
        
        if ($search) {
            $where .= " AND text LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM comments WHERE $where";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // 动态构建查询字段以兼容旧数据库
        $fields = 'id, text, location, ip_address, status, created_at, updated_at';
        try {
            // 通过查询information_schema来可靠地检查列是否存在
            $checkStmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'likes'");
            $checkStmt->execute();
            if ($checkStmt->fetch()) {
                $fields .= ', likes';
            }
        } catch (Exception $e) {
            // 如果查询information_schema失败，则忽略
        }

        $sql = "SELECT $fields FROM comments WHERE $where ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll();

        // 如果查询结果中没有likes字段，手动添加默认值
        if (!empty($comments) && !isset($comments[0]['likes'])) {
            foreach ($comments as &$comment) {
                $comment['likes'] = 0;
            }
        }
        
        jsonResponse([
            'success' => true,
            'data' => $comments,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } else {
        // 获取所有显示的评论（用于前端弹幕）
        // 动态检查likes、parent_id 列是否存在
        $fields = 'id, text, location, created_at';
        $hasLikes = false; $hasParent = false;
        try {
            $checkLikes = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'likes'");
            $checkLikes->execute();
            if ($checkLikes->fetch()) { $fields .= ', COALESCE(likes, 0) as likes'; $hasLikes = true; }
        } catch (Exception $e) {}
        try {
            $checkParent = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'parent_id'");
            $checkParent->execute();
            if ($checkParent->fetch()) { $hasParent = true; }
        } catch (Exception $e) {}

        $where = 'status = 1';

        $sql = "SELECT $fields FROM comments WHERE $where ORDER BY created_at DESC, id DESC LIMIT " . MAX_COMMENTS;
        $stmt = $db->query($sql);
        $comments = $stmt->fetchAll();

        // 若结果集中没有likes字段，则补0
        if (!empty($comments) && !$hasLikes) {
            foreach ($comments as &$c) { $c['likes'] = 0; }
        }
        
        jsonResponse([
            'success' => true,
            'data' => $comments,
            'count' => count($comments)
        ]);
    }
}

/**
 * 处理POST请求（添加评论）
 */
function handlePost($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['text'])) {
        jsonResponse([
            'success' => false,
            'message' => '评论内容不能为空'
        ], 400);
    }
    
    $text = trim($data['text']);
    $location = isset($data['location']) ? trim($data['location']) : null;
    // 如果location为空，使用IP地址的一部分作为默认显示
    if (empty($location)) {
        $client_ip = getClientIP();
        $ip_parts = explode('.', $client_ip);
        $location = count($ip_parts) >= 2 ? "${ip_parts[0]}.${ip_parts[1]}.*.*" : "未知地址";
    }
    
    // 验证评论内容
    if (empty($text)) {
        jsonResponse([
            'success' => false,
            'message' => '评论内容不能为空'
        ], 400);
    }
    
    if (mb_strlen($text) > MAX_COMMENT_LENGTH) {
        jsonResponse([
            'success' => false,
            'message' => '评论内容不能超过' . MAX_COMMENT_LENGTH . '个字符'
        ], 400);
    }
    
    // 检查评论数量，超过限制则删除最旧的
    $countSql = "SELECT COUNT(*) as total FROM comments";
    $countStmt = $db->query($countSql);
    $total = $countStmt->fetch()['total'];
    
    if ($total >= MAX_COMMENTS) {
        // 删除最旧的评论
        $deleteSql = "DELETE FROM comments 
                      WHERE id IN (
                          SELECT id FROM (
                              SELECT id FROM comments 
                              ORDER BY created_at ASC 
                              LIMIT 1
                          ) AS temp
                      )";
        $db->exec($deleteSql);
    }
    
    // 插入新评论/回复
    $sql = "INSERT INTO comments (text, location, ip_address, user_agent, status, parent_id) 
            VALUES (:text, :location, :ip, :ua, 1, :parent_id)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':text' => $text,
        ':location' => $location,
        ':ip' => getClientIP(),
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ':parent_id' => isset($data['parent_id']) ? (int)$data['parent_id'] : null
    ]);
    
    $commentId = $db->lastInsertId();
    
    // 获取刚插入的评论（检查likes列是否存在）
    try {
        $selectSql = "SELECT id, text, location, parent_id, created_at, COALESCE(likes, 0) as likes FROM comments WHERE id = :id";
        $selectStmt = $db->prepare($selectSql);
        $selectStmt->execute([':id' => $commentId]);
        $comment = $selectStmt->fetch();
    } catch (Exception $e) {
        // 如果likes列不存在，只查询其他字段
        $selectSql = "SELECT id, text, location, parent_id, created_at FROM comments WHERE id = :id";
        $selectStmt = $db->prepare($selectSql);
        $selectStmt->execute([':id' => $commentId]);
        $comment = $selectStmt->fetch();
        $comment['likes'] = 0; // 默认值
    }
    
    jsonResponse([
        'success' => true,
        'data' => $comment,
        'message' => '评论添加成功'
    ]);
}

/**
 * 处理点赞请求
 */
function handleLike($db) {
    $commentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($commentId <= 0) {
        jsonResponse([
            'success' => false,
            'message' => '无效的评论ID'
        ], 400);
    }
    
    // 检查评论是否存在
    $checkSql = "SELECT id, COALESCE(likes, 0) as likes FROM comments WHERE id = :id AND status = 1";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':id' => $commentId]);
    $comment = $checkStmt->fetch();
    
    if (!$comment) {
        jsonResponse([
            'success' => false,
            'message' => '评论不存在或已被隐藏'
        ], 404);
    }
    
    // 检查是否已经点赞（基于IP）
    $ip = getClientIP();
    // 检查comment_likes表是否存在
    try {
        $likeCheckSql = "SELECT id FROM comment_likes WHERE comment_id = :id AND ip_address = :ip";
        $likeCheckStmt = $db->prepare($likeCheckSql);
        $likeCheckStmt->execute([':id' => $commentId, ':ip' => $ip]);
        $existingLike = $likeCheckStmt->fetch();
    } catch (Exception $e) {
        // 如果comment_likes表不存在，则跳过点赞记录检查
        $existingLike = false;
    }
    
    if ($existingLike) {
        // 已经点赞过，取消点赞
        try {
            $deleteLikeSql = "DELETE FROM comment_likes WHERE id = :id";
            $deleteLikeStmt = $db->prepare($deleteLikeSql);
            $deleteLikeStmt->execute([':id' => $existingLike['id']]);
        } catch (Exception $e) {
            // 表不存在时忽略
        }
        
        // 减少点赞数（如果likes列存在）
        try {
            $updateSql = "UPDATE comments SET likes = GREATEST(0, COALESCE(likes, 0) - 1) WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([':id' => $commentId]);
        } catch (Exception $e) {
            // 如果likes列不存在，忽略更新
        }
        
        jsonResponse([
            'success' => true,
            'liked' => false,
            'likes' => max(0, ($comment['likes'] ?? 0) - 1),
            'message' => '已取消点赞'
        ]);
    } else {
        // 添加点赞记录
        try {
            $insertLikeSql = "INSERT INTO comment_likes (comment_id, ip_address, user_agent) 
                             VALUES (:id, :ip, :ua)";
            $insertLikeStmt = $db->prepare($insertLikeSql);
            $insertLikeStmt->execute([
                ':id' => $commentId,
                ':ip' => $ip,
                ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            // 如果comment_likes表不存在，忽略
        }
        
        // 增加点赞数（如果likes列存在）
        try {
            $updateSql = "UPDATE comments SET likes = COALESCE(likes, 0) + 1 WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([':id' => $commentId]);
        } catch (Exception $e) {
            // 如果likes列不存在，忽略更新
        }
        
        jsonResponse([
            'success' => true,
            'liked' => true,
            'likes' => ($comment['likes'] ?? 0) + 1,
            'message' => '点赞成功'
        ]);
    }
}

/**
 * 清空所有评论（仅管理员）
 */
function handleClear($db) {
    if (!isset($_SESSION['admin_id'])) {
        jsonResponse([
            'success' => false,
            'message' => '未授权：需要管理员登录'
        ], 403);
    }

    try {
        $db->beginTransaction();

        // 清空点赞表（如果存在）
        try {
            $db->exec("DELETE FROM comment_likes");
        } catch (Exception $e) {
            // 表不存在则忽略
        }

        // 清空评论表
        $deleted = $db->exec("DELETE FROM comments");

        // 尝试重置自增
        try { $db->exec("ALTER TABLE comments AUTO_INCREMENT = 1"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE comment_likes AUTO_INCREMENT = 1"); } catch (Exception $e) {}

        $db->commit();

        jsonResponse([
            'success' => true,
            'deleted' => (int)($deleted ?? 0),
            'message' => '已清空全部评论'
        ]);
    } catch (Exception $e) {
        try { $db->rollBack(); } catch (Exception $e2) {}
        jsonResponse([
            'success' => false,
            'message' => '清空失败: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * 处理DELETE请求
 */
function handleDelete($db) {
    $commentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($commentId <= 0) {
        jsonResponse([
            'success' => false,
            'message' => '无效的评论ID'
        ], 400);
    }
    
    // 软删除（修改状态）或硬删除
    $action = $_GET['type'] ?? 'soft'; // soft=软删除，hard=硬删除
    
    if ($action === 'hard') {
        $sql = "DELETE FROM comments WHERE id = :id";
    } else {
        $sql = "UPDATE comments SET status = 0 WHERE id = :id";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $commentId]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse([
            'success' => true,
            'message' => '评论删除成功'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => '评论不存在或已被删除'
        ], 404);
    }
}


