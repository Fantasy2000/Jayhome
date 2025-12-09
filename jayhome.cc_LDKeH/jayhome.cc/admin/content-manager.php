<?php
/**
 * 内容管理后台 - 统一管理评论和Banner
 */
session_start();

// 检查登录状态
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>内容管理后台 - 评论和Banner</title>
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
        
        .tab-navigation {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            backdrop-filter: blur(var(--card_filter));
            -webkit-backdrop-filter: blur(var(--card_filter));
            background: var(--item_bg_color);
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .tab-btn {
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--main_text_color);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            background: var(--purple_text_color);
            color: #ffffff;
            border-color: var(--purple_text_color);
        }
        
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .admin-content {
            backdrop-filter: blur(var(--card_filter));
            -webkit-backdrop-filter: blur(var(--card_filter));
            background: var(--item_bg_color);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
        
        .add-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            background: var(--purple_text_color);
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
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
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        th {
            font-weight: 600;
            color: var(--item_left_text_color);
            font-size: 13px;
        }
        
        td {
            font-size: 14px;
        }
        
        .text-truncate {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.3);
        }
        
        .btn-delete {
            background: rgba(255, 0, 0, 0.2);
            color: #ff4444;
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
        
        /* Modal样式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--item_bg_color);
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--main_text_color);
            font-size: 24px;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--main_text_color);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            border-color: var(--purple_text_color);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-submit, .btn-cancel {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit {
            background: var(--purple_text_color);
            color: #ffffff;
        }
        
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: var(--main_text_color);
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
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
            
            table {
                font-size: 12px;
            }
            
            .text-truncate {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1 class="admin-title">内容管理后台</h1>
        <div class="admin-user">
            <span class="admin-username">欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <button class="logout-btn" onclick="logout()">退出登录</button>
        </div>
    </div>
    
    <!-- 标签导航 -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="switchTab('comments')">评论管理</button>
        <button class="tab-btn" onclick="switchTab('banners')">Banner管理</button>
    </div>
    
    <div class="admin-content">
        <!-- 评论管理标签 -->
        <div id="comments-tab" class="tab-content active">
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
                        id="commentSearchInput" 
                        placeholder="搜索评论内容..."
                    >
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-status="1" onclick="filterCommentsByStatus(1)">显示中</button>
                        <button class="filter-btn" data-status="0" onclick="filterCommentsByStatus(0)">已隐藏</button>
                        <button class="filter-btn" data-status="all" onclick="filterCommentsByStatus('all')">全部</button>
                    </div>
                    <button class="add-btn" style="background:#ef4444" onclick="clearAllComments()">清空所有评论</button>
                </div>
            </div>
            
            <div id="commentsContainer">
                <div class="empty-state">加载中...</div>
            </div>
            
            <div class="pagination" id="commentsPagination"></div>
        </div>
        
        <!-- Banner管理标签 -->
        <div id="banners-tab" class="tab-content">
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">总Banner数</div>
                    <div class="stat-value" id="bannerTotalCount">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">显示中</div>
                    <div class="stat-value" id="bannerActiveCount">0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">已隐藏</div>
                    <div class="stat-value" id="bannerHiddenCount">0</div>
                </div>
            </div>
            
            <div class="toolbar">
                <div class="search-box">
                    <input 
                        type="text" 
                        class="search-input" 
                        id="bannerSearchInput" 
                        placeholder="搜索Banner内容..."
                    >
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-status="1" onclick="filterBannersByStatus(1)">显示中</button>
                        <button class="filter-btn" data-status="0" onclick="filterBannersByStatus(0)">已隐藏</button>
                        <button class="filter-btn" data-status="all" onclick="filterBannersByStatus('all')">全部</button>
                    </div>
                    <button class="add-btn" onclick="openBannerModal()">+ 添加Banner</button>
                </div>
            </div>
            
            <div id="bannersContainer">
                <div class="empty-state">加载中...</div>
            </div>
            
            <div class="pagination" id="bannersPagination"></div>
        </div>
    </div>
    
    <!-- Banner编辑Modal -->
    <div id="bannerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="bannerModalTitle">添加Banner</h2>
                <button class="modal-close" onclick="closeBannerModal()">&times;</button>
            </div>
            <form id="bannerForm" onsubmit="saveBanner(event)">
                <div class="form-group">
                    <label class="form-label">标题 *</label>
                    <input type="text" class="form-input" id="bannerTitle" required>
                </div>
                <div class="form-group">
                    <label class="form-label">内容 *</label>
                    <textarea class="form-textarea" id="bannerContent" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">图片URL</label>
                    <input type="text" class="form-input" id="bannerImageUrl" placeholder="https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label class="form-label">链接URL</label>
                    <input type="text" class="form-input" id="bannerLinkUrl" placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label class="form-label">排序顺序</label>
                    <input type="number" class="form-input" id="bannerSortOrder" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select class="form-select" id="bannerStatus">
                        <option value="1">显示</option>
                        <option value="0">隐藏</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeBannerModal()">取消</button>
                    <button type="submit" class="btn-submit">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentCommentPage = 1;
        let currentCommentStatus = 1;
        let currentCommentSearch = '';
        let commentPagination = null;
        
        let currentBannerPage = 1;
        let currentBannerStatus = 1;
        let currentBannerSearch = '';
        let bannerPagination = null;
        
        let editingBannerId = null;
        
        // 切换标签
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tab + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            if (tab === 'comments') {
                loadComments(1);
            } else if (tab === 'banners') {
                loadBanners(1);
            }
        }
        
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
        
        // ========== 评论管理 ==========
        
        async function loadComments(page = 1) {
            const container = document.getElementById('commentsContainer');
            container.innerHTML = '<div class="empty-state">加载中...</div>';
            
            try {
                const url = `../api/comments.php?action=list&page=${page}&limit=20&status=${currentCommentStatus}${currentCommentSearch ? '&search=' + encodeURIComponent(currentCommentSearch) : ''}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    commentPagination = result.pagination;
                    displayComments(result.data);
                    updateCommentPagination();
                    updateCommentStats();
                } else {
                    container.innerHTML = '<div class="empty-state">加载失败: ' + result.message + '</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="empty-state">网络错误，请稍后重试</div>';
            }
        }
        
        function displayComments(comments) {
            const container = document.getElementById('commentsContainer');
            
            if (comments.length === 0) {
                container.innerHTML = '<div class="empty-state">暂无评论</div>';
                return;
            }
            
            let html = '<div class="table-container"><table><thead><tr><th>ID</th><th>评论内容</th><th>点赞数</th><th>IP地址</th><th>创建时间</th><th>操作</th></tr></thead><tbody>';
            
            comments.forEach(comment => {
                const date = new Date(comment.created_at);
                const dateStr = date.toLocaleString('zh-CN');
                
                html += `
                    <tr>
                        <td>${comment.id}</td>
                        <td class="text-truncate" title="${escapeHtml(comment.text)}">${escapeHtml(comment.text)}</td>
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
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
        
        function updateCommentPagination() {
            const paginationDiv = document.getElementById('commentsPagination');
            
            if (!commentPagination || commentPagination.pages <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }
            
            let html = '';
            html += `<button class="pagination-btn" ${currentCommentPage <= 1 ? 'disabled' : ''} onclick="loadComments(${currentCommentPage - 1})">上一页</button>`;
            html += `<span class="pagination-info">第 ${currentCommentPage} / ${commentPagination.pages} 页，共 ${commentPagination.total} 条</span>`;
            html += `<button class="pagination-btn" ${currentCommentPage >= commentPagination.pages ? 'disabled' : ''} onclick="loadComments(${currentCommentPage + 1})">下一页</button>`;
            
            paginationDiv.innerHTML = html;
        }
        
        async function updateCommentStats() {
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
                    const totalLikes = allData.data.reduce((sum, comment) => sum + (comment.likes || 0), 0);
                    document.getElementById('totalLikes').textContent = totalLikes;
                }
                if (activeData.success) document.getElementById('activeCount').textContent = activeData.pagination.total;
                if (hiddenData.success) document.getElementById('hiddenCount').textContent = hiddenData.pagination.total;
            } catch (error) {
                console.error('更新统计失败:', error);
            }
        }
        
        function filterCommentsByStatus(status) {
            currentCommentStatus = status;
            currentCommentPage = 1;
            
            document.querySelectorAll('#comments-tab .filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status == status);
            });
            
            loadComments(1);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('commentSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    currentCommentSearch = e.target.value;
                    currentCommentPage = 1;
                    loadComments(1);
                });
            }
        });
        
        async function deleteComment(id) {
            if (!confirm('确定要删除这条评论吗？')) return;
            
            try {
                const response = await fetch(`../api/comments.php?id=${id}&type=hard`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('删除成功');
                    loadComments(currentCommentPage);
                    updateCommentStats();
                } else {
                    alert('删除失败: ' + result.message);
                }
            } catch (error) {
                alert('网络错误，请稍后重试');
            }
        }
        
        // ========== Banner管理 ==========
        
        async function loadBanners(page = 1) {
            const container = document.getElementById('bannersContainer');
            container.innerHTML = '<div class="empty-state">加载中...</div>';
            
            try {
                const url = `../api/banners.php?action=list&page=${page}&limit=20&status=${currentBannerStatus}${currentBannerSearch ? '&search=' + encodeURIComponent(currentBannerSearch) : ''}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    bannerPagination = result.pagination;
                    displayBanners(result.data);
                    updateBannerPagination();
                    updateBannerStats();
                } else {
                    container.innerHTML = '<div class="empty-state">加载失败: ' + result.message + '</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="empty-state">网络错误，请稍后重试</div>';
            }
        }
        
        function displayBanners(banners) {
            const container = document.getElementById('bannersContainer');
            
            if (banners.length === 0) {
                container.innerHTML = '<div class="empty-state">暂无Banner</div>';
                return;
            }
            
            let html = '<div class="table-container"><table><thead><tr><th>ID</th><th>标题</th><th>内容</th><th>状态</th><th>排序</th><th>创建时间</th><th>操作</th></tr></thead><tbody>';
            
            banners.forEach(banner => {
                const date = new Date(banner.created_at);
                const dateStr = date.toLocaleString('zh-CN');
                const statusText = banner.status === 1 ? '显示' : '隐藏';
                
                html += `
                    <tr>
                        <td>${banner.id}</td>
                        <td class="text-truncate" title="${escapeHtml(banner.title)}">${escapeHtml(banner.title)}</td>
                        <td class="text-truncate" title="${escapeHtml(banner.content)}">${escapeHtml(banner.content)}</td>
                        <td>${statusText}</td>
                        <td>${banner.sort_order}</td>
                        <td>${dateStr}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-edit" onclick="editBanner(${banner.id})">编辑</button>
                                <button class="btn-delete" onclick="deleteBanner(${banner.id})">删除</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
        
        function updateBannerPagination() {
            const paginationDiv = document.getElementById('bannersPagination');
            
            if (!bannerPagination || bannerPagination.pages <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }
            
            let html = '';
            html += `<button class="pagination-btn" ${currentBannerPage <= 1 ? 'disabled' : ''} onclick="loadBanners(${currentBannerPage - 1})">上一页</button>`;
            html += `<span class="pagination-info">第 ${currentBannerPage} / ${bannerPagination.pages} 页，共 ${bannerPagination.total} 条</span>`;
            html += `<button class="pagination-btn" ${currentBannerPage >= bannerPagination.pages ? 'disabled' : ''} onclick="loadBanners(${currentBannerPage + 1})">下一页</button>`;
            
            paginationDiv.innerHTML = html;
        }
        
        async function updateBannerStats() {
            try {
                const [allRes, activeRes, hiddenRes] = await Promise.all([
                    fetch('../api/banners.php?action=list&page=1&limit=1000&status=all'),
                    fetch('../api/banners.php?action=list&page=1&limit=1000&status=1'),
                    fetch('../api/banners.php?action=list&page=1&limit=1000&status=0')
                ]);
                
                const [allData, activeData, hiddenData] = await Promise.all([
                    allRes.json(),
                    activeRes.json(),
                    hiddenRes.json()
                ]);
                
                if (allData.success) document.getElementById('bannerTotalCount').textContent = allData.pagination.total;
                if (activeData.success) document.getElementById('bannerActiveCount').textContent = activeData.pagination.total;
                if (hiddenData.success) document.getElementById('bannerHiddenCount').textContent = hiddenData.pagination.total;
            } catch (error) {
                console.error('更新统计失败:', error);
            }
        }
        
        function filterBannersByStatus(status) {
            currentBannerStatus = status;
            currentBannerPage = 1;
            
            document.querySelectorAll('#banners-tab .filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status == status);
            });
            
            loadBanners(1);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('bannerSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    currentBannerSearch = e.target.value;
                    currentBannerPage = 1;
                    loadBanners(1);
                });
            }
        });
        
        function openBannerModal() {
            editingBannerId = null;
            document.getElementById('bannerModalTitle').textContent = '添加Banner';
            document.getElementById('bannerForm').reset();
            document.getElementById('bannerModal').classList.add('active');
        }
        
        function closeBannerModal() {
            document.getElementById('bannerModal').classList.remove('active');
            editingBannerId = null;
        }
        
        async function editBanner(id) {
            try {
                editingBannerId = id;
                document.getElementById('bannerModalTitle').textContent = '编辑Banner';
                
                // 从API获取Banner详情
                const response = await fetch(`../api/banners.php?id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const banner = result.data;
                    document.getElementById('bannerTitle').value = banner.title || '';
                    document.getElementById('bannerContent').value = banner.content || '';
                    document.getElementById('bannerImageUrl').value = banner.image_url || '';
                    document.getElementById('bannerLinkUrl').value = banner.link_url || '';
                    document.getElementById('bannerSortOrder').value = banner.sort_order || 0;
                    document.getElementById('bannerStatus').value = banner.status || 1;
                }
                
                document.getElementById('bannerModal').classList.add('active');
            } catch (error) {
                console.error('加载Banner详情失败:', error);
                alert('加载失败，请稍后重试');
            }
        }
        
        async function saveBanner(event) {
            event.preventDefault();
            
            const data = {
                title: document.getElementById('bannerTitle').value,
                content: document.getElementById('bannerContent').value,
                image_url: document.getElementById('bannerImageUrl').value,
                link_url: document.getElementById('bannerLinkUrl').value,
                sort_order: parseInt(document.getElementById('bannerSortOrder').value),
                status: parseInt(document.getElementById('bannerStatus').value)
            };
            
            if (editingBannerId) {
                data.id = editingBannerId;
            }
            
            try {
                const method = editingBannerId ? 'PUT' : 'POST';
                const response = await fetch('../api/banners.php', {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(editingBannerId ? '更新成功' : '添加成功');
                    closeBannerModal();
                    loadBanners(currentBannerPage);
                } else {
                    alert('保存失败: ' + result.message);
                }
            } catch (error) {
                alert('网络错误，请稍后重试');
            }
        }
        
        async function deleteBanner(id) {
            if (!confirm('确定要删除这个Banner吗？')) return;
            
            try {
                const response = await fetch(`../api/banners.php?id=${id}&type=hard`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('删除成功');
                    loadBanners(currentBannerPage);
                    updateBannerStats();
                } else {
                    alert('删除失败: ' + result.message);
                }
            } catch (error) {
                alert('网络错误，请稍后重试');
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        async function clearAllComments() {
            if (!confirm('确定要清空所有评论吗？此操作不可恢复！')) return;
            try {
                const resp = await fetch('../api/comments.php?action=clear', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin'
                });
                const result = await resp.json();
                if (result.success) {
                    alert('已清空所有评论');
                    loadComments(1);
                    updateCommentStats();
                } else {
                    alert('清空失败：' + (result.message || '未知错误'));
                }
            } catch (e) {
                alert('网络错误，请稍后重试');
            }
        }

        // 初始化
        checkAuth();
        loadComments(1);
        updateCommentStats();
        
        // 定期检查登录状态
        setInterval(checkAuth, 300000);
    </script>
</body>
</html>

