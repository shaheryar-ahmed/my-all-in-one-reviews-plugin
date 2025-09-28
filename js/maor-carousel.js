(function () {
  function setupCarousel(root) {
    // prevent double init
    if (root.dataset.carouselInit === '1') return;
    root.dataset.carouselInit = '1';

    var basePerView = parseInt(root.getAttribute('data-per-view'), 10) || 4;

    var viewport = root.querySelector('.maor-carousel-viewport');
    var track    = root.querySelector('.maor-carousel-track');
    var prevBtn  = root.querySelector('.maor-carousel-prev');
    var nextBtn  = root.querySelector('.maor-carousel-next');

    // slides can change if content is re-rendered; always re-query
    var getSlides = function(){ return Array.from(root.querySelectorAll('.maor-carousel-slide')); };

    var index = 0;
    var pv    = basePerView;

    function computePerView(w){
      if (w < 640)  return Math.min(basePerView, 1);
      if (w < 900)  return Math.min(basePerView, 2);
      if (w < 1200) return Math.min(basePerView, 3);
      return Math.min(basePerView, 4);
    }
    function maxIndex(){
      var s = getSlides();
      return Math.max(0, s.length - pv);
    }
    function setDisabled(){
      if (prevBtn) prevBtn.disabled = (index <= 0);
      if (nextBtn) nextBtn.disabled = (index >= maxIndex());
    }

    function update(){
      // If hidden (e.g. tab not visible yet), width is 0. Defer until visible.
      var w = viewport.clientWidth;
      if (!w) { setDisabled(); return; }

      pv = computePerView(w);

      var slides = getSlides();
      var slideWidth = w / pv;

      slides.forEach(function(slide){
        slide.style.flex = '0 0 ' + (100 / pv) + '%'; // percentage width
      });

      if (index > maxIndex()) index = maxIndex();
      track.style.transform = 'translateX(' + (-index * slideWidth) + 'px)';

      setDisabled();
    }

    function go(delta){
      index = Math.max(0, Math.min(index + delta, maxIndex()));
      update();
    }

    if (prevBtn) prevBtn.addEventListener('click', function(){ go(-1); });
    if (nextBtn) nextBtn.addEventListener('click', function(){ go(1); });

    // keep responsive
    window.addEventListener('resize', update);

    // observe size/visibility changes (works when tabs show/hide)
    if (window.ResizeObserver) {
      var ro = new ResizeObserver(update);
      ro.observe(viewport);
    }

    // re-measure after images load (prevents early 0-width calc)
    root.querySelectorAll('img').forEach(function(img){
      if (!img.complete) img.addEventListener('load', update, { once: true });
    });

    // expose a force-update for tab activations
    root._maorCarouselUpdate = update;

    update();
  }

  // Public re-init you can call after AJAX/tab render
  window.maorInitCarousels = function(){
    document.querySelectorAll('.maor-carousel').forEach(setupCarousel);
  };

  // Initial pass
  document.addEventListener('DOMContentLoaded', window.maorInitCarousels);

  // If your code dispatches this when content is replaced, we’ll re-init
  document.addEventListener('maor:content-updated', window.maorInitCarousels);

  // When user clicks a tab, nudge any visible carousels to re-measure next tick
  document.addEventListener('click', function(e){
    var tab = e.target.closest('.maor-tab');
    if (!tab) return;
    setTimeout(function(){
      document.querySelectorAll('.maor-carousel').forEach(function(root){
        if (root._maorCarouselUpdate) root._maorCarouselUpdate();
      });
    }, 0);
  });
})();
