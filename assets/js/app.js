document.querySelectorAll('img').forEach(function(img){
  if(!img.closest('.masthead-logo')) img.loading='lazy';
});

(function(){
  const button=document.querySelector('.menu-toggle');
  const menu=document.querySelector('.site-menu');
  const nav=document.querySelector('.primary-nav');

  if(nav){
    const syncNav=function(){
      nav.classList.toggle('scrolled',window.scrollY>18);
    };
    syncNav();
    window.addEventListener('scroll',syncNav,{passive:true});
  }

  if(!button||!menu) return;

  const closeMenu=function(){
    menu.classList.remove('open');
    button.setAttribute('aria-expanded','false');
  };

  button.addEventListener('click',function(){
    const open=menu.classList.toggle('open');
    button.setAttribute('aria-expanded',open?'true':'false');
  });

  menu.querySelectorAll('a').forEach(function(link){
    link.addEventListener('click',function(){
      if(window.innerWidth<=900) closeMenu();
    });
  });

  document.addEventListener('click',function(e){
    if(window.innerWidth>900) return;
    if(!menu.contains(e.target)&&!button.contains(e.target)) closeMenu();
  });

  document.addEventListener('keydown',function(e){
    if(e.key==='Escape') closeMenu();
  });

  window.addEventListener('resize',function(){
    if(window.innerWidth>900) closeMenu();
  });
})();

document.querySelectorAll('a[href="#top"]').forEach(function(link){
  link.addEventListener('click',function(e){
    e.preventDefault();
    window.scrollTo({top:0,behavior:'smooth'});
  });
});


(function(){
  const hero=document.querySelector('[data-interactive-hero]');
  const items=Array.from(document.querySelectorAll('.latest-item'));
  if(!hero||!items.length) return;

  const title=hero.querySelector('[data-hero-title]');
  const excerpt=hero.querySelector('[data-hero-excerpt]');
  const kicker=hero.querySelector('[data-hero-kicker]');
  const link=hero.querySelector('[data-hero-link]');
  let current=null;
  let switchTimer=null;

  items.forEach(function(item){
    const cover=item.dataset.heroCover;
    if(cover){
      const preload=new Image();
      preload.src=cover;
    }
  });

  function selectItem(item){
    if(!item||current===item) return;
    current=item;

    items.forEach(function(other){
      other.classList.toggle('is-active',other===item);
    });

    hero.classList.add('is-switching');
    window.clearTimeout(switchTimer);

    switchTimer=window.setTimeout(function(){
      if(title) title.textContent=item.dataset.heroTitle||'';
      if(excerpt) excerpt.textContent=item.dataset.heroExcerpt||'';
      if(kicker) kicker.textContent=item.dataset.heroKicker||'Новости района';
      if(link) link.href=item.dataset.heroUrl||'#';

      const cover=item.dataset.heroCover||'';
      hero.classList.remove('hero-reference');

      if(cover){
        hero.classList.remove('hero-clean');
        hero.classList.add('hero-has-cover');
        hero.style.backgroundImage='linear-gradient(90deg,rgba(19,21,18,.88) 0%,rgba(19,21,18,.57) 45%,rgba(19,21,18,.14) 82%),url("'+cover.replace(/"/g,'%22')+'")';
      }else{
        hero.classList.remove('hero-has-cover');
        hero.classList.add('hero-clean');
        hero.style.backgroundImage='';
      }

      requestAnimationFrame(function(){
        hero.classList.remove('is-switching');
      });
    },110);
  }

  items.forEach(function(item){
    item.addEventListener('mouseenter',function(){
      if(window.matchMedia('(hover:hover) and (pointer:fine)').matches) selectItem(item);
    });
    item.addEventListener('focusin',function(){
      selectItem(item);
    });
  });
})();