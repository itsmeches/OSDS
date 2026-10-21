// Dedicated outstanding recovery branch only; no authoring/review/publication replay.
import {readFileSync,writeFileSync,unlinkSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
let source=readFileSync('scripts/verify-builder-recovery.mjs','utf8');
const start=source.indexOf("    await newContext();await page.goto(base+'/admin/questionnaires?module=registration')");
const end=source.indexOf('}catch(error)',start);
if(start<0||end<0)throw new Error('Existing recovery/authoring boundary changed');
source=source.slice(0,start)+source.slice(end);
source=source.replaceAll('docs/screenshots/phase-3d7-e','docs/screenshots/phase-3d7-builder-recovery-final');
source=source.replace("const pass=name=>{results.checks.push(name);console.log('Passed: '+name);};",`const pass=name=>{
 const persisted=control('inspect');
 expect(persisted.questionRules).toEqual(data.questionRules);
 expect(persisted.documentRules).toEqual(data.documentRules);
 expect(persisted.documents).toEqual(data.documents);
 results.persistence??=[];results.persistence.push({name,locks:persisted.locks,states:persisted.states,questionLabels:persisted.questionLabels,questionRules:persisted.questionRules,documents:persisted.documents,reviewCount:persisted.reviewCount});
 results.checks.push(name);console.log('Passed: '+name);
};`);
source=source.replace("page.on('pageerror',e=>results.errors.push(e.message));",`page.on('pageerror',e=>results.errors.push(e.message));
 page.on('console',m=>{if(m.type()==='error')(results.consoleErrors??=[]).push(m.text());});
 page.on('requestfailed',r=>(results.failedRequests??=[]).push({path:new URL(r.url()).pathname,error:r.failure()?.errorText}));`);
const selectDoc=`await page.getByRole('button',{name:/2 Required documents/}).click();await page.locator('#requirement-second_document').click();await expect(page.getByLabel('Document name',{exact:true})).toHaveValue('Second document');await page.getByLabel('Instructions',{exact:true}).fill('RAM-only second document recovery');`;
source=source.replace(".fill('Local during outage');await page.route", ".fill('Local during outage');"+selectDoc+"await page.route");
source=source.replace("await page.unrouteAll({behavior:'wait'});pass('Actual HTTP 503", "await expect(page.getByLabel('Document name',{exact:true})).toHaveValue('Second document');await expect(page.getByLabel('Instructions',{exact:true})).toHaveValue('RAM-only second document recovery');await screens('http-503-selected-document');await page.unrouteAll({behavior:'wait'});pass('Actual HTTP 503");
source=source.replace(".fill('Session retained local title');await context.clearCookies()", ".fill('Session retained local title');"+selectDoc+"await context.clearCookies()");
source=source.replace("pass('Real cookie loss is caught", "await expect(page.getByLabel('Document name',{exact:true})).toHaveValue('Second document');await expect(page.getByLabel('Instructions',{exact:true})).toHaveValue('RAM-only second document recovery');await screens('expired-session-selected-document');pass('Real cookie loss is caught");
source=source.replace("pass('Response lost after commit", "await screens('uncertain-committed-save');pass('Response lost after commit");
// Keep all original scenario assertions, plus explicit final page-error gate.
source=source.replace('}catch(error)',"expect(results.errors).toEqual([]);\n}catch(error)");
const temp='scripts/.dedicated-builder-recovery-current.mjs';
try{writeFileSync(temp,source);execFileSync(process.execPath,[temp],{stdio:'inherit'});}finally{unlinkSync(temp);}
