<?php
/**
 * Banner数据库表设置脚本
 * 检查并创建banners表（如果不存在）
 */

// 引入数据库配置
require_once '../api/config.php';

function setupBannersTable() {
    try {
        $db = getDB();
        
        // 创建banners表的SQL语句
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
        
        // 执行创建表语句
        $db->exec($createTableSql);
        echo "Banners表已成功创建或已存在<br>";
        
        // 添加索引
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_status ON banners (status)",
            "CREATE INDEX IF NOT EXISTS idx_sort_order ON banners (sort_order)",
            "CREATE INDEX IF NOT EXISTS idx_created_at ON banners (created_at)"
        ];
        
        foreach ($indexes as $indexSql) {
            $db->exec($indexSql);
        }
        echo "索引已成功创建<br>";
        
        // 插入示例数据（如果表为空）
        $checkCountSql = "SELECT COUNT(*) as count FROM banners";
        $stmt = $db->query($checkCountSql);
        $count = $stmt->fetch()['count'];
        
        if ($count === 0) {
            $insertSql = "INSERT INTO banners (title, content, image_url, link_url, status, sort_order) VALUES 
                ('欢迎访问JayHome', '这是一个示例Banner，欢迎来到我的个人网站！', 'https://example.com/banner1.jpg', 'https://jayhome.cc', 1, 1),
                ('最新更新', '网站内容已更新，欢迎浏览！', 'https://example.com/banner2.jpg', 'https://jayhome.cc/latest', 1, 2)";
            $db->exec($insertSql);
            echo "已插入示例Banner数据<br>";
        }
        
        // 验证表结构
        $describeSql = "DESCRIBE banners";
        $stmt = $db->query($describeSql);
        $columns = $stmt->fetchAll();
        
        echo "<h3>Banners表结构：</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>字段名</th><th>类型</th><th>是否为空</th><th>键</th><th>默认值</th><th>额外</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Banner管理数据库设置完成！</h3>";
        
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage();
    }
}

// 执行设置
setupBannersTable();
