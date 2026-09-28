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
