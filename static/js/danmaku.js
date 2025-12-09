(() => {
    const LOCAL_STORAGE_KEY = 'jayhome.danmaku.cache';
const LOCATION_CACHE_KEY = 'jayhome.location.v1';
const LOCATION_TTL_MS = 24 * 60 * 60 * 1000; // 24h

    class DanmakuWall {
        constructor() {
        this.apiUrl = './api/comments.php';
        this.localMode = false; // 改为后端模式，写入数据库并从API拉取
        this.comments = [];
        this.allComments = [];
        this.queueIndex = 0;
        this.maxActive = 15; // 最多显示15条弹幕
        this.rows = 8;
        this.spawnInterval = null;
        this.refreshTimer = null;
        this.displayedComments = new Set(); // 记录已显示过的弹幕ID

        this.layer = null;
        this.form = document.getElementById('commentForm');
        this.input = document.getElementById('commentInput');
        this.statusEl = document.getElementById('commentStatus');
        this.submitBtn = this.form?.querySelector('.comment-submit-btn');

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init(), { once: true });
            } else {
                this.init();
            }
        }

        init() {
            if (this.layer) {
                return;
            }
            this.form = document.getElementById('commentForm');
            this.input = document.getElementById('commentInput');
            this.statusEl = document.getElementById('commentStatus');
            this.submitBtn = this.form?.querySelector('.comment-submit-btn');
            this.bellBtn = document.getElementById('commentBellBtn');
            this.commentSection = document.getElementById('commentSection');
            this.formContainer = document.querySelector('.comment-form-container');

            this.layer = document.createElement('div');
            this.layer.className = 'danmaku-stream';
            document.body.appendChild(this.layer);

            // 预取并缓存位置信息，提升首条弹幕体验
            this.prefetchLocation();

            // 清空本地缓存的自带评论
            try { localStorage.removeItem(LOCAL_STORAGE_KEY); } catch (_) {}

            this.bindEvents();
            this.bindBellButton();
            this.loadFromLocal();
            this.fetchComments();
            this.refreshTimer = setInterval(() => this.fetchComments(), 15000);
        }

        async prefetchLocation() {
            try {
                const cached = this.getCachedLocation();
                if (!cached) {
                    const info = await this.getLocationInfo();
                    this.cacheLocation(info);
                }
            } catch (e) {}
        }

        getCachedLocation() {
            try {
                const raw = localStorage.getItem(LOCATION_CACHE_KEY);
                if (!raw) return null;
                const obj = JSON.parse(raw);
                if (!obj || !obj.value || !obj.ts) return null;
                if (Date.now() - obj.ts > LOCATION_TTL_MS) return null;
                return obj.value;
            } catch (_) { return null; }
        }

        cacheLocation(info) {
            try {
                if (info && info.city && info.city !== '网络用户') {
                    localStorage.setItem(LOCATION_CACHE_KEY, JSON.stringify({ value: info, ts: Date.now() }));
                }
            } catch (_) {}
        }

        bindEvents() {
            if (this.form) {
                this.form.addEventListener('submit', event => {
                    event.preventDefault();
                    this.submitComment();
                });
            }
            // 添加Enter键发送功能（Shift+Enter换行）
            if (this.input) {
                this.input.addEventListener('keydown', event => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        this.submitComment();
                    }
                });
            }
        }

        bindBellButton() {
            if (!this.bellBtn) return;

            // 点击小铃铛按钮显示/隐藏输入框
            this.bellBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                // 打开当前轮播图对应的小弹窗
                try { if (window.openCurrentMoodModal) window.openCurrentMoodModal(); } catch (_) {}
            });

            // 点击输入框外关闭（仅手机端）
            if (this.commentSection && window.innerWidth <= 768) {
                document.addEventListener('click', (e) => {
                    if (!this.commentSection.contains(e.target) && e.target !== this.bellBtn) {
                        if (this.formContainer) {
                            this.formContainer.classList.remove('active');
                        }
                    }
                });
            }
        }



        setStatus(message, isError = false) {
            if (!this.statusEl) return;
            this.statusEl.textContent = message || '';
            this.statusEl.style.color = isError ? '#ff6b6b' : 'rgba(255, 255, 255, 0.9)';
        }

        showSuccessBubble() {
            // 创建气泡元素
            const bubble = document.createElement('div');
            bubble.className = 'comment-success-bubble';
            bubble.textContent = '发送成功';
            document.body.appendChild(bubble);
            
            // 触发动画（CSS动画会自动处理，从底部弹出，向上移动并淡出）
            requestAnimationFrame(() => {
                bubble.classList.add('show');
            });
            
            // 3秒后自动移除元素
            setTimeout(() => {
                bubble.remove();
            }, 3000);
        }

        toggleSubmitting(isSubmitting) {
            if (this.submitBtn) {
                this.submitBtn.disabled = isSubmitting;
            }
        }

        async submitComment() {
            const text = (this.input?.value || '').trim();
            if (!text) {
                this.setStatus('请输入评论内容', true);
                return;
            }

            this.toggleSubmitting(true);
            this.setStatus('发送中...');
            try {
                // 优先使用缓存位置，缺失再查询
                const cachedLoc = this.getCachedLocation();
                const locationInfo = cachedLoc || await this.getLocationInfo();
                if (!cachedLoc && locationInfo) this.cacheLocation(locationInfo);
                const location = locationInfo?.city || '网络用户';

                // 使用本地模式处理弹幕
                if (this.localMode) {
                    // 直接创建本地弹幕对象，包含位置信息
                    const payload = {
                        id: Date.now(),
                        text: text,
                        location: location,
                        created_at: new Date().toISOString(),
                        likes: 0
                    };
                    
                    this.comments.unshift(payload);
                    this.comments = this.comments.slice(0, 200);
                    this.persistLocal();
                    this.spawnDanmaku(payload);
                    
                    if (this.input) {
                        this.input.value = '';
                    }
                    // 显示气泡动画而不是底部文本
                    this.showSuccessBubble();
                    this.setStatus(''); // 清空状态文本
                } else {
                    // 尝试使用API（备用方案）
                    const resp = await fetch(this.apiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ text, location }),
                        credentials: 'same-origin'
                    });
                    const result = await resp.json();
                    if (!resp.ok || !result.success) {
                        throw new Error(result.message || '发送失败，请稍后重试');
                    }
                    const payload = result.data || { text, location, created_at: new Date().toISOString(), id: Date.now() };
                    this.comments.unshift(payload);
                    this.comments = this.comments.slice(0, 200);
                    // 更新最新5条ID集合用于上色
                    try { this.newestIds = new Set(this.comments.slice(0,5).map(c=>c.id).filter(Boolean)); } catch(_){}
                    this.persistLocal();
                    this.spawnDanmaku(payload);
                    if (this.input) {
                        this.input.value = '';
                    }
                    // 显示气泡动画而不是底部文本
                    this.showSuccessBubble();
                    this.setStatus(''); // 清空状态文本
                    // 立即从服务器刷新，确保其他终端可见
                    try { await this.fetchComments(); } catch (_) {}
                }
            } catch (error) {
                console.error('发送评论失败', error);
                this.setStatus(error.message || '发送失败，请稍后重试', true);
            } finally {
                this.toggleSubmitting(false);
            }
        }

        async getLocationInfo() {
            try {
                // 使用多个API源来获取位置信息，并尽量显示中文省市
                let city = '未知城市';
                let province = '';
                let country = '';

                // 英文->中文映射（常见省份/直辖市/特别行政区）
                const provinceMap = {
                    'Guangdong': '广东', 'Beijing': '北京', 'Shanghai': '上海', 'Tianjin': '天津', 'Chongqing': '重庆',
                    'Zhejiang': '浙江', 'Jiangsu': '江苏', 'Shandong': '山东', 'Sichuan': '四川', 'Hubei': '湖北',
                    'Hunan': '湖南', 'Henan': '河南', 'Fujian': '福建', 'Hebei': '河北', 'Liaoning': '辽宁',
                    'Anhui': '安徽', 'Shanxi': '山西', 'Shaanxi': '陕西', 'Jiangxi': '江西', 'Guangxi': '广西',
                    'Yunnan': '云南', 'Guizhou': '贵州', 'Inner Mongolia': '内蒙古', 'Gansu': '甘肃', 'Qinghai': '青海',
                    'Ningxia': '宁夏', 'Xinjiang': '新疆', 'Tibet': '西藏', 'Hainan': '海南', 'Jilin': '吉林',
                    'Heilongjiang': '黑龙江', 'Hong Kong': '香港', 'Macau': '澳门', 'Taiwan': '台湾'
                };
                const cityMap = {
                    'Guangzhou': '广州', 'Shenzhen': '深圳', 'Foshan': '佛山', 'Dongguan': '东莞', 'Zhuhai': '珠海',
                    'Beijing': '北京', 'Shanghai': '上海', 'Tianjin': '天津', 'Chongqing': '重庆',
                    'Hangzhou': '杭州', 'Ningbo': '宁波', 'Wenzhou': '温州',
                    'Nanjing': '南京', 'Suzhou': '苏州', 'Wuxi': '无锡',
                    'Jinan': '济南', 'Qingdao': '青岛',
                    'Chengdu': '成都', 'Wuhan': '武汉', 'Changsha': '长沙', 'Zhengzhou': '郑州',
                    'Fuzhou': '福州', 'Xiamen': '厦门', 'Shijiazhuang': '石家庄',
                    'Shenyang': '沈阳', 'Dalian': '大连', 'Hefei': '合肥',
                    'Taiyuan': '太原', 'Xi\'an': '西安', 'Nanchang': '南昌', 'Nanning': '南宁',
                    'Kunming': '昆明', 'Guiyang': '贵阳', 'Hohhot': '呼和浩特',
                    'Lanzhou': '兰州', 'Xining': '西宁', 'Yinchuan': '银川', 'Urumqi': '乌鲁木齐',
                    'Lhasa': '拉萨', 'Haikou': '海口', 'Changchun': '长春', 'Harbin': '哈尔滨',
                    'Hong Kong': '香港', 'Macau': '澳门', 'Taipei': '台北'
                };

                let ok = false;
                // 优先尝试百度企服IP定位（国内更稳定）
                try {
                    const resp = await Promise.race([
                        fetch('https://qifu.baidu.com/ip/geo/v1/district', { mode: 'cors' }),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 2500))
                    ]);
                    if (resp.ok) {
                        const bd = await resp.json();
                        const code = bd && (bd.code === 0 || bd.code === 'Success' || bd.msg === 'Success');
                        const data = bd && (bd.data || bd?.data?.location) || bd;
                        if (data && (code || data.city || data.prov || data.province)) {
                            const prov = data.prov || data.province || data.ad_info?.province || '';
                            const cty = data.city || data.ad_info?.city || '';
                            province = prov || province;
                            city = cty || city;
                            country = 'CN';
                            ok = true;
                        }
                    }
                } catch (_) {}

                try {
                    // 首选 ipapi.co
                    const response = await Promise.race([
                        fetch('https://ipapi.co/json/', { mode: 'cors' }),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 3000))
                    ]);
                    if (response.ok) {
                        const data = await response.json();
                        city = data.city || city;
                        province = data.region || '';
                        country = data.country || data.country_name || '';
                        ok = true;
                    }
                } catch (_) {}

                if (!ok) {
                    try {
                        // 备用 ipwho.is
                        const response = await Promise.race([
                            fetch('https://ipwho.is/', { mode: 'cors' }),
                            new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 3000))
                        ]);
                        if (response.ok) {
                            const data = await response.json();
                            city = data.city || city;
                            province = data.region || data.region_name || '';
                            country = data.country_code || data.country || '';
                            ok = true;
                        }
                    } catch (_) {}
                }

                if (!ok) {
                    try {
                        // 备用 ip.sb
                        const response = await Promise.race([
                            fetch('https://api.ip.sb/geoip', { mode: 'cors' }),
                            new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 3000))
                        ]);
                        if (response.ok) {
                            const data = await response.json();
                            city = data.city || city;
                            province = data.region || '';
                            country = data.country_code || data.country || '';
                            ok = true;
                        }
                    } catch (_) {}
                }

                // 如果是中国，尽量翻译为中文
                if (country === 'CN' || country === 'China' || provinceMap[province] || cityMap[city]) {
                    const zhProvince = provinceMap[province] || province;
                    const zhCity = cityMap[city] || city;
                    province = zhProvince;
                    city = zhCity;
                }

                // 组合显示“省份城市”或仅城市
                let location = city;
                if (province && province !== city) {
                    location = `${province}${city}`;
                }

                // 兜底
                if (!location || location === '未知城市') {
                    location = '网络用户';
                }

                return { city: location };
            } catch (error) {
                console.warn('获取位置信息异常', error);
                return { city: '网络用户' };
            }
        }

        async fetchComments() {
            try {
                // 如果是本地模式，直接从本地加载
                if (this.localMode) {
                    this.loadFromLocal();
                    // 如果没有本地数据，使用一些默认弹幕
                    if (!this.comments.length) {
                        this.loadDefaultComments();
                    }
                    this.startLoop();
                    return;
                }
                
                // 尝试从API获取（备用方案）
                const resp = await fetch(`${this.apiUrl}?_t=${Date.now()}`, { cache: 'no-store', credentials: 'same-origin' });
                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    throw new Error(data.message || '拉取失败');
                }
                if (Array.isArray(data.data)) {
                    // 按时间倒序（最新在前）
                    this.allComments = data.data.slice().sort((a,b)=> new Date(b.created_at) - new Date(a.created_at));
                    // 记录最新5条的ID用于上色
                    try {
                        this.newestIds = new Set(this.allComments.slice(0, 5).map(c => c.id));
                    } catch (_) { this.newestIds = null; }
                    // 始终包含最新的3条，再随机补足到10条
                    const newest = this.allComments.slice(0, Math.min(3, this.allComments.length));
                    const restPool = this.allComments.slice(newest.length);
                    const need = Math.max(0, 10 - newest.length);
                    const randomPicked = [];
                    for (let i = 0; i < need && restPool.length; i++) {
                        const idx = Math.floor(Math.random() * restPool.length);
                        randomPicked.push(restPool.splice(idx, 1)[0]);
                    }
                    this.comments = newest.concat(randomPicked);
                    this.queueIndex = 0;
                    this.persistLocal();
                    this.startLoop();
                }
            } catch (error) {
                console.warn('弹幕拉取失败，使用本地数据', error);
                if (!this.comments.length) {
                    this.loadFromLocal();
                    // 如果本地也没有数据，使用默认弹幕
                    if (!this.comments.length) {
                        this.loadDefaultComments();
                    }
                }
                this.startLoop();
            }
        }

        loadFromLocal() {
            try {
                const cached = localStorage.getItem(LOCAL_STORAGE_KEY);
                if (cached) {
                    const parsed = JSON.parse(cached);
                    if (Array.isArray(parsed) && parsed.length) {
                        this.comments = parsed;
                        this.queueIndex = 0;
                        this.startLoop();
                    }
                }
            } catch (error) {
                console.warn('读取本地弹幕失败', error);
            }
        }

        persistLocal() {
            try {
                localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(this.comments.slice(0, 200)));
            } catch (error) {
                console.warn('缓存弹幕失败', error);
            }
        }

        startLoop() {
            if (!this.comments.length) return;
            if (this.spawnInterval) {
                clearInterval(this.spawnInterval);
            }
            // 重置已显示的弹幕记录，允许新循环重新显示
            this.displayedComments.clear();
            this.spawnNext();
            this.spawnInterval = setInterval(() => this.spawnNext(), 1800);
        }

        spawnNext() {
            if (!this.comments.length) return;
            if (this.queueIndex >= this.comments.length) {
                this.queueIndex = 0;
            }
            const comment = this.comments[this.queueIndex++];
            this.spawnDanmaku(comment);
        }

        loadDefaultComments() {
            // 不加载默认弹幕，保持空白
            this.comments = [];
            this.persistLocal();
        }
        
        spawnDanmaku(comment) {
            const text = typeof comment === 'string' ? comment : comment?.text;
            const location = typeof comment === 'object' ? comment?.location : '';
            const commentId = typeof comment === 'object' ? comment?.id : null;
            
            if (!text || !this.layer) return;

            // 检查是否已显示过此弹幕
            if (commentId && this.displayedComments.has(commentId)) {
                return;
            }

            // 标记为已显示
            if (commentId) {
                this.displayedComments.add(commentId);
            }

            const node = document.createElement('div');
            node.className = 'danmaku-item';
            
            // 格式：内容 · 城市
            if (location) {
                node.textContent = `${text} · ${location}`;
            } else {
                node.textContent = text;
            }

            // 判断是否在最新5条中，决定颜色
            const isNewest = this.newestIds ? this.newestIds.has(commentId) : (this.comments.indexOf(comment) < 5);
            if (isNewest) {
                node.classList.add('danmaku-newest');
            }

            const topSpace = this.layer.clientHeight || window.innerHeight * 0.6;
            const laneHeight = topSpace / this.rows;
            const laneIndex = Math.floor(Math.random() * this.rows);
            node.style.top = `${laneIndex * laneHeight + 10}px`;

            const duration = 14 + Math.random() * 8;
            node.style.animationDuration = `${duration}s`;

            node.addEventListener('animationend', () => node.remove());

            this.layer.appendChild(node);
            this.trimOverflow();
        }

        trimOverflow() {
            if (!this.layer) return;
            const overflow = this.layer.children.length - this.maxActive;
            for (let i = 0; i < overflow; i++) {
                this.layer.removeChild(this.layer.firstElementChild);
            }
        }
    }

    new DanmakuWall();
})();


// 查看danmaku.js文件内容