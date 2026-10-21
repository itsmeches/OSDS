import {readFileSync,writeFileSync,unlinkSync} from 'node:fs';
import {execFileSync} from 'node:child_process';

// Reuse login/layout helpers only. Keep default axe rules and explicitly enable
// WCAG 2.2 target-size (not enabled by default); no exclusions or exceptions.
const baseline=process.argv.includes('--before');
for(const [workflow,port,prepare,path,label] of [
 ['representation',8027,'Prepare-RepresentationCreation.php','institution-representatives','institutionName'],
 ['regional',8026,'Prepare-RegionalGrantCreation.php','user-region-assignments','regionName'],
]){
 let prefix=readFileSync('scripts/verify-evidence-reviewer-creation.mjs','utf8').split('const controlCreate=')[0]
  .replaceAll('phase-3d7-g-int005',`phase-3d7-selector-accessibility/${baseline?'before':'after'}/${workflow}`)
  .replaceAll('8025',String(port));
 // Fixture controls write private JSON rather than printing it.
 const code=prefix+String.raw`
const prepare='PREPARE',workflow='WORKFLOW';
execFileSync('php',['scripts/'+prepare,'prepare'],{stdio:'pipe'});
const data=JSON.parse(readFileSync('storage/app/private/FIXTURE.json'));
async function choose(index,name){await page.getByRole('combobox').nth(index).click();await page.locator('.fi-select-input-search-ctn input:visible').fill(name);await page.getByRole('option',{name,exact:true}).click();}
try{
 fixture={super:{email:'unused'}};await login(data.email);await visit('/admin/PATH/create');await stable();
 await choose(0,data.targetName);await choose(1,data.LABEL);
 for(const theme of ['light','dark'])for(const width of [1440,375]){
  await page.setViewportSize({width,height:1000});await page.evaluate(t=>document.documentElement.classList.toggle('dark',t==='dark'),theme);await stable();
  const buttons=page.locator('.fi-select-input-value-remove-btn:visible');expect(await buttons.count()).toBe(2);
  const sizes=await buttons.evaluateAll(es=>es.map(e=>{const r=e.getBoundingClientRect();return {width:r.width,height:r.height,label:e.getAttribute('aria-label')};}));
  const axe=await new AxeBuilder({page}).options({rules:{'target-size':{enabled:true}}}).analyze();
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
  result.screens.push({workflow,theme,width,sizes,overflow,violations:axe.violations.map(v=>({id:v.id,impact:v.impact,nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary}))}))});
  await page.screenshot({path:dir+'/'+theme+'-'+width+'.png'});
  expect(overflow).toBe(false);
  if(!BASELINE){expect(axe.violations).toEqual([]);for(const s of sizes){expect(s.width).toBeGreaterThanOrEqual(24);expect(s.height).toBeGreaterThanOrEqual(24);}
   for(let i=0;i<2;i++){
    await page.getByRole('combobox').nth(i).focus();await page.keyboard.press('Tab');await expect(buttons.nth(i)).toBeFocused();
    const focus=await buttons.nth(i).evaluate(e=>{const b=e.closest('.fi-select-input').querySelector('.fi-select-input-btn'),s=getComputedStyle(b);return {outlineStyle:s.outlineStyle,outlineWidth:s.outlineWidth,outlineColor:s.outlineColor};});
    result.keyboard.push({theme,width,index:i,focus});expect(focus.outlineStyle).toBe('solid');expect(parseFloat(focus.outlineWidth)).toBeGreaterThanOrEqual(2);
    await page.screenshot({path:dir+'/focus-'+i+'-'+theme+'-'+width+'.png'});
    await page.keyboard.press('Enter');expect(await buttons.count()).toBe(1);
    const combo=page.getByRole('combobox').nth(i);await combo.focus();await page.keyboard.press('Space');
    await page.locator('.fi-select-input-search-ctn input:visible').fill(i===0?data.targetName:data.LABEL);
    const option=page.getByRole('option',{name:i===0?data.targetName:data.LABEL,exact:true});await expect(option).toBeVisible();
    await page.locator('.fi-select-input-search-ctn input:visible').press('ArrowDown');await expect(option).toBeFocused();await page.keyboard.press('Enter');await expect(buttons).toHaveCount(2);
   }
  }
 }
 if(!BASELINE){
  const date=page.locator('[id="form.starts_at"]'),value=await date.inputValue();await date.fill('');await page.getByRole('button',{name:'Create',exact:true}).click();expect(await date.evaluate(e=>e.validity.valueMissing)).toBe(true);await date.fill(value);
  await page.getByRole('button',{name:'Create',exact:true}).click();await expect(page).toHaveURL(/\/admin\/PATH\/\d+\/edit$/);
  execFileSync('php',['scripts/'+prepare,'inspect'],{stdio:'pipe'});const saved=JSON.parse(readFileSync('storage/app/private/FIXTURE.json'));expect(saved.assignments).toHaveLength(1);expect(saved.assignments[0].audits).toBe(1);
  pass('Keyboard clear/reselect, required validation and actual Create persist one audited assignment');
 }
 expect(result.errors).toEqual([]);
}catch(e){result.failure=String(e).slice(0,1800);console.error(result.failure);process.exitCode=1;}
finally{writeFileSync(dir+'/results.json',JSON.stringify(result,null,2));await browser.close();}
`.replaceAll('PREPARE',prepare).replaceAll('WORKFLOW',workflow).replaceAll('FIXTURE',workflow==='representation'?'representation-creation':'regional-grant-creation').replaceAll('PATH',path).replaceAll('LABEL',label).replaceAll('BASELINE',String(baseline));
 const temp='scripts/.selector-accessibility-current.mjs';
 try{writeFileSync(temp,code);execFileSync(process.execPath,[temp],{stdio:'inherit'});}finally{unlinkSync(temp);}
}
