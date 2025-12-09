<?php
/**
 * Banner API 诊断脚本
 * 检查数据库连接、表结构、数据等
 */

require_once '../api/config.php';

echo "<h1>Banner API 诊断报告</h1>";
echo "<hr>";

// 1. 检查数据库连接
echo "<h2>1. 数据库连接检查</h2>";
try {
    $db = getDB();
    echo "✅ 数据库连接成功<br>";
    echo "数据库名: " . DB_NAME . "<br>";
    echo "数据库用户: " . DB_USER . "<br>";
} catch (Exception $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "<br>";
    exit;
}

// 2. 检查表是否存在
echo "<h2>2. 表结构检查</h2>";
try {
    $sql = "SHOW TABLES LIKE 'banners'";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ banners 表存在<br>";
        
        // 显示表结构
        $sql = "DESCRIBE banners";
        $stmt = $db->query($sql);
        $columns = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>字段</th><th>类型</th><th>是否为空</th><th>键</th><th>默认值</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ banners 表不存在<br>";
        echo "<p><a href='init_carousel_data.php'>点击这里初始化数据库</a></p>";
    }
} catch (Exception $e) {
    echo "❌ 表检查失败: " . $e->getMessage() . "<br>";
}

// 3. 检查数据
echo "<h2>3. 数据检查</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM banners";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    $count = $result['count'];
    
    echo "✅ 表中有 " . $count . " 条数据<br>";
    
    if ($count > 0) {
        echo "<h3>现有数据：</h3>";
        $sql = "SELECT id, title, content, status, created_at FROM banners ORDER BY id DESC LIMIT 10";
        $stmt = $db->query($sql);
        $banners = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>标题</th><th>内容</th><th>状态</th><th>创建时间</th></tr>";
        foreach ($banners as $banner) {
            echo "<tr>";
            echo "<td>" . $banner['id'] . "</td>";
            echo "<td>" . htmlspecialchars($banner['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($banner['content'], 0, 50)) . "...</td>";
            echo "<td>" . ($banner['status'] == 1 ? '显示' : '隐藏') . "</td>";
            echo "<td>" . $banner['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ 表中没有数据<br>";
        echo "<p><a href='init_carousel_data.php'>点击这里初始化数据</a></p>";
    }
} catch (Exception $e) {
    echo "❌ 数据检查失败: " . $e->getMessage() . "<br>";
}

// 4. 测试 API
echo "<h2>4. API 测试</h2>";
echo "<h3>GET 请求测试</h3>";
echo "<p>测试 URL: ../api/banners.php?action=list&page=1&limit=10</p>";

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/banners.php?action=list&page=1&limit=10');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ API 响应正常 (HTTP " . $httpCode . ")<br>";
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            echo "✅ JSON 解析成功<br>";
            echo "响应: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "<br>";
        } else {
            echo "❌ JSON 解析失败<br>";
            echo "原始响应: " . htmlspecialchars($response) . "<br>";
        }
    } else {
        echo "❌ API 响应错误 (HTTP " . $httpCode . ")<br>";
        echo "响应: " . htmlspecialchars($response) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ API 测试失败: " . $e->getMessage() . "<br>";
}

// 5. 检查权限
echo "<h2>5. 权限检查</h2>";
try {
    // 测试插入
    $sql = "INSERT INTO banners (title, content, status) VALUES ('测试', '测试内容', 1)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $testId = $db->lastInsertId();
    echo "✅ 插入权限正常<br>";
    
    // 删除测试数据
    $sql = "DELETE FROM banners WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $testId]);
    echo "✅ 删除权限正常<br>";
} catch (Exception $e) {
    echo "❌ 权限检查失败: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='carousel-manager.html'>返回轮播管理</a></p>";
?>


