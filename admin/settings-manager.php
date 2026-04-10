<?php
/**
 * 网站设置管理后台
 */
session_start();

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
    <title>网站设置 - 管理后台</title>
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
        
        .admin-nav {
            display: flex;
            gap: 10px;
        }
        
        .admin-nav a {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--main_text_color);
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .admin-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
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
        
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .settings-section {
            backdrop-filter: blur(var(--card_filter));
            -webkit-backdrop-filter: blur(var(--card_filter));
            background: var(--item_bg_color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .settings-section h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--item_left_text_color);
        }
        
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.2);
            color: var(--main_text_color);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--purple_text_color);
            box-shadow: 0 0 0 3px rgba(155, 89, 182, 0.2);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-hint {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 6px;
        }
        
        .btn-primary {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            background: var(--purple_text_color);
            color: #ffffff;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-row {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .save-message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .save-message.success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.4);
            color: #81c784;
            display: block;
        }
        
        .save-message.error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.4);
            color: #e57373;
            display: block;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .project-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .project-item {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .project-item input {
            flex: 1;
            min-width: 200px;
        }
        
        .project-item .project-desc {
            flex: 2;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-title">网站设置</div>
        <div class="admin-nav">
            <a href="index.php">返回管理后台</a>
        </div>
        <div class="admin-user">
            <span class="admin-username"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <button class="logout-btn" onclick="logout()">退出登录</button>
        </div>
    </div>

    <div class="settings-container">
        <div id="saveMessage" class="save-message"></div>
        
        <div class="settings-section">
            <h2>基础设置</h2>
            <div class="form-group">
                <label for="site_title">网站名称</label>
                <input type="text" class="form-input" id="site_title" placeholder="例如：JayHome" value="JayHome">
            </div>
            <div class="form-group">
                <label for="site_subtitle">副标题 / 欢迎语</label>
                <input type="text" class="form-input" id="site_subtitle" placeholder="例如：Hello I'm Jay" value="Hello I'm Jay">
            </div>
        </div>
        
        <div class="settings-section">
            <h2>音乐播放器</h2>
            <div class="form-group">
                <label for="playlist_id">网易云歌单ID</label>
                <input type="text" class="form-input" id="playlist_id" placeholder="例如：2171200337" value="2171200337">
                <div class="form-hint">在网易云音乐歌单页面的URL中可找到ID</div>
            </div>
        </div>
        
        <div class="settings-section">
            <h2>左侧标签</h2>
            <div class="form-group">
                <label for="left_tags">标签列表（用逗号分隔）</label>
                <input type="text" class="form-input" id="left_tags" placeholder="例如：摄影,徒步,骑行,博主,音乐" value="摄影,徒步,骑行,博主,音乐,美剧,薄肌,剪辑,设计">
                <div class="form-hint">输入标签后用逗号分隔，如：摄影,徒步,骑行</div>
            </div>
        </div>
        
        <div class="settings-section">
            <h2>社交链接</h2>
            <div class="form-group">
                <label for="bilibili_link">B站链接</label>
                <input type="text" class="form-input" id="bilibili_link" placeholder="https://space.bilibili.com/..." value="https://space.bilibili.com/501394812?spm_id_from=333.1007.0.0">
            </div>
            <div class="form-group">
                <label for="wechat_image">微信二维码图片路径</label>
                <input type="text" class="form-input" id="wechat_image" placeholder="./static/img/wx.jpg" value="./static/img/wx.jpg">
            </div>
            <div class="form-group">
                <label for="zanshang_image">赞赏码图片路径</label>
                <input type="text" class="form-input" id="zanshang_image" placeholder="./static/img/wxzsm.jpg" value="./static/img/wxzsm.jpg">
            </div>
            <div class="form-group">
                <label for="github_link">GitHub链接</label>
                <input type="text" class="form-input" id="github_link" placeholder="https://github.com/..." value="https://github.com/Fantasy2000">
            </div>
        </div>
        
        <div class="settings-section">
            <h2>项目板块</h2>
            <div class="form-group">
                <label>项目名称、链接和描述</label>
                <div class="project-items">
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目1名称" value="粼光开源集">
                        <input type="text" class="form-input project-link" placeholder="项目1链接" value="https://www.lgboxs.com/">
                        <input type="text" class="form-input project-desc" placeholder="项目1描述" value="开源软件集">
                    </div>
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目2名称" value="Jay的资源库">
                        <input type="text" class="form-input project-link" placeholder="项目2链接" value="https://lovejay.top/">
                        <input type="text" class="form-input project-desc" placeholder="项目2描述" value="综合网站导航">
                    </div>
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目3名称" value="Jay部落">
                        <input type="text" class="form-input project-link" placeholder="项目3链接" value="https://jayclub.cc/">
                        <input type="text" class="form-input project-desc" placeholder="项目3描述" value="优质软件博客">
                    </div>
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目4名称" value="AI研究所">
                        <input type="text" class="form-input project-link" placeholder="项目4链接" value="https://www.aiyjs.com/">
                        <input type="text" class="form-input project-desc" placeholder="项目4描述" value="国内外AI工具集">
                    </div>
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目5名称" value="4K搜">
                        <input type="text" class="form-input project-link" placeholder="项目5链接" value="https://4kso.com/#/">
                        <input type="text" class="form-input project-desc" placeholder="项目5描述" value="专注4K影视资源">
                    </div>
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目6名称" value="Jay宇宙">
                        <input type="text" class="form-input project-link" placeholder="项目6链接" value="https://jayspace.cn/">
                        <input type="text" class="form-input project-desc" placeholder="项目6描述" value="记录我的点滴">
                    </div>
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目7名称" value="Howsay">
                        <input type="text" class="form-input project-link" placeholder="项目7链接" value="https://howsay.aiyjs.com/">
                        <input type="text" class="form-input project-desc" placeholder="项目7描述" value="我第一个AI编程项目">
                    </div>
                    <div class="project-item">
                        <input type="text" class="form-input project-name" placeholder="项目8名称" value="Jay卡萌">
                        <input type="text" class="form-input project-link" placeholder="项目8链接" value="https://ka.huojukj.com/?u=111888">
                        <input type="text" class="form-input project-desc" placeholder="项目8描述" value="正规流量卡加盟">
                    </div>
                </div>
                <div class="form-hint">每个项目的名称、链接和描述都可以单独修改</div>
            </div>
        </div>
        
        <div class="settings-section">
            <h2>其他设置</h2>
            <div class="form-group">
                <label for="hitokoto_api">一言API地址</label>
                <input type="text" class="form-input" id="hitokoto_api" placeholder="https://v1.hitokoto.cn/" value="https://v1.hitokoto.cn/">
            </div>
        </div>
        
        <div class="btn-row">
            <button class="btn-primary" onclick="saveSettings()" id="saveBtn">保存设置</button>
        </div>
    </div>

    <script>
        function logout() {
            fetch('./change_password.php?action=logout', { credentials: 'same-origin' })
                .then(() => window.location.href = 'login.php');
        }
        
        async function loadSettings() {
            try {
                const response = await fetch('../api/settings.php?action=get', {
                    credentials: 'same-origin'
                });
                const result = await response.json();
                
                if (result.success && result.data) {
                    const settings = result.data;
                    
                    if (settings.site_title) document.getElementById('site_title').value = settings.site_title;
                    if (settings.site_subtitle) document.getElementById('site_subtitle').value = settings.site_subtitle;
                    if (settings.playlist_id) document.getElementById('playlist_id').value = settings.playlist_id;
                    if (settings.left_tags) document.getElementById('left_tags').value = settings.left_tags;
                    if (settings.bilibili_link) document.getElementById('bilibili_link').value = settings.bilibili_link;
                    if (settings.wechat_image) document.getElementById('wechat_image').value = settings.wechat_image;
                    if (settings.zanshang_image) document.getElementById('zanshang_image').value = settings.zanshang_image;
                    if (settings.github_link) document.getElementById('github_link').value = settings.github_link;
                    if (settings.hitokoto_api) document.getElementById('hitokoto_api').value = settings.hitokoto_api;
                    
                    // 加载项目名称、链接和描述
                    if (settings.project_names && settings.project_links && settings.project_descriptions) {
                        const names = settings.project_names.split(',');
                        const links = settings.project_links.split(',');
                        const descriptions = settings.project_descriptions.split(',');
                        const nameInputs = document.querySelectorAll('.project-name');
                        const linkInputs = document.querySelectorAll('.project-link');
                        const descInputs = document.querySelectorAll('.project-desc');
                        
                        nameInputs.forEach((input, index) => {
                            if (names[index]) input.value = names[index].trim();
                        });
                        
                        linkInputs.forEach((input, index) => {
                            if (links[index]) input.value = links[index].trim();
                        });
                        
                        descInputs.forEach((input, index) => {
                            if (descriptions[index]) input.value = descriptions[index].trim();
                        });
                    }
                }
            } catch (error) {
                console.error('加载设置失败:', error);
            }
        }
        
        async function saveSettings() {
            const saveBtn = document.getElementById('saveBtn');
            const messageEl = document.getElementById('saveMessage');
            
            // 收集项目名称、链接和描述
            const nameInputs = document.querySelectorAll('.project-name');
            const linkInputs = document.querySelectorAll('.project-link');
            const descInputs = document.querySelectorAll('.project-desc');
            const projectNames = Array.from(nameInputs).map(input => input.value.trim()).join(',');
            const projectLinks = Array.from(linkInputs).map(input => input.value.trim()).join(',');
            const projectDescriptions = Array.from(descInputs).map(input => input.value.trim()).join(',');
            
            const settings = {
                site_title: document.getElementById('site_title').value,
                site_subtitle: document.getElementById('site_subtitle').value,
                playlist_id: document.getElementById('playlist_id').value,
                left_tags: document.getElementById('left_tags').value,
                bilibili_link: document.getElementById('bilibili_link').value,
                wechat_image: document.getElementById('wechat_image').value,
                zanshang_image: document.getElementById('zanshang_image').value,
                github_link: document.getElementById('github_link').value,
                project_names: projectNames,
                project_links: projectLinks,
                project_descriptions: projectDescriptions,
                hitokoto_api: document.getElementById('hitokoto_api').value
            };
            
            saveBtn.disabled = true;
            saveBtn.textContent = '保存中...';
            
            try {
                const response = await fetch('../api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(settings),
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error('服务器响应错误: ' + response.status);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    messageEl.textContent = '保存成功！';
                    messageEl.className = 'save-message success';
                    
                    setTimeout(() => {
                        messageEl.className = 'save-message';
                    }, 3000);
                } else {
                    messageEl.textContent = '保存失败: ' + (result.message || '未知错误');
                    messageEl.className = 'save-message error';
                }
            } catch (error) {
                console.error('保存设置时发生错误:', error);
                messageEl.textContent = '保存失败: ' + error.message;
                messageEl.className = 'save-message error';
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = '保存设置';
            }
        }
        
        loadSettings();
    </script>
</body>
</html>
