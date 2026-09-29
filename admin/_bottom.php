</main></div><script>
document.querySelector('.menu-toggle')?.addEventListener('click',()=>document.body.classList.toggle('menu-open'));

document.querySelectorAll('[data-category-toggle]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const box=document.querySelector('[data-category-create]');
    if(!box) return;
    const willOpen=box.hasAttribute('hidden');
    box.toggleAttribute('hidden',!willOpen);
    btn.textContent=willOpen?'− Скрыть создание рубрики':'＋ Новая рубрика';
    if(willOpen) box.querySelector('input')?.focus();
  });
});

const countChars=(input,output)=>{
  if(!input||!output) return;
  const update=()=>output.textContent=[...input.value].length;
  input.addEventListener('input',update);
  update();
};
countChars(document.querySelector('[data-title-input]'),document.querySelector('[data-title-count]'));
countChars(document.querySelector('[data-excerpt-input]'),document.querySelector('[data-excerpt-count]'));

const coverInput=document.querySelector('[data-cover-input]');
const coverPreview=document.querySelector('[data-cover-preview]');
const coverShell=document.querySelector('[data-cover-preview-shell]');
if(coverInput&&coverPreview&&coverShell){
  coverInput.addEventListener('change',()=>{
    const file=coverInput.files?.[0];
    if(!file) return;
    const url=URL.createObjectURL(file);
    coverPreview.src=url;
    coverPreview.hidden=false;
    coverShell.classList.remove('is-empty');
  });
}

const locationRegion=document.querySelector('[data-location-region]');
const locationCity=document.querySelector('[data-location-city]');
const locationPreviewRegion=document.querySelector('[data-location-preview-region]');
const locationPreviewCity=document.querySelector('[data-location-preview-city]');
if(locationRegion&&locationCity&&locationPreviewRegion&&locationPreviewCity){
  const syncLocationPreview=()=>{
    locationPreviewRegion.textContent=locationRegion.value.trim()||'Дагестан';
    locationPreviewCity.textContent=locationCity.value.trim()||'Унцукульский район';
  };
  locationRegion.addEventListener('input',syncLocationPreview);
  locationCity.addEventListener('input',syncLocationPreview);
  syncLocationPreview();
}
</script></body></html>