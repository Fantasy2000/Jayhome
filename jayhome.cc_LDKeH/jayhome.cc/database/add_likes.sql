-- 为评论表添加点赞字段
ALTER TABLE `comments` 
ADD COLUMN `likes` int(11) DEFAULT 0 COMMENT '点赞数' AFTER `status`,
ADD INDEX `idx_likes` (`likes`);

-- 创建点赞记录表（可选，用于防止重复点赞）
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '点赞记录ID',
  `comment_id` int(11) NOT NULL COMMENT '评论ID',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_comment_id` (`comment_id`),
  KEY `idx_ip` (`ip_address`),
  UNIQUE KEY `unique_like` (`comment_id`, `ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论点赞记录表';


