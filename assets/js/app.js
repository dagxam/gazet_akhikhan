document.querySelectorAll('img').forEach(function(img){img.loading='lazy';});

(function(){
  const form=document.querySelector('.search');
  const btn=document.querySelector('.search-toggle');
  if(form&&btn){
    btn.addEventListener('click',function(){
      form.classList.toggle('open');
      const input=form.querySelector('input');
      if(form.classList.contains('open')&&input) input.focus();
    });
  }
})();

document.querySelectorAll('a[href="#top"]').forEach(function(link){
  link.addEventListener('click',function(e){
    e.preventDefault();
    window.scrollTo({top:0,behavior:'smooth'});
  });
});
