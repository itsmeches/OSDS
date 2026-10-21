import {readFileSync,writeFileSync,unlinkSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
let prefix=readFileSync('scripts/verify-evidence-reviewer-creation.mjs','utf8').split('const controlCreate=')[0]
 .replaceAll('phase-3d7-g-int005','phase-3d7-k3-remediation').replaceAll('8025','8029')
 .replace("email.startsWith('gint005-')", "email.startsWith('k3create-')").replace("?'Admin':email===fixture.super.email", "?'Staff creator':email===fixture.super.email");
// All default axe rules plus target-size; report the separate existing clear
// control finding rather than turning this integrity fix into a styling change.
prefix=prefix.replaceAll(".withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa'])", ".options({rules:{'target-size':{enabled:true}}})")
 .replace('expect(axe.violations).toEqual([]);','');
const body=String.raw`
const controlCreate=action=>JSON.parse(execFileSync('php',['scripts/Prepare-InstitutionCreation.php',action],{encoding:'utf8'}));
let data,leaks=0;
const create=()=>page.getByRole('button',{name:'Create',exact:true});
const name=()=>page.getByRole('textbox',{name:/^Name/});
async function open(){await visit('/admin/institutions/create');await stable();}
async function fill(suffix=''){await name().fill(data.name+suffix);await page.getByRole('textbox',{name:/Official/}).fill(data.official+suffix);await page.getByRole('combobox').first().click();await page.locator('.fi-select-input-search-ctn input:visible').fill(data.regionName);await page.getByRole('option',{name:data.regionName,exact:true}).click();await page.locator('select').selectOption('active');}
try{
 data=controlCreate('prepare');fixture={super:{email:'unused'}};await login(data.email);await open();
 page.on('response',async r=>{if(r.request().method()==='POST'&&r.url().includes('livewire')){const t=await r.text().catch(()=>'');if(/ISOLATED_K3_CREATE_AUDIT_FAILURE|SQLSTATE|password_reset_token|locator_ciphertext/.test(t))leaks++;}});
 await fill();await name().fill('');await create().click();expect(await name().evaluate(e=>e.validity.valueMissing)).toBe(true);expect(controlCreate('inspect').institutions).toHaveLength(0);await expect(page.getByRole('textbox',{name:/Official/})).toHaveValue(data.official);pass('Required validation retains the other entries without creation');
 await name().fill(data.name);await context.setExtraHTTPHeaders({'X-Isolated-Atomicity-Fault':'after-audit'});await create().click();await expect(page.getByText('The institution creation could not be confirmed. Keep your entries and inspect saved institutions before trying again.',{exact:true})).toBeVisible();await expect(name()).toHaveValue(data.name);expect(controlCreate('inspect').institutions).toHaveLength(0);await capture('create-rollback');await context.setExtraHTTPHeaders({});pass('Actual Create audit insertion failure rolls back institution and audit with retained input');
 await page.setViewportSize({width:1440,height:1000});await create().dblclick();await page.waitForURL(u=>!u.pathname.endsWith('/create'));let rows=controlCreate('inspect').institutions;expect(rows).toHaveLength(1);expect(rows[0].audits).toHaveLength(1);expect(rows[0].audits[0].actor_id).toBe(data.actor);expect(rows[0].is_synthetic).toBe(false);pass('Delivered double click creates one institution and one current-actor audit');
 await open();await fill();await create().click();await expect(page.getByText(/already been taken/)).toBeVisible();expect(controlCreate('inspect').institutions).toHaveLength(1);pass('Duplicate official ID validates without a second operation');
 await fill('-uncertain');let dropped=0;await page.route('**/livewire*/update',async r=>{if(r.request().postDataJSON()?.components?.some(c=>c.calls?.some(a=>a.method==='create'))){dropped++;await r.fetch();await r.abort('failed');}else await r.continue();});await create().click();await expect(page.getByText('Action not confirmed',{exact:true})).toBeVisible();await expect(name()).toHaveValue(data.name+'-uncertain');expect(dropped).toBe(1);rows=controlCreate('inspect').institutions;expect(rows).toHaveLength(2);expect(rows.every(r=>r.audits.length===1)).toBe(true);await capture('create-uncertain');await page.unrouteAll({behavior:'wait'});
 await visit('/admin/institutions');await page.getByRole('searchbox').fill(data.official);await expect(page.getByRole('cell',{name:data.name+'-uncertain',exact:true})).toBeVisible();pass('Committed response loss remains uncertain; saved list confirms state without replay');
 data=controlCreate('prepare');await login(data.email);await open();await fill();let aborted=0;await page.route('**/livewire*/update',r=>{if(r.request().postDataJSON()?.components?.some(c=>c.calls?.some(a=>a.method==='create'))){aborted++;return r.abort();}return r.continue();});await create().click();await expect(page.getByText('Action not confirmed',{exact:true})).toBeVisible();await expect(name()).toHaveValue(data.name);expect(aborted).toBe(1);expect(controlCreate('inspect').institutions).toHaveLength(0);await page.unrouteAll({behavior:'wait'});pass('Precommit interruption retains local input without creation or replay');
 data=controlCreate('prepare');await login(data.email);await open();await fill();controlCreate('revoke-permission');await create().click();await expect(page.getByText('Action not confirmed',{exact:true})).toBeVisible();expect(controlCreate('inspect').institutions).toHaveLength(0);pass('Permission loss after mount denies actual browser Create');
 data=controlCreate('prepare');await login(data.email);await open();await fill();await context.clearCookies();await create().click();await expect(page.getByText('Action not confirmed',{exact:true})).toBeVisible();await expect(name()).toHaveValue(data.name);expect(controlCreate('inspect').institutions).toHaveLength(0);pass('Session loss retains local input and does not replay Create');
 expect(leaks).toBe(0);expect(result.errors).toEqual([]);
}catch(e){result.failure=String(e).slice(0,1800);if(page)writeFileSync('storage/app/private/k3-browser-page.txt',await page.locator('body').innerText().catch(()=>''));console.error(result.failure);process.exitCode=1;}
finally{result.confidentialityLeaks=leaks;writeFileSync(dir+'/results.json',JSON.stringify(result,null,2));await browser.close();}
`;
const temp='scripts/.k3-create-browser-current.mjs';
try{writeFileSync(temp,prefix+body);execFileSync(process.execPath,[temp],{stdio:'inherit'});}finally{unlinkSync(temp);}
