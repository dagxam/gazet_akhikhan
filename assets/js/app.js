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
    document.body.classList.remove('mobile-menu-open');
    button.setAttribute('aria-expanded','false');
  };

  button.addEventListener('click',function(){
    const open=menu.classList.toggle('open');
    document.body.classList.toggle('mobile-menu-open',open && window.innerWidth<=900);
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
  const heroLocationCity=hero.querySelector('[data-hero-location-city]');
  const heroLocationRegion=hero.querySelector('[data-hero-location-region]');
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
      if(heroLocationCity) heroLocationCity.textContent=item.dataset.heroLocationCity||'Унцукульский район';
      if(heroLocationRegion) heroLocationRegion.textContent=item.dataset.heroLocationRegion||'Дагестан';

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
      if(document.documentElement.classList.contains('a11y-active')) return;
      if(window.matchMedia('(hover:hover) and (pointer:fine)').matches) selectItem(item);
    });
    item.addEventListener('focusin',function(){
      selectItem(item);
    });
  });
})();

(function(){
  const shell=document.querySelector('[data-menu-shell]');
  const menu=document.querySelector('#site-menu');
  const toggle=document.querySelector('[data-menu-overflow-toggle]');
  const panel=document.querySelector('[data-menu-overflow-panel]');
  if(!shell||!menu||!toggle||!panel) return;

  let raf=0;

  function closeOverflow(){
    shell.classList.remove('is-open');
    toggle.setAttribute('aria-expanded','false');
  }

  function restoreItems(){
    while(panel.firstChild){
      menu.appendChild(panel.firstChild);
    }
  }

  function fitMenu(){
    window.cancelAnimationFrame(raf);
    raf=window.requestAnimationFrame(function(){
      restoreItems();
      closeOverflow();
      toggle.hidden=true;

      if(window.innerWidth<=900) return;

      // First measure the full menu with no overflow button.
      if(menu.scrollWidth<=menu.clientWidth+1){
        return;
      }

      // The arrow itself takes space, so reveal it before moving items.
      toggle.hidden=false;

      let guard=0;
      while(menu.scrollWidth>menu.clientWidth+1 && menu.children.length>1 && guard<100){
        panel.insertBefore(menu.lastElementChild,panel.firstChild);
        guard++;
      }

      if(panel.children.length===0){
        toggle.hidden=true;
      }else{
        toggle.title='Дополнительные пункты: '+panel.children.length;
      }
    });
  }

  toggle.addEventListener('click',function(e){
    e.stopPropagation();
    const open=!shell.classList.contains('is-open');
    shell.classList.toggle('is-open',open);
    toggle.setAttribute('aria-expanded',open?'true':'false');
  });

  panel.addEventListener('click',function(e){
    if(e.target.closest('a')) closeOverflow();
  });

  document.addEventListener('click',function(e){
    if(window.innerWidth<=900) return;
    if(!shell.contains(e.target)) closeOverflow();
  });

  document.addEventListener('keydown',function(e){
    if(e.key==='Escape') closeOverflow();
  });

  window.addEventListener('resize',fitMenu);
  window.addEventListener('load',fitMenu);
  if(document.fonts&&document.fonts.ready){
    document.fonts.ready.then(fitMenu);
  }
  fitMenu();
})();


(function(){
  const box=document.querySelector('[data-site-weather]');
  const textNode=document.querySelector('[data-site-weather-text]');
  if(!box||!textNode) return;

  const weatherLabels={
    0:'Ясно',1:'Преим. ясно',2:'Переменная облачность',3:'Облачно',
    45:'Туман',48:'Туман',51:'Морось',53:'Морось',55:'Морось',
    61:'Дождь',63:'Дождь',65:'Сильный дождь',
    71:'Снег',73:'Снег',75:'Сильный снег',
    80:'Ливень',81:'Ливень',82:'Сильный ливень',
    95:'Гроза',96:'Гроза',99:'Гроза'
  };

  const url='https://api.open-meteo.com/v1/forecast?latitude=42.711488&longitude=46.786628&current=temperature_2m,weather_code&timezone=Europe%2FMoscow';

  fetch(url,{headers:{'Accept':'application/json'}})
    .then(response=>{
      if(!response.ok) throw new Error('weather');
      return response.json();
    })
    .then(data=>{
      const current=data&&data.current?data.current:null;
      if(!current||typeof current.temperature_2m!=='number') throw new Error('weather');
      const temperature=Math.round(current.temperature_2m);
      const condition=weatherLabels[current.weather_code]||'Погода';
      textNode.textContent=condition+' · '+(temperature>0?'+':'')+temperature+'°';
      box.title='Унцукуль · '+condition+', '+(temperature>0?'+':'')+temperature+'°C';
    })
    .catch(()=>{
      textNode.textContent='Погода · Унцукуль';
      box.title='Погода в Унцукуле';
    });
})();

(function(){
  const banner=document.querySelector('[data-privacy-notice]');
  const accept=document.querySelector('[data-privacy-accept]');
  if(!banner||!accept) return;

  const version=(banner.dataset.privacyVersion||'1').replace(/[^A-Za-z0-9._-]/g,'').slice(0,40)||'1';
  const key='akhikhan_privacy_notice_v'+version;

  function hasChoice(){
    try{
      return window.localStorage.getItem(key)==='accepted';
    }catch(e){
      return false;
    }
  }

  function rememberChoice(){
    try{
      window.localStorage.setItem(key,'accepted');
    }catch(e){}
  }

  function hideBanner(){
    banner.classList.remove('is-visible');
    window.setTimeout(function(){
      banner.hidden=true;
    },220);
  }

  if(!hasChoice()){
    banner.hidden=false;
    window.requestAnimationFrame(function(){
      window.requestAnimationFrame(function(){
        banner.classList.add('is-visible');
      });
    });
  }

  accept.addEventListener('click',function(){
    rememberChoice();
    hideBanner();
  });
})();

(function(){
  const account=document.querySelector('[data-topbar-admin-account]');
  const toggle=document.querySelector('[data-topbar-admin-toggle]');
  const menu=document.querySelector('[data-topbar-admin-menu]');
  if(!account||!toggle||!menu) return;

  function setOpen(open){
    account.classList.toggle('is-open',open);
    toggle.setAttribute('aria-expanded',open?'true':'false');
    menu.hidden=!open;
  }

  toggle.addEventListener('click',function(e){
    e.stopPropagation();
    setOpen(menu.hidden);
  });

  menu.addEventListener('click',function(e){
    e.stopPropagation();
  });

  document.addEventListener('click',function(){
    setOpen(false);
  });

  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
      setOpen(false);
      toggle.focus();
    }
  });
})();

(function(){
  const copyButtons=[...document.querySelectorAll('[data-copy-article-link]')];
  copyButtons.forEach(function(button){
    button.addEventListener('click',async function(){
      const url=button.dataset.copyArticleLink||window.location.href;
      try{
        await navigator.clipboard.writeText(url);
      }catch(e){
        const input=document.createElement('textarea');
        input.value=url;
        input.setAttribute('readonly','');
        input.style.position='fixed';
        input.style.opacity='0';
        document.body.appendChild(input);
        input.select();
        try{ document.execCommand('copy'); }catch(err){}
        input.remove();
      }
      button.classList.add('is-copied');
      const label=button.querySelector('span');
      const previous=label?label.textContent:'';
      if(label) label.textContent='Скопировано';
      window.setTimeout(function(){
        button.classList.remove('is-copied');
        if(label) label.textContent=previous||'Ссылка';
      },1600);
    });
  });
})();

(function(){
  const widget=document.querySelector('[data-article-reaction-widget]');
  if(!widget) return;

  const articleId=String(widget.dataset.articleId||'').replace(/[^0-9]/g,'');
  const endpoint=widget.dataset.endpoint||'';
  if(!articleId||!endpoint) return;

  const buttons=[...widget.querySelectorAll('[data-reaction]')];
  const storageKey='akhikhan_article_reaction_'+articleId;
  const tokenKey='akhikhan_reader_token_v1';

  function readLocal(key){
    try{return window.localStorage.getItem(key)||'';}catch(e){return '';}
  }
  function writeLocal(key,value){
    try{
      if(value) window.localStorage.setItem(key,value);
      else window.localStorage.removeItem(key);
    }catch(e){}
  }
  function createToken(){
    let token=readLocal(tokenKey);
    if(token.length>=16) return token;
    if(window.crypto&&typeof window.crypto.randomUUID==='function'){
      token=window.crypto.randomUUID().replace(/-/g,'')+Date.now().toString(36);
    }else{
      token=Date.now().toString(36)+Math.random().toString(36).slice(2)+Math.random().toString(36).slice(2);
    }
    writeLocal(tokenKey,token);
    return token;
  }
  function setActive(value){
    buttons.forEach(function(button){
      button.classList.toggle('is-active',button.dataset.reaction===value);
      button.setAttribute('aria-pressed',button.dataset.reaction===value?'true':'false');
    });
  }

  setActive(readLocal(storageKey));

  buttons.forEach(function(button){
    button.addEventListener('click',async function(){
      if(widget.classList.contains('is-loading')) return;

      const requested=button.dataset.reaction||'';
      const current=readLocal(storageKey);
      const next=current===requested?'none':requested;
      const body=new URLSearchParams();
      body.set('article_id',articleId);
      body.set('reaction',next);
      body.set('token',createToken());

      widget.classList.add('is-loading');
      buttons.forEach(btn=>btn.disabled=true);

      try{
        const response=await fetch(endpoint,{
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'},
          body:body.toString(),
          credentials:'same-origin'
        });
        const data=await response.json();
        if(!response.ok||!data.ok) throw new Error(data.error||'reaction');

        const likeCount=widget.querySelector('[data-reaction-count="like"]');
        const dislikeCount=widget.querySelector('[data-reaction-count="dislike"]');
        if(likeCount) likeCount.textContent=Number(data.likes||0).toLocaleString('ru-RU');
        if(dislikeCount) dislikeCount.textContent=Number(data.dislikes||0).toLocaleString('ru-RU');

        const active=next==='none'?'':next;
        writeLocal(storageKey,active);
        setActive(active);
      }catch(e){
        widget.classList.add('has-error');
        window.setTimeout(()=>widget.classList.remove('has-error'),1200);
      }finally{
        widget.classList.remove('is-loading');
        buttons.forEach(btn=>btn.disabled=false);
      }
    });
  });
})();

(function(){
  const panel=document.querySelector('[data-accessibility-panel]');
  const toggle=document.querySelector('[data-accessibility-toggle]');
  if(!panel||!toggle) return;

  const closeButton=panel.querySelector('[data-accessibility-close]');
  const resetButton=panel.querySelector('[data-accessibility-reset]');
  const standardButton=panel.querySelector('[data-accessibility-standard]');
  const status=panel.querySelector('[data-accessibility-status]');
  const summary=panel.querySelector('[data-accessibility-summary]');
  const stateLabel=toggle.querySelector('[data-accessibility-state-label]');
  const presetButtons=[...panel.querySelectorAll('[data-a11y-preset]')];
  const fontButtons=[...panel.querySelectorAll('[data-a11y-font]')];
  const contrastButtons=[...panel.querySelectorAll('[data-a11y-contrast]')];
  const spacingButton=panel.querySelector('[data-a11y-spacing]');
  const grayscaleButton=panel.querySelector('[data-a11y-grayscale]');
  const motionButton=panel.querySelector('[data-a11y-motion]');
  const root=document.documentElement;
  const storageKey='akhikhan_accessibility_v2';

  const defaults={
    active:false,
    font:['100','125','150','200'].includes(panel.dataset.defaultFont)?panel.dataset.defaultFont:'100',
    contrast:['normal','black-white','white-black','yellow-black'].includes(panel.dataset.defaultContrast)?panel.dataset.defaultContrast:'normal',
    spacing:panel.dataset.defaultSpacing==='wide'?'wide':'normal',
    grayscale:panel.dataset.defaultGrayscale==='true',
    motion:panel.dataset.defaultMotion==='reduce'?'reduce':'normal'
  };

  function readState(){
    try{
      const raw=window.localStorage.getItem(storageKey);
      if(!raw){
        const legacy=window.localStorage.getItem('akhikhan_accessibility_v1');
        if(legacy){
          const parsedLegacy=JSON.parse(legacy);
          return {
            active:parsedLegacy.active===true,
            font:['100','125','150','200'].includes(String(parsedLegacy.font))?String(parsedLegacy.font):defaults.font,
            contrast:['normal','black-white','white-black','yellow-black'].includes(parsedLegacy.contrast)?parsedLegacy.contrast:defaults.contrast,
            spacing:parsedLegacy.spacing==='wide'?'wide':'normal',
            grayscale:parsedLegacy.grayscale===true,
            motion:parsedLegacy.motion==='reduce'?'reduce':'normal'
          };
        }
        return {...defaults};
      }
      const parsed=JSON.parse(raw);
      return {
        active:parsed.active===true,
        font:['100','125','150','200'].includes(String(parsed.font))?String(parsed.font):defaults.font,
        contrast:['normal','black-white','white-black','yellow-black'].includes(parsed.contrast)?parsed.contrast:defaults.contrast,
        spacing:parsed.spacing==='wide'?'wide':'normal',
        grayscale:parsed.grayscale===true,
        motion:parsed.motion==='reduce'?'reduce':'normal'
      };
    }catch(e){
      return {...defaults};
    }
  }

  function saveState(nextState){
    try{window.localStorage.setItem(storageKey,JSON.stringify(nextState));}catch(e){}
  }

  let state=readState();

  function summaryText(){
    const bits=[];
    bits.push(state.font+'%');
    const contrastLabels={
      normal:'обычный контраст',
      'black-white':'чёрный / белый',
      'white-black':'белый / чёрный',
      'yellow-black':'жёлтый / чёрный'
    };
    bits.push(contrastLabels[state.contrast]||'обычный контраст');
    if(state.spacing==='wide') bits.push('увеличенные интервалы');
    if(state.grayscale) bits.push('ч/б изображения');
    if(state.motion==='reduce') bits.push('без анимации');
    return bits.join(' · ');
  }

  function applyState(announce){
    root.classList.toggle('a11y-active',state.active);
    root.dataset.a11yFont=state.font;
    root.dataset.a11yContrast=state.contrast;
    root.dataset.a11ySpacing=state.spacing;
    root.dataset.a11yGrayscale=state.grayscale?'true':'false';
    root.dataset.a11yMotion=state.motion;

    toggle.setAttribute('aria-pressed',state.active?'true':'false');
    toggle.classList.toggle('is-active',state.active);
    if(stateLabel) stateLabel.textContent=state.active?'Вкл.':'Выкл.';

    fontButtons.forEach(button=>{
      const active=button.dataset.a11yFont===state.font;
      button.classList.toggle('is-active',active);
      button.setAttribute('aria-pressed',active?'true':'false');
    });
    contrastButtons.forEach(button=>{
      const active=button.dataset.a11yContrast===state.contrast;
      button.classList.toggle('is-active',active);
      button.setAttribute('aria-pressed',active?'true':'false');
    });
    if(spacingButton) spacingButton.setAttribute('aria-pressed',state.spacing==='wide'?'true':'false');
    if(grayscaleButton) grayscaleButton.setAttribute('aria-pressed',state.grayscale?'true':'false');
    if(motionButton) motionButton.setAttribute('aria-pressed',state.motion==='reduce'?'true':'false');
    if(summary) summary.textContent=state.active?summaryText():'Обычная версия сайта';

    presetButtons.forEach(button=>button.classList.remove('is-active'));

    saveState(state);
    if(announce&&status){
      status.textContent=state.active?'Настройки применены.':'Обычная версия сайта включена.';
      window.setTimeout(()=>{status.textContent='';},1800);
    }
  }

  function applyPreset(name){
    state.active=true;
    if(name==='comfortable'){
      state.font='150';
      state.contrast='normal';
      state.spacing='wide';
      state.grayscale=false;
      state.motion='reduce';
    }else if(name==='contrast'){
      state.font='125';
      state.contrast='white-black';
      state.spacing='wide';
      state.grayscale=false;
      state.motion='reduce';
    }else if(name==='calm'){
      state.font='125';
      state.contrast='normal';
      state.spacing='normal';
      state.grayscale=true;
      state.motion='reduce';
    }
    applyState(true);
    presetButtons.forEach(button=>button.classList.toggle('is-active',button.dataset.a11yPreset===name));
  }

  function openPanel(){
    panel.hidden=false;
    toggle.setAttribute('aria-expanded','true');
    document.body.classList.add('accessibility-panel-open');
    window.requestAnimationFrame(()=>{
      const target=panel.querySelector('[data-a11y-preset], [data-a11y-font], [data-a11y-contrast], [data-a11y-spacing], [data-a11y-grayscale], [data-a11y-motion], button');
      if(target) target.focus();
    });
  }

  function closePanel(returnFocus){
    panel.hidden=true;
    toggle.setAttribute('aria-expanded','false');
    document.body.classList.remove('accessibility-panel-open');
    if(returnFocus) toggle.focus();
  }

  toggle.addEventListener('click',function(){
    if(panel.hidden){
      if(!state.active){
        state={...defaults,active:true};
        applyState(true);
      }
      openPanel();
    }else{
      closePanel(true);
    }
  });

  if(closeButton) closeButton.addEventListener('click',()=>closePanel(true));

  presetButtons.forEach(button=>{
    button.addEventListener('click',()=>applyPreset(button.dataset.a11yPreset||''));
  });

  fontButtons.forEach(button=>{
    button.addEventListener('click',()=>{
      state.active=true;
      state.font=button.dataset.a11yFont||defaults.font;
      applyState(true);
    });
  });

  contrastButtons.forEach(button=>{
    button.addEventListener('click',()=>{
      state.active=true;
      state.contrast=button.dataset.a11yContrast||defaults.contrast;
      applyState(true);
    });
  });

  if(spacingButton){
    spacingButton.addEventListener('click',()=>{
      state.active=true;
      state.spacing=state.spacing==='wide'?'normal':'wide';
      applyState(true);
    });
  }

  if(grayscaleButton){
    grayscaleButton.addEventListener('click',()=>{
      state.active=true;
      state.grayscale=!state.grayscale;
      applyState(true);
    });
  }

  if(motionButton){
    motionButton.addEventListener('click',()=>{
      state.active=true;
      state.motion=state.motion==='reduce'?'normal':'reduce';
      applyState(true);
    });
  }

  if(resetButton){
    resetButton.addEventListener('click',()=>{
      state={...defaults,active:true};
      applyState(true);
    });
  }

  if(standardButton){
    standardButton.addEventListener('click',()=>{
      state={...defaults,active:false};
      applyState(true);
      closePanel(true);
    });
  }

  panel.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
      e.preventDefault();
      closePanel(true);
    }
  });

  document.addEventListener('click',function(e){
    if(panel.hidden) return;
    if(panel.contains(e.target)||toggle.contains(e.target)) return;
    closePanel(false);
  });

  applyState(false);
})();;

