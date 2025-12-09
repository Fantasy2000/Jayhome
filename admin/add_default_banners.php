<?php
/**
 * 添加默认轮播数据脚本
 * 将前台的5条默认轮播数据添加到数据库
 */

require_once '../api/config.php';

echo "<h1>添加默认轮播数据</h1>";
echo "<hr>";

try {
    $db = getDB();
    
    // 首先检查表是否存在
    $checkTableSql = "SHOW TABLES LIKE 'banners'";
    $stmt = $db->query($checkTableSql);
    $result = $stmt->fetch();
    
    if (!$result) {
        echo "❌ banners 表不存在，正在创建...<br>";
        
        $createTableSql = "CREATE TABLE IF NOT EXISTS banners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL COMMENT 'Banner标题',
            content TEXT NOT NULL COMMENT 'Banner内容',
            image_url VARCHAR(500) DEFAULT '' COMMENT '图片URL',
            link_url VARCHAR(500) DEFAULT '' COMMENT '链接URL',
            status TINYINT DEFAULT 1 COMMENT '状态：1-显示，0-隐藏',
            sort_order INT DEFAULT 0 COMMENT '排序顺序',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->exec($createTableSql);
        echo "✅ banners 表已创建<br>";
    } else {
        echo "✅ banners 表已存在<br>";
    }
    
    // 检查是否已有数据
    $countSql = "SELECT COUNT(*) as count FROM banners WHERE status = 1";
    $stmt = $db->query($countSql);
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        echo "⚠️ 数据库中已有 " . $count . " 条数据<br>";
        echo "<p>如果要重新添加默认数据，请先删除现有数据。</p>";
        echo "<p><a href='javascript:history.back()'>返回</a></p>";
        exit;
    }
    
    // 默认轮播数据
    $defaultData = [
        [
            'title' => '未完待续',
            'content' => '人生还在继续，故事还没完结。每一天都是新的开始，每一次失败都是成功的垫脚石。相信未来，相信自己。',
            'sort_order' => 1
        ],
        [
            'title' => '从中铁工地跑路',
            'content' => '那段时光虽然辛苦，但让我学会了坚持和吃苦。离开不是逃避，而是为了寻找更适合自己的道路。感谢那段经历让我成长。',
            'sort_order' => 2
        ],
        [
            'title' => '从宁夏大学毕业',
            'content' => '四年的青春岁月在银川度过，从青涩少年到成熟青年。感谢母校的培养，感谢朋友的陪伴。毕业不是结束，而是新的开始。',
            'sort_order' => 3
        ],
        [
            'title' => '建设第一个网站',
            'content' => '从零开始学习编程，一行行代码构建起自己的网络世界。那时的我充满了对技术的热情和对未来的憧憬。这是我梦想的起点。',
            'sort_order' => 4
        ],
        [
            'title' => '从娘胎出来',
            'content' => '生命的开始，一切的起源。感谢父母的养育，感谢这个世界给我的一切。每一个人的出生都是一个奇迹。',
            'sort_order' => 5
        ]
    ];
    
    // 插入数据
    $insertSql = "INSERT INTO banners (title, content, image_url, link_url, status, sort_order) VALUES (?, '', '', 1, ?)";
    $stmt = $db->prepare($insertSql);
    
    echo "<h2>正在添加数据...</h2>";
    echo "<ul>";
    
    foreach ($defaultData as $data) {
        $stmt->execute([$data['title'], $data['content'], $data['sort_order']]);
        echo "<li>✅ " . htmlspecialchars($data['title']) . "</li>";
    }
    
    echo "</ul>";
    
    echo "<h2>添加完成！</h2>";
    echo "<p>已成功添加 " . count($defaultData) . " 条轮播数据。</p>";
    
    // 显示已添加的数据
    echo "<h3>现有轮播数据：</h3>";
    $listSql = "SELECT id, title, content, created_at FROM banners ORDER BY sort_order ASC";
    $stmt = $db->query($listSql);
    $banners = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>标题</th><th>内容</th><th>创建时间</th></tr>";
    foreach ($banners as $banner) {
        echo "<tr>";
        echo "<td>" . $banner['id'] . "</td>";
        echo "<td>" . htmlspecialchars($banner['title']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($banner['content'], 0, 50)) . "...</td>";
        echo "<td>" . $banner['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><a href='carousel-manager.html'>返回轮播管理后台</a></p>";
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "<br>";
    echo "<p><a href='javascript:history.back()'>返回</a></p>";
}
?>


