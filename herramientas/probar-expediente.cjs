const {chromium}=require(process.env.INMO_PLAYWRIGHT || 'C:/Users/camii/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');
const base=process.env.INMO_TEST_URL,id=process.env.INMO_TEST_REQUEST;
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});try{
 async function login(role){const context=await browser.newContext();const page=await context.newPage();await page.goto(base+'/login.php');await page.locator('[name=email]').fill(role+'@expediente.test');await page.locator('[name=password]').fill('Prueba!2026');await Promise.all([page.waitForURL(url=>!url.pathname.endsWith('login.php')),page.getByRole('button',{name:'Iniciar sesión',exact:true}).click()]);return page;}
 const client=await login('cliente');const ficha=base+'/ficha-privada.php?tipo=solicitud&id='+id;
 await client.goto(base+'/cliente/solicitud.php?id='+id);
 assert.equal(await client.locator('.offer-fields [type=checkbox]:checked').count(),3);
 assert.equal(await client.locator('[name="caracteristicas[]"]:checked').count(),3);
 await client.locator('[name="ofertas[2][activa]"]').uncheck();assert.equal(await client.locator('#offer-price-2').isDisabled(),true);await client.locator('[name="ofertas[2][activa]"]').check();
 await Promise.all([client.waitForNavigation(),client.getByRole('button',{name:'Guardar y enviar a revisión'}).click()]);
 assert.equal(await client.locator('.offer-fields [type=checkbox]:checked').count(),3);
 await client.goto(ficha);
 await client.locator('[name="planos[]"]').setInputFiles({name:'plano-prueba.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF')});
 await client.locator('[name=impuesto_concepto]').fill('ABL');await client.locator('[name=impuesto_base]').fill('125000.50');await client.locator('[name=impuesto_porcentaje]').fill('3.25');await client.locator('[name=impuesto_periodo]').fill('2026-10');
 assert.match(await client.locator('[data-tax-total]').textContent(),/4\.062,52/);
 await Promise.all([client.waitForNavigation(),client.getByRole('button',{name:'Guardar en la ficha'}).click()]);
 assert.match(await client.locator('body').textContent(),/guardados correctamente/);
 const doc=await client.locator('a[href*="documento.php?id="]').getAttribute('href');
 const documentUrl=new URL(doc,base).href;
 assert.equal((await client.request.get(documentUrl)).status(),200);
 const noCsrf=await client.request.post(ficha,{form:{impuesto_concepto:'No autorizado'}});assert.equal(noCsrf.status(),403);
 for(const role of ['otro','ajeno']){const page=await login(role);assert.equal((await page.goto(ficha)).status(),404);assert.equal((await page.request.get(documentUrl)).status(),404);}
 const agent=await login('agente');assert.equal((await agent.goto(ficha)).status(),200);assert.equal((await agent.request.get(documentUrl)).status(),200);
 const admin=await login('admin');assert.equal((await admin.goto(ficha)).status(),200);
 await admin.goto(base+'/administrador.php?seccion=solicitudes');const record=admin.locator('#solicitud-'+id);await record.evaluate(el=>el.open=true);assert.equal(await record.locator('a[href*="ficha-privada"]').count(),1);
 await Promise.all([admin.waitForNavigation(),record.getByRole('button',{name:'Aprobar y publicar'}).click()]);
 await client.goto(ficha);assert.equal(await client.locator('a[href*="documento.php"]').count(),1);
 await client.goto(base+'/cliente/solicitar-propiedad.php');await client.setViewportSize({width:390,height:844});
 assert.equal(await client.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
 for(const key of ['dormitorios','banos','superficie']){const label=client.locator('label[for='+key+']');assert.equal(await label.evaluate(el=>el.scrollWidth>el.clientWidth),false);}

 await client.locator('#tipo').selectOption({label:'Casa'});
 for(const [key,value] of Object.entries({titulo:'Alta con tres operaciones',direccion:'Prueba 456',localidad:'Prueba',descripcion:'Carga inicial completa con características y múltiples ofertas',superficie:'100',ambientes:'3',dormitorios:'2',banos:'1',antiguedad:'0'}))await client.locator('#'+key).fill(value);
 for(const [key,price,currency] of [[1,'120000','USD'],[2,'500000','ARS'],[3,'900','USD']]){await client.locator('[name="ofertas['+key+'][activa]"]').check();await client.locator('#offer-price-'+key).fill(price);await client.locator('#offer-currency-'+key).selectOption(currency);}
 await client.locator('[name="caracteristicas[]"][value="8"]').check();await client.locator('[name=declaracion]').check();
 await Promise.all([client.waitForURL('**/cliente/solicitudes.php'),client.getByRole('button',{name:'Enviar solicitud',exact:true}).click()]);
 assert.match(await client.locator('body').textContent(),/Alta con tres operaciones/);
 console.log('OK: alta inicial con tres precios, monedas independientes y aire acondicionado.');
 console.log('OK: subida PDF, cálculo visible, descarga autorizada, bloqueo de ajenos/CSRF, revisión previa, aprobación y móvil.');
}finally{await browser.close();}})().catch(error=>{console.error(error);process.exit(1)});
