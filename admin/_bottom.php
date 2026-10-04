</main></div><?php csp_render_dynamic_styles(); ?><script nonce="<?=e(csp_nonce())?>">
document.querySelectorAll('form[data-confirm]').forEach(form=>{
  form.addEventListener('submit',event=>{
    if(!window.confirm(form.dataset.confirm||'Подтвердить действие?')) event.preventDefault();
  });
});
document.querySelectorAll('[data-auto-submit]').forEach(input=>{
  input.addEventListener('change',()=>input.form?.requestSubmit());
});

document.querySelectorAll('[data-editor-role-select]').forEach(select=>{
  const form=select.closest('form');
  const panel=form?.querySelector('[data-editor-permissions-panel]');
  if(!panel) return;

  const syncEditorPermissions=()=>{
    panel.hidden=select.value!=='editor';
  };

  select.addEventListener('change',syncEditorPermissions);
  syncEditorPermissions();
});

document.querySelectorAll('[data-user-access-toggle]').forEach(button=>{
  const id=button.getAttribute('aria-controls');
  const panel=id ? document.getElementById(id) : null;
  if(!panel) return;

  button.addEventListener('click',()=>{
    const willOpen=panel.hidden;
    panel.hidden=!willOpen;
    button.setAttribute('aria-expanded',willOpen?'true':'false');
    const label=button.querySelector('span');
    if(label) label.textContent=willOpen?'Скрыть':'Настроить';
    button.classList.toggle('is-open',willOpen);
  });
});

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

const richEditorFonts=[
  ['Manrope','Manrope'],
  ['Montserrat','Montserrat'],
  ['PT Serif','PT Serif'],
  ['Rubik','Rubik'],
  ['Noto Sans','Noto Sans'],
  ['Noto Serif','Noto Serif'],
  ['Georgia','Georgia']
];

function initRichEditor(textarea){
  if(!textarea || textarea.dataset.richReady==='1') return;
  textarea.dataset.richReady='1';

  const shell=document.createElement('div');
  shell.className='rich-editor-shell';
  const toolbar=document.createElement('div');
  toolbar.className='rich-editor-toolbar';
  const surface=document.createElement('div');
  surface.className='rich-editor-surface';
  surface.contentEditable='true';
  surface.dataset.placeholder=textarea.getAttribute('placeholder')||'Введите текст…';
  surface.setAttribute('role','textbox');
  surface.setAttribute('aria-multiline','true');

  const raw=textarea.value||'';
  if(/<\/?[a-z][\s\S]*>/i.test(raw)){
    surface.innerHTML=raw;
  }else if(raw.trim()!==''){
    surface.innerHTML=raw.split(/\n{2,}/).map(p=>'<p>'+p.replace(/\n/g,'<br>')+'</p>').join('');
  }

  let savedRange=null;
  const saveSelection=()=>{
    const sel=window.getSelection();
    if(sel&&sel.rangeCount&&surface.contains(sel.anchorNode)){
      savedRange=sel.getRangeAt(0).cloneRange();
    }
  };
  const restoreSelection=()=>{
    surface.focus();
    if(!savedRange) return;
    const sel=window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  };
  const sync=()=>{
    textarea.value=surface.innerHTML
      .replace(/<div><br><\/div>/g,'<p><br></p>')
      .trim();
  };
  const exec=(cmd,value=null)=>{
    restoreSelection();
    document.execCommand(cmd,false,value);
    saveSelection();
    sync();
  };
  const group=()=>{
    const g=document.createElement('div');
    g.className='rich-editor-group';
    toolbar.appendChild(g);
    return g;
  };
  const button=(parent,icon,title,cmd,value=null)=>{
    const b=document.createElement('button');
    b.type='button';
    b.className='rich-editor-btn';
    b.title=title;
    b.setAttribute('aria-label',title);
    b.innerHTML=icon;
    b.addEventListener('mousedown',e=>e.preventDefault());
    b.addEventListener('click',()=>exec(cmd,value));
    parent.appendChild(b);
    return b;
  };
  const select=(parent,cls,title,options,onChange)=>{
    const el=document.createElement('select');
    el.className=cls;
    el.title=title;
    options.forEach(([value,label])=>{
      const op=document.createElement('option');
      op.value=value;op.textContent=label;el.appendChild(op);
    });
    el.addEventListener('mousedown',saveSelection);
    el.addEventListener('change',()=>{
      restoreSelection();
      onChange(el.value);
      el.selectedIndex=0;
      saveSelection();
      sync();
    });
    parent.appendChild(el);
    return el;
  };

  const g1=group();
  button(g1,'<b>Ж</b>','Жирный','bold');
  button(g1,'<i>К</i>','Курсив','italic');
  button(g1,'<u>Ч</u>','Подчёркивание','underline');
  button(g1,'<s>З</s>','Зачёркивание','strikeThrough');

  const g2=group();
  select(g2,'rich-format-select','Абзац и заголовки',[
    ['','Абзац'],['p','Обычный текст'],['h2','Заголовок 2'],['h3','Заголовок 3'],['h4','Заголовок 4'],['blockquote','Цитата']
  ],value=>{ if(value) exec('formatBlock','<'+value+'>'); });

  select(g2,'rich-font-select','Шрифт',[
    ['','Шрифт'],...richEditorFonts
  ],value=>{ if(value) exec('fontName',value); });

  select(g2,'rich-size-select','Размер текста',[
    ['','Размер'],['2','Маленький'],['3','Обычный'],['4','Средний'],['5','Большой'],['6','Крупный']
  ],value=>{ if(value) exec('fontSize',value); });

  const g3=group();
  const colorWrap=document.createElement('label');
  colorWrap.className='rich-editor-color-wrap';
  colorWrap.title='Цвет текста';
  colorWrap.innerHTML='<i class="fa-solid fa-font"></i>';
  const color=document.createElement('input');
  color.type='color';color.className='rich-editor-color';color.value='#2f2924';
  color.addEventListener('mousedown',saveSelection);
  color.addEventListener('input',()=>exec('foreColor',color.value));
  colorWrap.appendChild(color);g3.appendChild(colorWrap);

  const bgWrap=document.createElement('label');
  bgWrap.className='rich-editor-color-wrap';
  bgWrap.title='Цвет фона текста';
  bgWrap.innerHTML='<i class="fa-solid fa-highlighter"></i>';
  const bg=document.createElement('input');
  bg.type='color';bg.className='rich-editor-color';bg.value='#f3e4cf';
  bg.addEventListener('mousedown',saveSelection);
  bg.addEventListener('input',()=>exec('hiliteColor',bg.value));
  bgWrap.appendChild(bg);g3.appendChild(bgWrap);

  const g4=group();
  button(g4,'<i class="fa-solid fa-align-left"></i>','По левому краю','justifyLeft');
  button(g4,'<i class="fa-solid fa-align-center"></i>','По центру','justifyCenter');
  button(g4,'<i class="fa-solid fa-align-right"></i>','По правому краю','justifyRight');
  button(g4,'<i class="fa-solid fa-align-justify"></i>','По ширине','justifyFull');

  const g5=group();
  button(g5,'<i class="fa-solid fa-list-ul"></i>','Маркированный список','insertUnorderedList');
  button(g5,'<i class="fa-solid fa-list-ol"></i>','Нумерованный список','insertOrderedList');
  const linkBtn=button(g5,'<i class="fa-solid fa-link"></i>','Добавить ссылку','noop');
  linkBtn.addEventListener('click',e=>{
    e.preventDefault();
    restoreSelection();
    const href=prompt('Введите ссылку:','https://');
    if(href&&href!=='https://') exec('createLink',href);
  },{capture:true});
  button(g5,'<i class="fa-solid fa-link-slash"></i>','Убрать ссылку','unlink');

  const g6=group();
  button(g6,'<i class="fa-solid fa-rotate-left"></i>','Отменить','undo');
  button(g6,'<i class="fa-solid fa-rotate-right"></i>','Повторить','redo');
  button(g6,'<i class="fa-solid fa-eraser"></i>','Очистить форматирование','removeFormat');

  const help=document.createElement('div');
  help.className='rich-editor-help';
  help.innerHTML='<span>Форматирование сохраняется на сайте</span><span>Кириллица поддерживается</span>';

  shell.append(toolbar,surface,help);

  // A contenteditable placed inside <label> makes the browser activate the
  // hidden source textarea after a click. That steals focus and removes the
  // caret from the visual editor. Replace only the direct label wrapper with
  // a neutral div while preserving its classes/attributes and exact layout.
  const labelHost=textarea.parentElement?.tagName==='LABEL' ? textarea.parentElement : null;
  if(labelHost){
    const neutralHost=document.createElement('div');
    [...labelHost.attributes].forEach(attr=>{
      if(attr.name!=='for') neutralHost.setAttribute(attr.name,attr.value);
    });
    while(labelHost.firstChild) neutralHost.appendChild(labelHost.firstChild);
    labelHost.replaceWith(neutralHost);
  }

  textarea.insertAdjacentElement('afterend',shell);

  ['keyup','mouseup','input','focus'].forEach(ev=>surface.addEventListener(ev,()=>{
    saveSelection();
    if(ev==='input') sync();
  }));
  surface.addEventListener('blur',sync);
  textarea.form?.addEventListener('submit',sync);
  sync();
}

document.querySelectorAll('textarea[data-rich-text]').forEach(initRichEditor);

</script></body></html>