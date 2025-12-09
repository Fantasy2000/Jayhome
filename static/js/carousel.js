/**
 * 轮播banner和心情笔记功能
 */

// 从数据库加载心情笔记数据
let moodNotes = {};
const DEFAULT_MOODS = {
    '未完待续': {
        date: '2025.008a29',
        content: '人生还在继续，故事还没完结。每一天都是新的开始，每一次失败都是成功的垫脚石。相信未来，相信自己。'
    },
    '从中铁工地跑路': {
        date: '2024.05',
        content: '那段时光虽然辛苦，但让我学会了坚持和吃苦。离开不是逃避，而是为了寻找更适合自己的道路。感谢那段经历让我成长。'
    },
    '从宁夏大学毕业': {
        date: '2023.06',
        content: '四年的青春岁月在银川度过，从青涩少年到成熟青年。感谢母校的培养，感谢朋友的陪伴。毕业不是结束，而是新的开始。'
    },
    '建设第一个网站': {
        date: '2022.04',
        content: '从零开始学习编程，一行行代码构建起自己的网络世界。那时的我充满了对技术的热情和对未来的憧憬。这是我梦想的起点。'
    },
    '从娘胎出来': {
        date: '2000.09',
        content: '生命的开始，一切的起源。感谢父母的养育，感谢这个世界给我的一切。每一个人的出生都是一个奇迹。'
    }
};

async function loadMoodNotes() {
    try {
        // 先尝试从数据库加载
        const response = await fetch('./api/banners.php', {
            credentials: 'same-origin'
        });
        const result = await response.json();
        
        if (result.success && Array.isArray(result.data) && result.data.length > 0) {
            // 从数据库数据转换为旧格式
            moodNotes = {};
            result.data.forEach(item => {
                moodNotes[item.title] = {
                    date: item.created_at ? item.created_at.split('T')[0] : '未知',
                    content: item.content,
                    id: item.id
                };
            });
        } else {
            // 如果数据库为空，使用默认数据
            moodNotes = { ...DEFAULT_MOODS };
        }
    } catch (error) {
        console.error('加载轮播数据失败:', error);
        // 加载失败时使用默认数据
        moodNotes = { ...DEFAULT_MOODS };
    }
}

class TimelineCarousel {
    constructor() {
        this.currentIndex = 0;
        this.autoPlayInterval = null;
        this.autoPlayDelay = 5000; // 5秒自动切换
        this.items = [];
        this.dots = [];
        this.init();
    }

    async init() {
        // 异步加载轮播数据
        await loadMoodNotes();
        
        this.track = document.getElementById('carouselTrack');
        this.dotsContainer = document.getElementById('carouselDots');

        if (!this.track || !this.dotsContainer) return;

        // 基于数据渲染轮播项
        this.buildItemsFromData();
        this.items = this.track.querySelectorAll('.carousel-item');
        if (!this.items.length) return;

        // 创建指示点
        this.createDots();

        // 绑定事件
        this.bindEvents();

        // 启动自动播放
        this.startAutoPlay();
    }

    buildItemsFromData() {
        // 将 localStorage 的数据渲染为轮播项
        const entries = Object.entries(moodNotes);
        if (!entries.length) return;
        // 确保顺序稳定（按日期或标题排序可选）
        // 这里按日期字符串倒序，若无则按标题
        entries.sort((a, b) => (b[1].date || b[0]).localeCompare(a[1].date || a[0]));
        this.track.innerHTML = entries.map(([title, info]) => `
            <div class="carousel-item" data-mood="${title}" data-date="${info.date || ''}">
                <div class="carousel-content">
                    <div class="carousel-title">${title}</div>
                    <div class="carousel-date">${info.date || ''}</div>
                </div>
            </div>
        `).join('');
    }

    createDots() {
        this.dotsContainer.innerHTML = '';
        this.items.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.className = `carousel-dot ${index === 0 ? 'active' : ''}`;
            dot.addEventListener('click', () => this.goToSlide(index));
            this.dotsContainer.appendChild(dot);
        });
        this.dots = document.querySelectorAll('.carousel-dot');
    }

    bindEvents() {
        this.items.forEach((item, index) => {
            item.addEventListener('click', () => {
                this.openMoodModal(index);
            });
        });

        // 鼠标悬停时暂停自动播放
        this.track.addEventListener('mouseenter', () => this.stopAutoPlay());
        this.track.addEventListener('mouseleave', () => this.startAutoPlay());
    }

    goToSlide(index) {
        this.currentIndex = index;
        this.updateCarousel();
        this.resetAutoPlay();
    }

    updateCarousel() {
        // 更新轨道位置
        const offset = -this.currentIndex * 100;
        this.track.style.transform = `translateX(${offset}%)`;

        // 更新指示点
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === this.currentIndex);
        });
    }

    nextSlide() {
        this.currentIndex = (this.currentIndex + 1) % this.items.length;
        this.updateCarousel();
    }

    startAutoPlay() {
        if (this.autoPlayInterval) return;
        this.autoPlayInterval = setInterval(() => this.nextSlide(), this.autoPlayDelay);
    }

    stopAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }

    resetAutoPlay() {
        this.stopAutoPlay();
        this.startAutoPlay();
    }

    openMoodModal(index) {
        if (!this.items || !this.items.length) return;
        this.currentIndex = (index + this.items.length) % this.items.length;
        const item = this.items[this.currentIndex];
        const mood = item.dataset.mood;
        const date = item.dataset.date;
        const noteData = moodNotes[mood];

        if (!noteData) return;

        const modal = document.getElementById('moodModal');
        document.getElementById('moodTitle').textContent = mood;
        document.getElementById('moodContent').textContent = noteData.content;
        document.getElementById('moodDate').textContent = date;

        modal.classList.add('active');
    }

    prevModal() {
        this.openMoodModal(this.currentIndex - 1);
    }

    nextModal() {
        this.openMoodModal(this.currentIndex + 1);
    }
}

// 关闭心情笔记弹窗
function closeMoodModal() {
    const modal = document.getElementById('moodModal');
    modal.classList.remove('active');
}

// 点击背景关闭弹窗
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('moodModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeMoodModal();
            }
        });
    }

    // 初始化轮播并暴露到全局，提供打开当前弹窗方法
    window.timelineCarousel = new TimelineCarousel();
    window.openCurrentMoodModal = function() {
        try {
            if (window.timelineCarousel) {
                window.timelineCarousel.openMoodModal(window.timelineCarousel.currentIndex || 0);
            }
        } catch (e) {}
    }
    window.prevMood = function(){ try{ window.timelineCarousel && window.timelineCarousel.prevModal(); }catch(e){} }
    window.nextMood = function(){ try{ window.timelineCarousel && window.timelineCarousel.nextModal(); }catch(e){} }
});


