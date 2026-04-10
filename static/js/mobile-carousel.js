/**
 * 手机端轮播弹窗功能
 */

class MobileCarouselModal {
    constructor() {
        this.currentIndex = 0;
        this.items = [];
        this.init();
    }

    init() {
        // 等待DOM加载完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup(), { once: true });
        } else {
            this.setup();
        }
    }

    setup() {
        // 创建轮播弹窗HTML
        this.createModal();
        this.bindEvents();
        this.loadCarouselData();
    }

    createModal() {
        // 移除创建按钮的代码，改为使用danmaku.css中的图标

        // 创建轮播弹窗
        const modal = document.createElement('div');
        modal.className = 'carousel-modal';
        modal.id = 'carouselModal';
        modal.innerHTML = `
            <div class="carousel-modal-content">
                <div class="carousel-modal-header">
                    <h2 id="carouselModalTitle">未完待续</h2>
                    <button class="carousel-modal-close" onclick="mobileCarouselModal.closeModal()">&times;</button>
                </div>
                <div class="carousel-modal-body">
                    <p id="carouselModalContent">加载中...</p>
                </div>
                <div class="carousel-modal-footer">
                    <span id="carouselModalDate">2025.01.01</span>
                </div>
                <div class="carousel-modal-nav">
                    <button id="carouselPrevBtn" onclick="mobileCarouselModal.prevSlide()">← 上一个</button>
                    <span class="carousel-modal-counter">
                        <span id="carouselCurrentIndex">1</span> / <span id="carouselTotalCount">5</span>
                    </span>
                    <button id="carouselNextBtn" onclick="mobileCarouselModal.nextSlide()">下一个 →</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    bindEvents() {
        const modal = document.getElementById('carouselModal');
        const commentFormContainer = document.querySelector('.comment-form-container');

        // 为右侧图标添加点击事件（通过父元素委托）
        if (commentFormContainer) {
            commentFormContainer.addEventListener('click', (e) => {
                // 检查点击位置是否在伪元素区域（右侧）
                const rect = commentFormContainer.getBoundingClientRect();
                // 伪元素在右侧，计算点击位置
                const clickX = e.clientX - rect.left;
                const formWidth = rect.width;
                
                // 如果点击在右侧40px范围内，且不是点击在评论表单内部
                if (clickX > formWidth && !e.target.closest('.comment-form')) {
                    e.preventDefault();
                    this.openModal();
                }
            });
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });
        }

        // 键盘导航
        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;
            if (e.key === 'ArrowLeft') this.prevSlide();
            if (e.key === 'ArrowRight') this.nextSlide();
            if (e.key === 'Escape') this.closeModal();
        });
    }

    loadCarouselData() {
        try {
            const stored = localStorage.getItem('jayhome.carousel.data');
            if (stored) {
                const data = JSON.parse(stored);
                this.items = Object.entries(data).map(([title, item]) => ({
                    title,
                    ...item
                }));
            } else {
                // 使用默认数据
                this.items = [
                    {
                        title: '未完待续',
                        date: '2025.008a29',
                        content: '人生还在继续，故事还没完结。每一天都是新的开始，每一次失败都是成功的垫脚石。相信未来，相信自己。'
                    },
                    {
                        title: '从中铁工地跑路',
                        date: '2024.05',
                        content: '那段时光虽然辛苦，但让我学会了坚持和吃苦。离开不是逃避，而是为了寻找更适合自己的道路。感谢那段经历让我成长。'
                    },
                    {
                        title: '从宁夏大学毕业',
                        date: '2023.06',
                        content: '四年的青春岁月在银川度过，从青涩少年到成熟青年。感谢母校的培养，感谢朋友的陪伴。毕业不是结束，而是新的开始。'
                    },
                    {
                        title: '建设第一个网站',
                        date: '2022.04',
                        content: '从零开始学习编程，一行行代码构建起自己的网络世界。那时的我充满了对技术的热情和对未来的憧憬。这是我梦想的起点。'
                    },
                    {
                        title: '从娘胎出来',
                        date: '2000.09',
                        content: '生命的开始，一切的起源。感谢父母的养育，感谢这个世界给我的一切。每一个人的出生都是一个奇迹。'
                    }
                ];
            }

            this.updateCounter();
            this.renderSlide();
        } catch (error) {
            console.error('加载轮播数据失败:', error);
        }
    }

    openModal() {
        const modal = document.getElementById('carouselModal');
        if (modal) {
            modal.classList.add('active');
            this.renderSlide();
        }
    }

    closeModal() {
        const modal = document.getElementById('carouselModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    nextSlide() {
        this.currentIndex = (this.currentIndex + 1) % this.items.length;
        this.renderSlide();
    }

    prevSlide() {
        this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
        this.renderSlide();
    }

    renderSlide() {
        if (this.items.length === 0) return;

        const item = this.items[this.currentIndex];
        document.getElementById('carouselModalTitle').textContent = item.title;
        document.getElementById('carouselModalContent').textContent = item.content;
        document.getElementById('carouselModalDate').textContent = item.date;
        document.getElementById('carouselCurrentIndex').textContent = this.currentIndex + 1;

        // 更新按钮状态
        const prevBtn = document.getElementById('carouselPrevBtn');
        const nextBtn = document.getElementById('carouselNextBtn');
        
        if (prevBtn) prevBtn.disabled = this.items.length <= 1;
        if (nextBtn) nextBtn.disabled = this.items.length <= 1;
    }

    updateCounter() {
        document.getElementById('carouselTotalCount').textContent = this.items.length;
    }
}

// 初始化
let mobileCarouselModal;
document.addEventListener('DOMContentLoaded', () => {
    mobileCarouselModal = new MobileCarouselModal();
});

