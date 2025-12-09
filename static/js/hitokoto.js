/**
 * 一言名言功能
 * 调用 https://developer.hitokoto.cn/sentence/
 */

class HitokotoWidget {
    constructor() {
        // 优先使用左侧容器，否则使用原容器
        this.container = document.getElementById('hitokoto-container-left') || document.getElementById('hitokoto-container');
        this.apiUrl = 'https://v1.hitokoto.cn';
        this.isDesktop = window.innerWidth > 900;
        this.init();
    }

    init() {
        // 在所有设备上显示
        if (this.container) {
            this.container.style.display = 'flex';
            this.loadHitokoto();
            
            // 点击刷新
            this.container.addEventListener('click', () => this.loadHitokoto());

            // 响应式处理
            window.addEventListener('resize', () => {
                const isDesktop = window.innerWidth > 900;
                if (isDesktop !== this.isDesktop) {
                    this.isDesktop = isDesktop;
                    // 始终显示
                    this.container.style.display = 'flex';
                }
            });
        }
    }

    async loadHitokoto() {
        try {
            this.container.innerHTML = '<div class="hitokoto-loading">加载中...</div>';

            const response = await fetch(`${this.apiUrl}?encode=json`, {
                mode: 'cors',
                timeout: 5000
            });

            if (!response.ok) throw new Error('Failed to fetch hitokoto');

            const data = await response.json();
            this.displayHitokoto(data);
        } catch (error) {
            console.warn('获取一言名言失败', error);
            this.displayError();
        }
    }

    displayHitokoto(data) {
        const text = data.hitokoto || '加载失败';
        const author = data.from || '未知';
        const type = data.type || 'a';

        // 获取分类名称
        const typeNames = {
            'a': '动画',
            'b': '漫画',
            'c': '游戏',
            'd': '文学',
            'e': '原创',
            'f': '网络',
            'g': '其他',
            'h': '影视',
            'i': '诗词',
            'j': '网易云',
            'k': '哲学',
            'l': '抖音'
        };

        const typeName = typeNames[type] || '名言';

        this.container.innerHTML = `
            <div class="hitokoto-text">"${text}"</div>
            <div class="hitokoto-author">—— ${author} (${typeName})</div>
        `;
    }

    displayError() {
        this.container.innerHTML = `
            <div class="hitokoto-text">点击重新加载</div>
            <div class="hitokoto-author">—— 一言</div>
        `;
    }
}

// 初始化一言名言
document.addEventListener('DOMContentLoaded', () => {
    new HitokotoWidget();
});


