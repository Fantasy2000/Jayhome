/**
 * 网站设置加载器
 * 从API获取设置并应用到前端
 */

let siteSettings = {};

async function loadSiteSettings() {
    try {
        const response = await fetch('./api/settings.php?action=get', {
            credentials: 'same-origin'
        });
        const result = await response.json();
        
        if (result.success && result.data) {
            console.log('加载网站设置成功:', result.data);
            siteSettings = result.data;
            applySettings();
        } else {
            console.log('加载网站设置失败，保持默认值');
        }
    } catch (error) {
        console.error('加载网站设置失败:', error);
        console.log('API调用失败，保持默认值');
    }
}

function applySettings() {
    applyHeaderText();
    applyLeftTags();
    applySocialLinks();
    applyProjectLinks();
    applyMusicPlayer();
}

function applyHeaderText() {
    if (siteSettings.site_title) {
        document.title = siteSettings.site_title;
    }
    
    const welcomeEl = document.querySelector('.welcome .gradientText');
    if (welcomeEl && siteSettings.site_subtitle) {
        welcomeEl.textContent = siteSettings.site_subtitle;
    }
}

function applyLeftTags() {
    const tagsContainer = document.querySelector('.left-tag');
    if (!tagsContainer || !siteSettings.left_tags) return;
    
    const tags = siteSettings.left_tags.split(',').map(t => t.trim()).filter(t => t);
    if (tags.length === 0) return;
    
    tagsContainer.innerHTML = tags.map(tag => 
        `<div class="left-tag-item">${escapeHtml(tag)}</div>`
    ).join('');
}

function applySocialLinks() {
    const bilibiliLink = document.querySelector('.iconItem[href*="bilibili"]');
    if (bilibiliLink && siteSettings.bilibili_link) {
        bilibiliLink.href = siteSettings.bilibili_link;
    }
    
    const wechatLink = document.querySelector('.iconItem[onclick*="wx.jpg"]');
    if (wechatLink && siteSettings.wechat_image) {
        wechatLink.setAttribute('onclick', `pop('${siteSettings.wechat_image}')`);
    }
    
    const zanshangLink = document.querySelector('.iconItem[onclick*="wxzsm.jpg"]');
    if (zanshangLink && siteSettings.zanshang_image) {
        zanshangLink.setAttribute('onclick', `pop('${siteSettings.zanshang_image}')`);
    }
    
    const githubLink = document.querySelector('.iconItem[href*="github"]');
    if (githubLink && siteSettings.github_link) {
        githubLink.href = siteSettings.github_link;
    }
}

function applyProjectLinks() {
    // 只有当设置都存在且不为空时才更新
    if (!siteSettings || !siteSettings.project_names || !siteSettings.project_links || 
        siteSettings.project_names.trim() === '' || siteSettings.project_links.trim() === '') {
        console.log('项目链接设置为空，保持默认值');
        return;
    }
    
    try {
        const names = siteSettings.project_names.split(',').map(n => n.trim()).filter(n => n);
        const links = siteSettings.project_links.split(',').map(l => l.trim()).filter(l => l);
        const descriptions = siteSettings.project_descriptions ? 
            siteSettings.project_descriptions.split(',').map(d => d.trim()).filter(d => d) : [];
        
        if (names.length === 0 || links.length === 0) {
            console.log('项目链接设置解析为空，保持默认值');
            return;
        }
        
        const projectItems = document.querySelectorAll('.projectItem');
        if (projectItems.length === 0) {
            console.log('未找到项目项，保持默认值');
            return;
        }
        
        projectItems.forEach((item, index) => {
            if (links[index]) {
                item.href = links[index];
            }
            // 更新项目名称
            if (names[index]) {
                const projectTitle = item.querySelector('.projectItemLeft h1');
                if (projectTitle) {
                    projectTitle.textContent = names[index];
                }
            }
            // 更新项目描述
            if (descriptions[index]) {
                const projectDesc = item.querySelector('.projectItemLeft p');
                if (projectDesc) {
                    projectDesc.textContent = descriptions[index];
                }
            }
        });
        console.log('项目链接更新成功');
    } catch (error) {
        console.error('更新项目链接失败:', error);
    }
}

function applyMusicPlayer() {
    if (siteSettings.playlist_id && window.musicPlayer) {
        window.musicPlayer.reloadPlaylist();
    }
}

window.addEventListener('DOMContentLoaded', () => {
    loadSiteSettings();
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.siteSettings = siteSettings;
window.loadSiteSettings = loadSiteSettings;
window.reloadSettings = loadSiteSettings;
