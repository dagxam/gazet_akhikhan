document.querySelectorAll('img').forEach(function(img){
  if(!img.closest('.masthead-logo')) img.loading='lazy';
});

(function(){
  const button=document.querySelector('.menu-toggle');
  const menu=document.querySelector('.site-menu');
  if(!button||!menu) return;

  button.addEventListener('click',function(){
    const open=menu.classList.toggle('open');
    button.setAttribute('aria-expanded',open?'true':'false');
  });

  document.addEventListener('click',function(e){
    if(window.innerWidth>900) return;
    if(!menu.contains(e.target)&&!button.contains(e.target)){
      menu.classList.remove('open');
      button.setAttribute('aria-expanded','false');
    }
  });

  window.addEventListener('resize',function(){
    if(window.innerWidth>900){
      menu.classList.remove('open');
      button.setAttribute('aria-expanded','false');
    }
  });
})();

document.querySelectorAll('a[href="#top"]').forEach(function(link){
  link.addEventListener('click',function(e){
    e.preventDefault();
    window.scrollTo({top:0,behavior:'smooth'});
  });
});
