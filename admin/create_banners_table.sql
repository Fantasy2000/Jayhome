-- 创建banners表（如果不存在）
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT 'Banner标题',
    content TEXT NOT NULL COMMENT 'Banner内容',
    image_url VARCHAR(500) DEFAULT '' COMMENT '图片URL',
    link_url VARCHAR(500) DEFAULT '' COMMENT '链接URL',
    status TINYINT DEFAULT 1 COMMENT '状态：1-显示，0-隐藏',
    sort_order INT DEFAULT 0 COMMENT '排序顺序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 添加索引以提高查询性能
ALTER TABLE banners ADD INDEX idx_status (status);
ALTER TABLE banners ADD INDEX idx_sort_order (sort_order);
ALTER TABLE banners ADD INDEX idx_created_at (created_at);

-- 检查表结构是否已创建成功
SELECT 'Banners表已成功创建或已存在' AS result;

-- 插入示例数据（可选）
INSERT INTO banners (title, content, image_url, link_url, status, sort_order) VALUES 
('欢迎访问JayHome', '这是一个示例Banner，欢迎来到我的个人网站！', 'https://example.com/banner1.jpg', 'https://jayhome.cc', 1, 1),
('最新更新', '网站内容已更新，欢迎浏览！', 'https://example.com/banner2.jpg', 'https://jayhome.cc/latest', 1, 2)
ON DUPLICATE KEY UPDATE title=title; -- 避免重复插入
