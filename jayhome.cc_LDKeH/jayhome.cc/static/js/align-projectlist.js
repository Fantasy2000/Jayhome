(function(){
  function alignProjectList(){
    try{
      if (window.innerWidth < 900) return; // desktop only
      const carousel = document.querySelector('.timeline-carousel') || document.querySelector('.left-time');
      const proj = document.querySelector('.projectList');
      if (!carousel || !proj) return;
      // Align BOTTOMS: projectList bottom to carousel bottom (downward only, small cap)
      const carRect = carousel.getBoundingClientRect();
      const projRect = proj.getBoundingClientRect();
      const leftBottom = carRect.bottom;
      const projBottom = projRect.bottom;
      let delta = Math.round(leftBottom - projBottom); // >0 means project needs to move down
      if (delta < 0) delta = 0; // don't move up (avoid pulling content away)
      // Cap the additional shift to avoid overshoot
      const MAX_SHIFT = 80; // px
      delta = Math.min(delta, MAX_SHIFT);
      // Apply on top of stylesheet margin
      const cs = window.getComputedStyle(proj);
      const baseline = parseFloat(cs.marginTop || '0') || 0;
      const target = baseline + delta;
      proj.style.marginTop = target + 'px';
    }catch(e){ /* noop */ }
  }
  window.addEventListener('load', alignProjectList);
  window.addEventListener('resize', function(){
    const proj = document.querySelector('.projectList');
    if (proj) proj.style.marginTop = '';
    alignProjectList();
  });
})();

