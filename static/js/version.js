// 版本控制 - 强制刷新页面以加载最新UI
(function(){
  var APP_VERSION = '20260409';
  try {
    var last = localStorage.getItem('jayhome.appVersion');
    if (last !== APP_VERSION) {
      localStorage.setItem('jayhome.appVersion', APP_VERSION);
      // 强制刷新页面以加载最新UI
      window.location.reload(true);
    }
  } catch (e) {}
})();
