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

