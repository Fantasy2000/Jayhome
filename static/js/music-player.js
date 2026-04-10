/**
 * 在线音乐播放器
 * 使用Meting API获取网易云歌单
 */

const DEFAULT_PLAYLIST_ID = '2171200337';
let currentPlaylistId = DEFAULT_PLAYLIST_ID;

function getMetingApiUrl(playlistId) {
    return `https://api.injahow.cn/meting/?type=playlist&id=${playlistId}`;
}

let state = {
    isPlaying: false,
    isDraggingProgress: false,
    currentIndex: 0,
    playlist: [],
    isLoading: true
};

let audio = null;
let ui = {};

window.addEventListener('DOMContentLoaded', () => {
    buildPlayerShell();
    initAudio();
    fetchPlaylist();
});

async function fetchPlaylist() {
    const playlistId = window.siteSettings?.playlist_id || currentPlaylistId;
    const apiUrl = getMetingApiUrl(playlistId);
    
    try {
        state.isLoading = true;
        updateLoadingUI();
        
        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        
        if (Array.isArray(data) && data.length > 0) {
            state.playlist = data.map(track => ({
                id: track.id,
                name: track.name,
                artist: track.artist,
                url: track.url,
                cover: track.pic,
                lrc: track.lrc
            }));
        } else {
            throw new Error('Playlist is empty');
        }
        
        state.isLoading = false;
        updateLoadingUI();
        
        if (state.playlist.length > 0) {
            loadTrack(0);
        }
    } catch (error) {
        console.error('Failed to fetch playlist:', error);
        state.isLoading = false;
        updateLoadingUI();
        showPlayerTip('加载歌单失败');
    }
}

function updateLoadingUI() {
    if (!ui.root) return;
    
    if (state.isLoading) {
        ui.root.classList.add('is-loading');
        if (ui.trackTitleEl) ui.trackTitleEl.textContent = '加载中...';
        if (ui.trackArtistEl) ui.trackArtistEl.textContent = '请稍候';
    } else {
        ui.root.classList.remove('is-loading');
    }
}

function buildPlayerShell() {
    const root = document.getElementById('music-player');
    if (!root) return;

    root.innerHTML = `
        <div class="player-shell">
            <div class="player-body">
                <div class="player-cover" id="track-cover"></div>
                <div class="player-meta">
                    <div class="track-title" id="track-title">加载中...</div>
                    <div class="track-artist" id="track-artist">请稍候</div>
                </div>

                <div class="player-controls">
                    <button class="control-btn prev-btn" id="prev-button" aria-label="上一首" title="上一首">${createIcon('prev')}</button>
                    <button class="control-btn play-btn" id="play-button" aria-label="播放/暂停" title="播放/暂停">${createIcon('play')}</button>
                    <button class="control-btn next-btn" id="next-button" aria-label="下一首" title="下一首">${createIcon('next')}</button>
                </div>
            </div>
            <div class="progress-row">
                <input type="range" class="progress-bar" min="0" max="100" value="0" step="1">
                <div class="timecodes">
                    <span class="current-time">00:00</span>
                    <span class="duration">00:00</span>
                </div>
            </div>
        </div>
    `;

    ui = {
        root,
        playBtn: root.querySelector('.play-btn'),
        prevBtn: root.querySelector('.prev-btn'),
        nextBtn: root.querySelector('.next-btn'),
        progressBar: root.querySelector('.progress-bar'),
        currentTimeEl: root.querySelector('.current-time'),
        durationEl: root.querySelector('.duration'),
        trackTitleEl: root.querySelector('#track-title'),
        trackArtistEl: root.querySelector('#track-artist'),
        trackCoverEl: root.querySelector('#track-cover')
    };

    ui.playBtn.addEventListener('click', togglePlay);
    ui.prevBtn.addEventListener('click', prevTrack);
    ui.nextBtn.addEventListener('click', nextTrack);

    ui.progressBar.addEventListener('input', handleProgressInput);
    ui.progressBar.addEventListener('change', handleProgressCommit);
    ui.progressBar.addEventListener('mousedown', () => state.isDraggingProgress = true);
    ui.progressBar.addEventListener('touchstart', () => state.isDraggingProgress = true);
    document.addEventListener('mouseup', () => state.isDraggingProgress = false);
    document.addEventListener('touchend', () => state.isDraggingProgress = false);
}

function createIcon(type) {
    switch (type) {
        case 'play':
            return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7 4 20 12 7 20"></polygon></svg>`;
        case 'pause':
            return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="4" x2="8" y2="20"></line><line x1="16" y1="4" x2="16" y2="20"></line></svg>`;
        case 'prev':
            return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="19 20 9 12 19 4"></polygon><line x1="5" y1="4" x2="5" y2="20"></line></svg>`;
        case 'next':
            return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 4 15 12 5 20"></polygon><line x1="19" y1="4" x2="19" y2="20"></line></svg>`;
        default:
            return '';
    }
}

function initAudio() {
    audio = new Audio();
    audio.preload = 'auto';

    audio.addEventListener('timeupdate', updateProgress);
    audio.addEventListener('loadedmetadata', () => {
        ui.durationEl.textContent = formatTime(audio.duration);
    });
    audio.addEventListener('ended', () => {
        nextTrack();
    });
    audio.addEventListener('play', () => setPlayingState(true));
    audio.addEventListener('pause', () => setPlayingState(false));
    audio.addEventListener('error', handleAudioError);
}

function loadTrack(index) {
    if (state.isLoading || state.playlist.length === 0) return;
    
    if (typeof index === 'number') {
        state.currentIndex = (index + state.playlist.length) % state.playlist.length;
    }
    const track = state.playlist[state.currentIndex];
    if (!track) return;

    if (audio) {
        audio.src = track.url;
        audio.load();
    }
    if (ui.trackTitleEl) ui.trackTitleEl.textContent = track.name || '';
    if (ui.trackArtistEl) ui.trackArtistEl.textContent = track.artist || '';
    if (ui.trackCoverEl && track.cover) ui.trackCoverEl.style.backgroundImage = `url(${track.cover})`;

    if (ui.progressBar) ui.progressBar.value = 0;
    if (ui.currentTimeEl) ui.currentTimeEl.textContent = '00:00';
    if (ui.durationEl) ui.durationEl.textContent = '00:00';
}

function togglePlay() {
    if (!audio || state.isLoading || state.playlist.length === 0) return;
    if (audio.paused) {
        audio.play().catch(err => {
            console.error('播放失败:', err);
            showPlayerTip('播放失败，请重试');
        });
    } else {
        audio.pause();
    }
}

function setPlayingState(isPlaying) {
    state.isPlaying = isPlaying;
    if (ui.playBtn) ui.playBtn.innerHTML = isPlaying ? createIcon('pause') : createIcon('play');
    if (ui.root) ui.root.classList.toggle('is-playing', isPlaying);
}

function nextTrack() {
    if (state.isLoading || state.playlist.length === 0) return;
    loadTrack(state.currentIndex + 1);
    if (state.isPlaying) {
        audio.play().catch(()=>{});
    }
}

function prevTrack() {
    if (state.isLoading || state.playlist.length === 0) return;
    loadTrack(state.currentIndex - 1);
    if (state.isPlaying) {
        audio.play().catch(()=>{});
    }
}



function updateProgress() {
    if (!audio || !audio.duration || state.isDraggingProgress) return;
    const progress = (audio.currentTime / audio.duration) * 100;
    ui.progressBar.value = progress || 0;
    ui.currentTimeEl.textContent = formatTime(audio.currentTime);
    ui.durationEl.textContent = formatTime(audio.duration);
}

function handleProgressInput(event) {
    const value = Number(event.target.value);
    const duration = audio?.duration || 0;
    ui.currentTimeEl.textContent = formatTime((value / 100) * duration);
}

function handleProgressCommit(event) {
    const value = Number(event.target.value);
    const duration = audio?.duration || 0;
    if (!duration || !audio) return;
    const newTime = (value / 100) * duration;
    audio.currentTime = newTime;
    state.isDraggingProgress = false;
}

function handleAudioError(event) {
    console.error('音频播放错误:', event);
    showPlayerTip('音频加载失败');
}

function showPlayerTip(text) {
    if (!ui.root) return;
    const tip = document.createElement('div');
    tip.className = 'player-tip';
    tip.textContent = text;
    ui.root.appendChild(tip);
    setTimeout(() => tip.remove(), 2500);
}

function formatTime(seconds) {
    if (!Number.isFinite(seconds)) return '00:00';
    const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
    const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
    return `${mins}:${secs}`;
}

// 对外暴露便于调试
window.musicPlayer = {
    play: () => audio?.play(),
    pause: () => audio?.pause(),
    toggle: togglePlay,
    reloadPlaylist: () => fetchPlaylist()
};
