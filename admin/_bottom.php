</main></div><script>
document.querySelector('.menu-toggle')?.addEventListener('click',()=>document.body.classList.toggle('menu-open'));
document.querySelectorAll('[data-category-toggle]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const box=document.querySelector('[data-category-create]');
    if(!box) return;
    const willOpen=box.hasAttribute('hidden');
    box.toggleAttribute('hidden',!willOpen);
    btn.textContent=willOpen?'− Скрыть создание рубрики':'＋ Создать новую рубрику';
    if(willOpen) box.querySelector('input')?.focus();
  });
});
</script></body></html>
