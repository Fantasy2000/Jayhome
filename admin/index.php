<?php
/**
 * 后台管理重定向
 * 统一使用content-manager.php作为管理后台
 */
session_start();

// 检查登录状态
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit;
}

// 重定向到统一的管理后台
header('Location: content-manager.php');
exit;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>评论管理后台</title>
    <link rel="stylesheet" href="../static/css/style.css">
    <link rel="stylesheet" href="../static/css/root.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--main_bg_color);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--main_text_color);
            font-family: "b", "a", sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        
        .admin-header {
            backdrop-filter: blur(var(--card_filter));
            -webkit-backdrop-filter: blur(var(--card_filter));
            background: var(--item_bg_color);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .admin-title {
            font-size: 24px;
            font-weight: 800;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-username {
            font-size: 14px;
            color: var(--item_left_text_color);
        }
        
        .logout-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            background: var(--purple_text_color);
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .admin-content {
            backdrop-filter: blur(var(--card_filter));
            -webkit-backdrop-filter: blur(var(--card_filter));
            background: var(--item_bg_color);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
            max-width: 400px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--main_text_color);
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--purple_text_color);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--main_text_color);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active {
            background: var(--purple_text_color);
            color: #ffffff;
            border-color: var(--purple_text_color);
        }
        
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--item_left_text_color);
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--main_text_color);
        }
        
        .comments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .comments-table th,
        .comments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .comments-table th {
            font-weight: 600;
            color: var(--item_left_text_color);
            font-size: 13px;
        }
        
        .comments-table td {
            font-size: 14px;
        }
        
        .comment-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            background: rgba(255, 0, 0, 0.2);
            color: #ff4444;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background: rgba(255, 0, 0, 0.3);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--main_text_color);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-info {
            font-size: 14px;
            color: var(--item_left_text_color);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--item_left_text_color);
        }
        
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .toolbar {
                flex-direction: column;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .comments-table {
                font-size: 12px;
            }
            
            .comment-text {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1 class="admin-title">评论管理后台</h1>
        <div class="admin-user">
            <span class="admin-username">欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <button class="logout-btn" onclick="logout()">退出登录</button>
        </div>
    </div>
    
    <div class="admin-content">
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">总评论数</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">显示中</div>
                <div class="stat-value" id="activeCount">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">已隐藏</div>
                <div class="stat-value" id="hiddenCount">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">总点赞数</div>
                <div class="stat-value" id="totalLikes">0</div>
            </div>
        </div>
        
        <div class="toolbar">
            <div class="search-box">
                <input 
                    type="text" 
                    class="search-input" 
                    id="searchInput" 
                    placeholder="搜索评论内容..."
                >
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-status="1" onclick="filterByStatus(1)">显示中</button>
                <button class="filter-btn" data-status="0" onclick="filterByStatus(0)">已隐藏</button>
                <button class="filter-btn" data-status="all" onclick="filterByStatus('all')">全部</button>
            </div>
        </div>
        
        <div id="commentsContainer">
            <div class="empty-state">加载中...</div>
        </div>
        
        <div class="pagination" id="pagination"></div>
    </div>

    <script>
        let currentPage = 1;
        let currentStatus = 1;
        let currentSearch = '';
        let pagination = null;
        
        // 检查登录状态
        async function checkAuth() {
            try {
                const response = await fetch('../api/auth.php?action=check');
                const result = await response.json();
                if (!result.authenticated) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error('检查登录状态失败:', error);
            }
        }
        
        // 退出登录
        async function logout() {
            if (confirm('确定要退出登录吗？')) {
                try {
                    await fetch('../api/auth.php?action=logout', { method: 'POST' });
                    window.location.href = 'login.php';
                } catch (error) {
                    console.error('退出登录失败:', error);
                }
            }
        }
        
        // 加载评论列表
        async function loadComments(page = 1) {
            const container = document.getElementById('commentsContainer');
            container.innerHTML = '<div class="empty-state">加载中...</div>';
            
            try {
                const url = `../api/comments.php?action=list&page=${page}&limit=20&status=${currentStatus}${currentSearch ? '&search=' + encodeURIComponent(currentSearch) : ''}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    pagination = result.pagination;
                    displayComments(result.data);
                    updatePagination();
                    updateStats();
                } else {
                    container.innerHTML = '<div class="empty-state">加载失败: ' + result.message + '</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="empty-state">网络错误，请稍后重试</div>';
            }
        }
        
        // 显示评论列表
        function displayComments(comments) {
            const container = document.getElementById('commentsContainer');
            
            if (comments.length === 0) {
                container.innerHTML = '<div class="empty-state">暂无评论</div>';
                return;
            }
            
            let html = '<table class="comments-table"><thead><tr><th>ID</th><th>评论内容</th><th>点赞数</th><th>IP地址</th><th>创建时间</th><th>操作</th></tr></thead><tbody>';
            
            comments.forEach(comment => {
                const date = new Date(comment.created_at);
                const dateStr = date.toLocaleString('zh-CN');
                
                html += `
                    <tr>
                        <td>${comment.id}</td>
                        <td class="comment-text" title="${escapeHtml(comment.text)}">${escapeHtml(comment.text)}</td>
                        <td>${comment.likes || 0}</td>
                        <td>${comment.ip_address || '-'}</td>
                        <td>${dateStr}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-delete" onclick="deleteComment(${comment.id})">删除</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        // 更新分页
        function updatePagination() {
            const paginationDiv = document.getElementById('pagination');
            
            if (!pagination || pagination.pages <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }
            
            let html = '';
            
            // 上一页
            html += `<button class="pagination-btn" ${currentPage <= 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">上一页</button>`;
            
            // 页码
            html += `<span class="pagination-info">第 ${currentPage} / ${pagination.pages} 页，共 ${pagination.total} 条</span>`;
            
            // 下一页
            html += `<button class="pagination-btn" ${currentPage >= pagination.pages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">下一页</button>`;
            
            paginationDiv.innerHTML = html;
        }
        
        // 更新统计
        async function updateStats() {
            try {
                const [allRes, activeRes, hiddenRes] = await Promise.all([
                    fetch('../api/comments.php?action=list&page=1&limit=1000&status=all'),
                    fetch('../api/comments.php?action=list&page=1&limit=1000&status=1'),
                    fetch('../api/comments.php?action=list&page=1&limit=1000&status=0')
                ]);
                
                const [allData, activeData, hiddenData] = await Promise.all([
                    allRes.json(),
                    activeRes.json(),
                    hiddenRes.json()
                ]);
                
                if (allData.success) {
                    document.getElementById('totalCount').textContent = allData.pagination.total;
                    // 计算总点赞数
                    const totalLikes = allData.data.reduce((sum, comment) => sum + (comment.likes || 0), 0);
                    document.getElementById('totalLikes').textContent = totalLikes;
                }
                if (activeData.success) document.getElementById('activeCount').textContent = activeData.pagination.total;
                if (hiddenData.success) document.getElementById('hiddenCount').textContent = hiddenData.pagination.total;
            } catch (error) {
                console.error('更新统计失败:', error);
            }
        }
        
        // 切换状态筛选
        function filterByStatus(status) {
            currentStatus = status;
            currentPage = 1;
            
            // 更新按钮状态
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status == status);
            });
            
            loadComments(1);
        }
        
        // 搜索
        document.getElementById('searchInput').addEventListener('input', function(e) {
            currentSearch = e.target.value;
            currentPage = 1;
            loadComments(1);
        });
        
        // 切换页码
        function changePage(page) {
            if (page < 1 || (pagination && page > pagination.pages)) return;
            currentPage = page;
            loadComments(page);
        }
        
        // 删除评论
        async function deleteComment(id) {
            if (!confirm('确定要删除这条评论吗？')) return;
            
            try {
                const response = await fetch(`../api/comments.php?id=${id}&type=hard`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('删除成功');
                    loadComments(currentPage);
                    updateStats();
                } else {
                    alert('删除失败: ' + result.message);
                }
            } catch (error) {
                alert('网络错误，请稍后重试');
            }
        }
        
        // HTML转义
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // 初始化
        checkAuth();
        loadComments(1);
        updateStats();
        
        // 定期检查登录状态
        setInterval(checkAuth, 300000); // 5分钟检查一次
    </script>
</body>
</html>


