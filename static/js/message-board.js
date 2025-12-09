(function(){
  const API_URL = './api/comments.php';

  // 简单的工具
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  // 缓存相关常量 - 使用已有的常量名保持兼容性
  const IP_CACHE_KEY = 'jayhome.ip.v1';
  const LOCATION_CACHE_KEY = 'jayhome.location.v1';
  const CACHE_TTL_MS = 24 * 60 * 60 * 1000;
  const API_TIMEOUT = 2000; // API调用超时时间
  const MAX_RETRY_ATTEMPTS = 1; // 最大重试次数
  
  // 获取缓存的IP地址
  function getCachedIP(){
    try{
      const raw = localStorage.getItem(IP_CACHE_KEY);
      if(!raw) return null;
      const obj = JSON.parse(raw);
      if(!obj||!obj.ip||!obj.ts) return null;
      if(Date.now()-obj.ts>IP_CACHE_DURATION) return null;
      return obj.ip;
    }catch(e){
      console.log('Failed to get cached IP:', e);
      return null;
    }
  }
  
  // 缓存IP地址
  function cacheIP(ip){
    try{ 
      if(ip && ip.trim() !== ''){
        localStorage.setItem(IP_CACHE_KEY, JSON.stringify({ ip: ip, ts: Date.now() })); 
      }
    }catch(e){
      console.log('Failed to cache IP:', e);
    }
  }
  
  // 从缓存获取位置信息
  function getCachedLocation() {
    try {
      const cached = localStorage.getItem(LOCATION_CACHE_KEY);
      if (cached) {
        const { value, ts } = JSON.parse(cached);
        if (value && Date.now() - ts < LOCATION_CACHE_DURATION) {
          return value;
        }
      }
    } catch (e) {
      console.log('Failed to get cached location:', e);
    }
    return null;
  }
  
  // 重试函数 - 带指数退避
  async function retry(fn, maxAttempts = MAX_RETRY_ATTEMPTS) {
    let lastError;
    for (let attempt = 0; attempt <= maxAttempts; attempt++) {
      try {
        if (attempt > 0) {
          // 指数退避
          const delay = Math.min(1000 * Math.pow(2, attempt - 1), 5000);
          await new Promise(resolve => setTimeout(resolve, delay));
          console.log(`Retrying API call, attempt ${attempt}`);
        }
        return await fn();
      } catch (error) {
        lastError = error;
        console.log(`Attempt ${attempt} failed:`, error.message);
      }
    }
    throw lastError;
  }
  
  // 使用多个可靠的API获取真实IP地址，提高成功率
  async function getRealIP() {
    // 带超时的fetch包装器
    async function fetchWithTimeout(url, options = {}, timeout = API_TIMEOUT) {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);
      
      try {
        const response = await fetch(url, {
          ...options,
          signal: controller.signal
        });
        clearTimeout(timeoutId);
        return response;
      } catch (error) {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
          throw new Error('Request timeout');
        }
        throw error;
      }
    }
    
    const ipAPIs = [
      // 主要API - ip-api.com 提供IP和位置信息
      async () => {
        const response = await fetchWithTimeout('http://ip-api.com/json/?fields=query', {
          mode: 'cors',
          headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
          const data = await response.json();
          if (data.query) {
            return data.query;
          }
        }
        throw new Error('ip-api failed');
      },
      // 备用API 1 - ip9.com.cn 不限量支持
      async () => {
        const response = await fetchWithTimeout('https://www.ip9.com.cn/api/ip', {
          mode: 'cors',
          headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
          const data = await response.json();
          if (data.ip) {
            return data.ip;
          }
        }
        throw new Error('ip9 failed');
      },
      // 备用API 2 - 回退到ipify.org
      async () => {
        const response = await fetchWithTimeout('https://api.ipify.org?format=json', {
          headers: { 'Accept': 'application/json' },
          mode: 'cors'
        });
        if (response.ok) {
          const data = await response.json();
          if (data.ip) {
            return data.ip;
          }
        }
        throw new Error('ipify failed');
      },
      // 备用API 3 - 使用JSONP方式避免CORS问题
      async () => {
        return new Promise((resolve, reject) => {
          const callbackName = 'displayIP_' + Date.now();
          window[callbackName] = function(json) {
            delete window[callbackName];
            const script = document.getElementById(callbackName);
            if (script) script.remove();
            resolve(json.ip);
          };
          
          const script = document.createElement('script');
          script.id = callbackName;
          script.src = `https://api.ipify.org?format=jsonp&callback=${callbackName}`;
          script.onerror = () => {
            delete window[callbackName];
            if (script.parentNode) script.parentNode.removeChild(script);
            reject(new Error('jsonp failed'));
          };
          document.body.appendChild(script);
          
          // 添加超时处理
          setTimeout(() => {
            delete window[callbackName];
            if (script.parentNode) script.parentNode.removeChild(script);
            reject(new Error('jsonp timeout'));
          }, API_TIMEOUT);
        });
      }
    ];
    
    // 尝试所有API，直到成功或全部失败
    for (const api of ipAPIs) {
      try {
        const ip = await retry(api, MAX_RETRY_ATTEMPTS);
        if (ip && ip.trim() !== '') {
          return ip;
        }
      } catch (e) {
        console.log('IP API failed:', e.message);
        // 继续尝试下一个API
      }
    }
    
    // 如果所有API都失败，尝试从window对象获取（开发环境可能有模拟数据）
    if (window.mockIP) {
      return window.mockIP;
    }
    
    return null;
  }
  
  // 获取位置信息的函数（简化版）
  function getCachedLocation(){
    try{
      const raw = localStorage.getItem(LOCATION_CACHE_KEY);
      if(!raw) return null; 
      const obj = JSON.parse(raw);
      if(!obj||!obj.value||!obj.ts) return null; 
      if(Date.now()-obj.ts>CACHE_TTL_MS) return null; 
      return obj.value;
    }catch(_){return null}
  }
  
  async function getLocationInfo() {
    try {
      // 先尝试从缓存获取
      const cachedLocation = getCachedLocation();
      if (cachedLocation) {
        return cachedLocation;
      }
      
      // 先获取真实IP地址
      let ip = getCachedIP();
      if (!ip) {
        ip = await getRealIP();
        if (ip) {
          cacheIP(ip);
        }
      }
      
      // 如果没有获取到IP，使用默认值
      if (!ip) {
        return { city: '网络用户' };
      }
      
      // 带超时的fetch包装器
      async function fetchWithTimeout(url, options = {}, timeout = API_TIMEOUT + 500) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        try {
          const response = await fetch(url, {
            ...options,
            signal: controller.signal
          });
          clearTimeout(timeoutId);
          return response;
        } catch (error) {
          clearTimeout(timeoutId);
          if (error.name === 'AbortError') {
            throw new Error('Request timeout');
          }
          throw error;
        }
      }
      
      // 尝试获取地理位置信息的多个API
      const locationAPIs = [
        // 主要API - ip-api.com 提供详细的地理位置信息
        async () => {
          const response = await fetchWithTimeout(`http://ip-api.com/json/${encodeURIComponent(ip)}?lang=zh-CN`, {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
          });
          if (response.ok) {
            const data = await response.json();
            if (data.status === 'success') {
              let city = data.city || '';
              let regionName = data.regionName || '';
              let country = data.country || '';
              
              // 构建显示名称
              let displayName = '';
              if (country === '中国') {
                if (city) {
                  displayName = city;
                } else if (regionName) {
                  displayName = regionName;
                } else {
                  displayName = '中国';
                }
              } else {
                displayName = country;
              }
              
              return { 
                city: displayName || ip, 
                fullLocation: { country, regionName, city },
                ip: data.query
              };
            }
          }
          throw new Error('ip-api location failed');
        },
        // 备用API 1 - IP9.com.cn 不限量支持
        async () => {
          const response = await fetchWithTimeout(`https://www.ip9.com.cn/api/ip/location?ip=${encodeURIComponent(ip)}`, {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
          });
          if (response.ok) {
            const data = await response.json();
            if (data.status === 0) {
              let displayName = data.data.city || data.data.province || ip;
              return { city: displayName, ip: ip };
            }
          }
          throw new Error('ip9 location failed');
        },
        // 备用API 2 - apihz.cn 支持IPv4/IPv6
        async () => {
          const response = await fetchWithTimeout(`https://cn.apihz.cn/api/ip/chaapi.php?ip=${encodeURIComponent(ip)}`, {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
          });
          if (response.ok) {
            const data = await response.json();
            if (data.code === 1) {
              let displayName = data.data.city || data.data.province || ip;
              return { city: displayName, ip: ip };
            }
          }
          throw new Error('apihz location failed');
        },
        // 备用API 3 - 百度地图API
        async () => {
          const response = await fetchWithTimeout('https://qifu.baidu.com/ip/geo/v1/district', {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
          });
          if (response.ok) {
            const bd = await response.json();
            const data = bd && (bd.data || bd?.data?.location) || bd;
            const prov = data?.prov || data?.province || data?.ad_info?.province || '';
            const cty = data?.city || data?.ad_info?.city || '';
            
            if (prov || cty) {
              let displayName = cty || prov;
              return { city: displayName, ip: ip };
            }
          }
          throw new Error('baidu location failed');
        }
      ];
      
      // 尝试所有地理位置API
      for (const api of locationAPIs) {
        try {
          const locationInfo = await retry(api, MAX_RETRY_ATTEMPTS);
          if (locationInfo && locationInfo.city && locationInfo.city.trim() !== '') {
            // 缓存结果
            try {
              localStorage.setItem(LOCATION_CACHE_KEY, JSON.stringify({
                value: locationInfo,
                ts: Date.now()
              }));
            } catch (e) {
              console.log('Failed to cache location:', e);
            }
            return locationInfo;
          }
        } catch (e) {
          console.log('Location API failed:', e.message);
          // 继续尝试下一个API
        }
      }
      
      // 如果所有地理位置API都失败，返回IP地址
      return { city: ip, ip: ip };
    } catch (e) {
      console.error('Error in getLocationInfo:', e);
      // 异常情况下，尝试获取IP作为最后的备选
      try {
        const ip = await getRealIP();
        if (ip) {
          cacheIP(ip);
          return { city: ip, ip: ip };
        }
      } catch (_) {
        console.log('Failed to get fallback IP');
      }
      
      // 最后的兜底方案 - 显示默认位置
      return { city: '网络用户' };
    }
  }
  
  async function ensureLocation() {
    // 不使用缓存，每次都尝试获取最新的IP
    const info = await getLocationInfo(); 
    return info; 
  }

  // 头像：dicebear identicon
  function avatarUrl(seed){ return `https://api.dicebear.com/7.x/identicon/svg?seed=${encodeURIComponent(String(seed||Math.random()))}`; }

  // UI 构建
  function buildModal(){
    if($('#messageBoardModal')) return;
    const modal = document.createElement('div');
    modal.id='messageBoardModal'; modal.className='message-board-modal';
    modal.innerHTML = `
      <div class="mb-overlay"></div>
      <div class="mb-panel">
        <div class="mb-header">
          <div class="mb-title">留言板</div>
          <button class="mb-close" aria-label="关闭">×</button>
        </div>
        <div class="mb-body">
          <div class="mb-compose">
            <img class="mb-avatar" src="${avatarUrl('me')}" alt="avatar"/>
            <textarea class="mb-input" placeholder="写下你的留言..." maxlength="100"></textarea>
            <button class="mb-send">发送</button>
          </div>
          <div class="mb-list"></div>
        </div>
      </div>`;
    document.body.appendChild(modal);

    // 绑定事件
    $('.mb-close', modal).addEventListener('click', ()=> modal.classList.remove('active'));
    $('.mb-overlay', modal).addEventListener('click', ()=> modal.classList.remove('active'));
    $('.mb-send', modal).addEventListener('click', async ()=>{
      const ta = $('.mb-input', modal); const text = (ta.value||'').trim(); if(!text) return;
      const loc = await ensureLocation();
      try{
        const resp = await fetch(API_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ text, location: loc?.city||'网络用户' }), credentials:'same-origin' });
        const data = await resp.json(); if(!resp.ok||!data.success) throw new Error(data.message||'发送失败');
        ta.value=''; await loadThreads();
      }catch(e){ alert(e.message||'发送失败'); }
    });
  }

  function openModal(){ buildModal(); const modal = $('#messageBoardModal'); if(modal){ modal.classList.add('active'); loadThreads(); }}

  // 渲染线程
  async function loadThreads(){
    const list = $('.mb-list'); if(!list) return; list.innerHTML = '<div class="mb-loading">加载中...</div>';
    try{
      const resp = await fetch(`${API_URL}?action=threads&limit=50&_t=${Date.now()}`, { credentials:'same-origin' });
      const result = await resp.json(); if(!resp.ok||!result.success) throw new Error(result.message||'加载失败');
      const items = result.data||[];
      list.innerHTML = items.map(renderThread).join('');
      bindReplyEvents(list);
    }catch(e){ list.innerHTML = `<div class="mb-error">${e.message || '加载失败'}</div>`; }
  }

  function renderThread(item){
    const seed = item.id;
    const replies = item.replies || [];
    const repliesCount = replies.length;
    // 优化地点显示，确保即使location为空也显示默认信息
    const locationText = item.location && item.location.trim() !== '' ? 
                         item.location : '网络用户';
    const header = `
      <div class="mb-item">
        <div class="mb-item-left">
          <img class="mb-avatar" src="${avatarUrl(seed)}" alt="avatar"/>
          <div class="mb-line"></div>
        </div>
        <div class="mb-item-right">
          <div class="mb-meta">
            <div class="mb-meta-left">
              <span class="mb-loc">${escapeHtml(locationText)}</span>
            </div>
            <div class="mb-meta-right">
              <span class="mb-time">${formatTime(item.created_at)}</span>
              <button class="mb-reply-btn mb-reply-btn--meta" data-id="${item.id}" title="回复">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
              </button>
            </div>
          </div>
          <div class="mb-text">${escapeHtml(item.text||'')}</div>
          <div class="mb-actions">
            ${repliesCount > 0 ? `<button class="mb-replies-toggle" data-id="${item.id}" data-open="0">${repliesCount}条回复</button>` : ''}
          </div>
          <div class="mb-replies" data-for="${item.id}" hidden>
            ${replies.map(r=>renderReply(r)).join('')}
          </div>
          <div class="mb-reply-box" data-for="${item.id}" hidden>
            <div class="mb-reply-compose">
              <textarea class="mb-reply-input" placeholder="回复..." maxlength="100"></textarea>
              <button class="mb-reply-send" data-id="${item.id}">发送</button>
              <button class="mb-reply-cancel" data-id="${item.id}">取消</button>
            </div>
          </div>
        </div>
      </div>`;
    return header;
  }

  function renderReply(r){
    // 优化地点显示，确保即使location为空也显示默认信息
    const locationText = r.location && r.location.trim() !== '' ? 
                         r.location : '网络用户';
    return `
      <div class="mb-reply">
        <img class="mb-avatar" src="${avatarUrl(r.id)}" alt="avatar"/>
        <div class="mb-reply-body">
          <div class="mb-meta">
            <span class="mb-loc">${escapeHtml(locationText)}</span>
            <span class="mb-dot">·</span>
            <span class="mb-time">${formatTime(r.created_at)}</span>
          </div>
          <div class="mb-text">${escapeHtml(r.text||'')}</div>
        </div>
      </div>`;
  }

  function bindReplyEvents(root){
    $$(`.mb-reply-btn`, root).forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-id');
        const box = root.querySelector(`.mb-reply-box[data-for="${id}"]`);
        if(box){ 
          box.hidden = !box.hidden;
          if(!box.hidden) {
            box.querySelector('.mb-reply-input')?.focus();
          }
        }
      });
    });

    // 查看/收起 回复
    $$(`.mb-replies-toggle`, root).forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-id');
        const list = root.querySelector(`.mb-replies[data-for="${id}"]`);
        if(!list) return;
        const isOpen = btn.getAttribute('data-open') === '1';
        if(isOpen){
          list.hidden = true;
          btn.setAttribute('data-open','0');
          const count = list.children.length;
          btn.textContent = `${count}条回复`;
        } else {
          list.hidden = false;
          btn.setAttribute('data-open','1');
          btn.textContent = '收起回复';
        }
      });
    });

    // 取消回复
    $$(`.mb-reply-cancel`, root).forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-id');
        const box = root.querySelector(`.mb-reply-box[data-for="${id}"]`);
        if(box){ 
          box.hidden = true;
          const ta = box.querySelector('.mb-reply-input');
          if(ta) ta.value = '';
        }
      });
    });

    // 发送回复
    $$(`.mb-reply-send`, root).forEach(btn=>{
      btn.addEventListener('click', async()=>{
        const id = parseInt(btn.getAttribute('data-id'));
        const box = root.querySelector(`.mb-reply-box[data-for="${id}"]`);
        const ta = box?.querySelector('.mb-reply-input'); 
        const text = (ta?.value||'').trim(); 
        if(!text) return;
        const loc = await ensureLocation();
        try{
          const resp = await fetch(API_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ text, parent_id: id, location: loc?.city||'网络用户' }), credentials:'same-origin' });
          const data = await resp.json(); 
          if(!resp.ok||!data.success) throw new Error(data.message||'发送失败');
          ta.value=''; 
          box.hidden=true; 
          await loadThreads();
        }catch(e){ alert(e.message||'发送失败'); }
      });
    });
  }

  function escapeHtml(t){ const div = document.createElement('div'); div.textContent = t||''; return div.innerHTML; }
  function formatTime(ts){ try{ const d=new Date(ts); if(!isFinite(d)) return ''; const y=d.getFullYear(); const m=String(d.getMonth()+1).padStart(2,'0'); const da=String(d.getDate()).padStart(2,'0'); const hh=String(d.getHours()).padStart(2,'0'); const mm=String(d.getMinutes()).padStart(2,'0'); return `${y}-${m}-${da} ${hh}:${mm}`;}catch(_){return ''} }

  // 暴露打开方法
  window.openMessageBoard = openModal;

  // 按钮绑定
  document.addEventListener('DOMContentLoaded', ()=>{
    const card = document.getElementById('message-board-card');
    if(card){ card.addEventListener('click', openModal); }
    
    // 也支持通过图标打开
    const icon = document.getElementById('message-board-icon');
    if(icon){ icon.addEventListener('click', openModal); }
  });
})();
