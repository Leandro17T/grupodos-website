(function(){
  function initCarousel(root){
    if(!root || root.__gdosInited) return;
    root.__gdosInited = true;

    var track = root.querySelector('[data-gdos-track]');
    var prev  = root.querySelector('.gdos-nav--prev');
    var next  = root.querySelector('.gdos-nav--next');
    if(!track) return;

    function cardWidth(){
      var card = track.querySelector('.gdos-card');
      return card ? (card.getBoundingClientRect().width + 16) : 280;
    }

    function scrollByDir(dir){
      track.scrollBy({left: dir * cardWidth(), behavior: 'smooth'});
    }

    if(prev) prev.addEventListener('click', function(){ scrollByDir(-1); });
    if(next) next.addEventListener('click', function(){ scrollByDir(1); });

    // Drag to scroll (opcional, UX pro)
    var isDown = false, startX = 0, scrollLeft = 0;
    track.addEventListener('mousedown', function(e){
      isDown = true; startX = e.pageX - track.offsetLeft; scrollLeft = track.scrollLeft;
      track.classList.add('is-dragging');
    });
    window.addEventListener('mouseup', function(){
      isDown = false; track.classList.remove('is-dragging');
    });
    track.addEventListener('mousemove', function(e){
      if(!isDown) return;
      e.preventDefault();
      var x = e.pageX - track.offsetLeft;
      var walk = (x - startX) * 1.1;
      track.scrollLeft = scrollLeft - walk;
    });

    // Touch inertia handled by browser; optional snap assist:
    track.addEventListener('keydown', function(e){
      if(e.key === 'ArrowRight') scrollByDir(1);
      if(e.key === 'ArrowLeft')  scrollByDir(-1);
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-gdos-carousel]').forEach(initCarousel);
  });
})();
