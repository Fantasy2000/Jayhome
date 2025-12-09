// 一次性强制刷新 + 资源版本同步（仅在版本变更的首次访问触发）
(function(){
  var APP_VERSION = '20251209-01';
  try {
    var last = localStorage.getItem('jayhome.appVersion');
    var refreshedFor = localStorage.getItem('jayhome.refreshedFor');
    if (last !== APP_VERSION) {
      localStorage.setItem('jayhome.appVersion', APP_VERSION);
      if (refreshedFor !== APP_VERSION) {
        // 将静态资源链接的 ?v= 参数统一更新为 APP_VERSION
        var updateUrlVersion = function(u){
          try {
            var url = new URL(u, location.origin);
            url.searchParams.set('v', APP_VERSION);
            return url.pathname + (url.search ? url.search : '');
          } catch (e) {
            if (/([?&])v=/.test(u)) {
              return u.replace(/([?&])v=[^&]*/,'$1v='+APP_VERSION);
            }
            return u + (u.indexOf('?')>-1?'&':'?') + 'v=' + APP_VERSION;
          }
        };
        var nodes = document.querySelectorAll('link[rel="stylesheet"][href], script[src]');
        nodes.forEach(function(el){
          var attr = el.tagName === 'LINK' ? 'href' : 'src';
          var val = el.getAttribute(attr);
          if (!val) return;
          // 仅处理本地静态资源
          if (!/^(?:https?:)?\/\//i.test(val) && /\/static\//.test(val)) {
            el.setAttribute(attr, updateUrlVersion(val));
          }
        });
        // 标记本版本已刷新，并强制刷新一次页面
        localStorage.setItem('jayhome.refreshedFor', APP_VERSION);
        location.reload();
      }
    }
  } catch (e) {}
})();
