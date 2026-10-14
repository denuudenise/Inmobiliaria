document.addEventListener('DOMContentLoaded', () => {
  const byId = id => document.getElementById(id);
  const bindMenu = (buttonId, panelId, className) => {
    const button=byId(buttonId), panel=byId(panelId);
    button?.addEventListener('click',()=> {const open=panel.classList.toggle(className); button.setAttribute('aria-expanded',String(open));});
    return () => {panel?.classList.remove(className);button?.setAttribute('aria-expanded','false');};
  };
  const closeBuy=bindMenu('buyMenuButton','buyMenu','open');
  const closeAccount=bindMenu('accountButton','accountMenu','open');
  const closeMobile=bindMenu('mobileToggle','mainNav','mobile-open');
  const search=byId('headerSearch'), searchButton=byId('searchToggle');
  searchButton?.addEventListener('click',()=>{search.hidden=!search.hidden;searchButton.setAttribute('aria-expanded',String(!search.hidden));if(!search.hidden) byId('quickSearch').focus();});
  document.addEventListener('click',e=>{if(!e.target.closest('#buyNav'))closeBuy();if(!e.target.closest('#accountWrap'))closeAccount();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeBuy();closeAccount();closeMobile();if(search){search.hidden=true;searchButton.setAttribute('aria-expanded','false');}}});
  const toast=document.createElement('div'); toast.className='toast'; toast.setAttribute('role','status'); document.body.append(toast); let timer;
  const notify=message=>{toast.textContent=message;toast.classList.add('show');clearTimeout(timer);timer=setTimeout(()=>toast.classList.remove('show'),4500);};
  const buttons=[...document.querySelectorAll('[data-favorite]')];
  const setSaved=(button,saved)=>{button.classList.toggle('saved',saved);button.textContent=saved?'♥':'♡';button.setAttribute('aria-pressed',String(saved));button.setAttribute('aria-label',saved?'Quitar de favoritos':'Guardar en favoritos');};
  if(buttons.length && document.body.dataset.role==='Cliente') fetch('/Inmobiliaria/favorito.php').then(r=>r.json()).then(ids=>buttons.forEach(b=>setSaved(b,ids.includes(Number(b.dataset.favorite))))).catch(()=>notify('No se pudieron cargar tus favoritos. Recargá la página.'));
  buttons.forEach(button=>button.addEventListener('click',async()=>{
    if(document.body.dataset.role==='Invitado'){location.href='/Inmobiliaria/login.php?propiedad='+button.dataset.favorite;return;}
    if(document.body.dataset.role!=='Cliente'){notify('Los favoritos están disponibles en las cuentas de clientes.');return;}
    const saved=!button.classList.contains('saved');button.disabled=true;
    try {const r=await fetch('/Inmobiliaria/favorito.php',{method:'POST',body:new URLSearchParams({id:button.dataset.favorite,guardar:saved?'1':'0',csrf_token:document.body.dataset.csrf})}); if(!r.ok) throw new Error(); const result=await r.json();if(!result.ok)throw new Error();setSaved(button,saved);notify(saved?'Propiedad guardada en tus favoritos.':'Propiedad quitada de favoritos.');if(!saved && location.pathname.endsWith('/actividad.php'))button.closest('article')?.remove();}
    catch {notify('No se pudo guardar el cambio. Intentá nuevamente.');} finally{button.disabled=false;}
  }));
  document.querySelectorAll('[data-gallery]').forEach(button=>button.addEventListener('click',()=>{byId('galleryMain').src=button.dataset.gallery;document.querySelectorAll('[data-gallery]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));}));
  document.querySelectorAll('input[type="password"]').forEach(input=>{const button=document.createElement('button');button.type='button';button.className='password-toggle';button.textContent='Mostrar contraseña';button.setAttribute('aria-controls',input.id);button.setAttribute('aria-pressed','false');button.addEventListener('click',()=>{const show=input.type==='password';input.type=show?'text':'password';button.textContent=show?'Ocultar contraseña':'Mostrar contraseña';button.setAttribute('aria-pressed',String(show));});input.after(button);});
  document.querySelectorAll('[data-print]').forEach(b=>b.addEventListener('click',()=>window.print()));
  document.querySelectorAll('img').forEach(img=>img.addEventListener('error',()=>{if(!img.src.endsWith('/propiedad.svg'))img.src='/Inmobiliaria/recursos/imagenes/propiedad.svg';}));
  if(location.hash.startsWith('#solicitud-')){const target=byId(location.hash.slice(1));if(target)target.open=true;}
});
