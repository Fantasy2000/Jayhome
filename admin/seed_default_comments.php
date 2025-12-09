<?php
/**
 * 批量插入默认评论脚本
 * 访问此文件将把预置的评论插入到 comments 表（顶层评论，status=1）
 */
require_once '../api/config.php';

echo '<h2>默认评论导入</h2>';

try {
    $db = getDB();

    // 自动迁移缺失列（location、parent_id）
    try {
        $check = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'location'");
        $check->execute();
        if (!$check->fetch()) {
            $db->exec("ALTER TABLE comments ADD COLUMN location VARCHAR(100) NULL COMMENT '城市/地区' AFTER user_agent");
        }
        $checkP = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'comments' AND column_name = 'parent_id'");
        $checkP->execute();
        if (!$checkP->fetch()) {
            $db->exec("ALTER TABLE comments ADD COLUMN parent_id INT NULL DEFAULT NULL COMMENT '父评论ID' AFTER id");
            $db->exec("CREATE INDEX idx_parent_id ON comments(parent_id)");
        }
    } catch (Exception $e) {
        // 忽略自动迁移失败
    }

    $items = [
        '粼光开源集的资源能不能不用百度网盘啊，可以试试夸克',
        '现在都没有19元的流量卡了么',
        '首页是我基于github上ZYYO666项日二改的',
        '站长这个首页源码可以分享一个么，好好看',
        '资源库过来的',
        '站长做网站应该有4年了吧',
        '流量卡怎么做副业啊，有群么',
        'Jay部落的资源该更新了，站长',
        '这个弹幕是实时显示的么',
        'kity你看到了吗，这是jay',
        '为什么弹幕不现实了',
        '首页UI改变了很多啊',
    ];

    $insert = $db->prepare("INSERT INTO comments (text, location, ip_address, user_agent, status, parent_id, created_at) VALUES (:text, :location, :ip, :ua, 1, NULL, NOW())");

    $count = 0;
    foreach ($items as $t) {
        $insert->execute([
            ':text' => $t,
            ':location' => '网络用户',
            ':ip' => '0.0.0.0',
            ':ua' => 'seed-script'
        ]);
        $count++;
    }

    echo '<p>已成功插入 ' . $count . ' 条默认评论。</p>';

    // 展示最近插入的内容
    $stmt = $db->query("SELECT id, text, location, created_at FROM comments ORDER BY id DESC LIMIT 20");
    $rows = $stmt->fetchAll();
    echo '<table border="1" cellspacing="0" cellpadding="6">';
    echo '<tr><th>ID</th><th>内容</th><th>位置</th><th>时间</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . (int)$r['id'] . '</td><td>' . htmlspecialchars($r['text']) . '</td><td>' . htmlspecialchars($r['location'] ?? '') . '</td><td>' . htmlspecialchars($r['created_at']) . '</td></tr>';
    }
    echo '</table>';

    echo '<p><a href="../index.html" target="_blank">返回首页</a> | <a href="carousel-manager.html">进入后台</a></p>';
} catch (Exception $e) {
    echo '<p style="color:red">导入失败：' . htmlspecialchars($e->getMessage()) . '</p>';
}

