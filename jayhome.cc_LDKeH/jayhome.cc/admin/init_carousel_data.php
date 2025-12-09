<?php
/**
 * 初始化轮播数据
 * 将默认的轮播数据插入到数据库
 */

require_once '../api/config.php';

function initCarouselData() {
    try {
        $db = getDB();
        
        // 首先检查表是否存在，如果不存在则创建
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
        echo "✓ Banners表已准备就绪<br>";
        
        // 检查表中是否已有数据
        $checkCountSql = "SELECT COUNT(*) as count FROM banners WHERE status = 1";
        $stmt = $db->query($checkCountSql);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            echo "✓ 数据库中已有 " . $count . " 条轮播数据<br>";
            echo "<h3>现有轮播数据：</h3>";
            $listSql = "SELECT id, title, created_at FROM banners WHERE status = 1 ORDER BY sort_order ASC, created_at DESC";
            $stmt = $db->query($listSql);
            $items = $stmt->fetchAll();
            echo "<ul>";
            foreach ($items as $item) {
                echo "<li>" . htmlspecialchars($item['title']) . " (ID: " . $item['id'] . ")</li>";
            }
            echo "</ul>";
            return;
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
        
        // 插入默认数据
        $insertSql = "INSERT INTO banners (title, content, image_url, link_url, status, sort_order) VALUES (?, ?, '', '', 1, ?)";
        $stmt = $db->prepare($insertSql);
        
        foreach ($defaultData as $data) {
            $stmt->execute([$data['title'], $data['content'], $data['sort_order']]);
        }
        
        echo "✓ 已成功插入 " . count($defaultData) . " 条默认轮播数据<br>";
        echo "<h3>已初始化的轮播数据：</h3>";
        echo "<ul>";
        foreach ($defaultData as $data) {
            echo "<li>" . htmlspecialchars($data['title']) . "</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "✗ 错误: " . $e->getMessage();
    }
}

// 执行初始化
echo "<h2>轮播数据初始化</h2>";
initCarouselData();
echo "<hr>";
echo "<p><a href='carousel-manager.html'>返回轮播管理后台</a></p>";

