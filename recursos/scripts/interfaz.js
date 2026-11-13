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
  document.querySelectorAll('[data-accumulate-files]').forEach(input=>{
    let selected=new DataTransfer();
    const list=byId('selected-files-'+input.id)||document.querySelector(`[data-selected-files="${input.id}"]`);
    const render=()=>{
      if(!list)return;
      list.replaceChildren();
      [...selected.files].forEach((file,index)=>{
        const item=document.createElement('li');
        const name=document.createElement('span'); name.textContent=file.name;
        const remove=document.createElement('button'); remove.type='button'; remove.textContent='Quitar'; remove.setAttribute('aria-label',`Quitar ${file.name}`);
        remove.addEventListener('click',()=>{const next=new DataTransfer();[...selected.files].forEach((f,i)=>{if(i!==index)next.items.add(f)});selected=next;input.files=selected.files;input.dispatchEvent(new Event('change',{bubbles:true}));});
        item.append(name,remove);list.append(item);
      });
      if(selected.files.length){const count=document.createElement('li');count.className='file-count';count.textContent=`${selected.files.length} archivo${selected.files.length===1?'':'s'} seleccionado${selected.files.length===1?'':'s'}`;list.append(count)}
    };
    input.addEventListener('change',()=>{
      const group=input.closest('.private-fields')||input.closest('.field');
      const others=group?[...group.querySelectorAll('[data-accumulate-files]')].filter(other=>other!==input).reduce((sum,other)=>sum+other.files.length,0):0;
      const limit=8;
      const maxForInput=Math.max(0,limit-others);
      const incoming=[...input.files];
      let rejected=0;
      incoming.forEach(file=>{const duplicate=[...selected.files].some(existing=>existing.name===file.name&&existing.size===file.size&&existing.lastModified===file.lastModified&&existing.type===file.type);if(duplicate)return;if(selected.files.length<maxForInput)selected.items.add(file);else rejected++});
      input.files=selected.files;render();
      if(list){const status=list.parentElement.querySelector('[data-file-limit-message]');if(status)status.remove();if(rejected){const note=document.createElement('small');note.dataset.fileLimitMessage='';note.textContent='El máximo es de 8 archivos.';list.after(note)}}
    });
    document.querySelector(`[data-add-files="${input.id}"]`)?.addEventListener('click',()=>input.click());
  });
  const missingDocs=[...document.querySelectorAll('[data-document-missing]')], documentNotes=document.querySelector('[data-document-notes]');
  const syncDocumentNotes=()=>{if(documentNotes){const required=missingDocs.some(box=>box.checked);documentNotes.required=required;documentNotes.minLength=required?10:0;}};
  missingDocs.forEach(box=>box.addEventListener('change',syncDocumentNotes));syncDocumentNotes();
  document.querySelectorAll('[data-print]').forEach(b=>b.addEventListener('click',()=>window.print()));
  document.querySelectorAll('img').forEach(img=>img.addEventListener('error',()=>{if(!img.src.endsWith('/propiedad.svg'))img.src='/Inmobiliaria/recursos/imagenes/propiedad.svg';}));
  if(location.hash.startsWith('#solicitud-')){const target=byId(location.hash.slice(1));if(target)target.open=true;}
});
