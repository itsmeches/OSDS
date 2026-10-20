import {chromium,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync} from 'node:fs';
execFileSync('php',['scripts/Prepare-BuilderJourney.php','prepare-matrix'],{stdio:'pipe'});
const f=JSON.parse(readFileSync('storage/app/private/builder-journey.json','utf8'));
const b=await chromium.launch({channel:'chrome',headless:true}),p=await b.newPage();
await p.route('**/*',r=>new URL(r.request().url()).hostname==='127.0.0.1'?r.continue():r.abort());
await p.addInitScript(()=>{
    window.orderTrace=[];
    document.addEventListener('livewire:init',()=>Livewire.hook('component.init',({component:c})=>{
        if(typeof c.reactive.formLock!=='number')return;
        const state=()=>({selected:c.reactive.selectedQuestionKey,labels:c.reactive.sections.flatMap(s=>s.questions).map(q=>[q.key,q.label])});
        const merge=c.mergeNewSnapshot.bind(c);
        c.mergeNewSnapshot=(...args)=>{window.orderTrace.push({phase:'before-merge',...state(),incomingSelected:JSON.parse(args[0]).data.selectedQuestionKey});const r=merge(...args);window.orderTrace.push({phase:'after-merge',...state()});return r;};
        c.$wire.$interceptMessage(({message,onSend})=>onSend(()=>window.orderTrace.push({phase:'dispatch',actions:[...message.actions].map(a=>a.name),updates:JSON.parse(JSON.stringify(message.updates)),...state()})));
    }));
});
let failure;
try{
    await p.goto('http://127.0.0.1:8017/admin/login');await p.locator('input[type=email]').fill('osds@example.invalid');await p.locator('input[type=password]').fill('UiBrowserOnly-2026!');await p.getByRole('button',{name:'Sign in',exact:true}).click();await p.waitForURL(u=>!u.pathname.endsWith('/login'));await p.goto('http://127.0.0.1:8017'+f.builder);
    if(process.argv.includes('--delayed')){
        let release,arrive,captured=false;const arrived=new Promise(r=>arrive=r);
        await p.route('**/livewire-*/*',async route=>{const payload=route.request().postDataJSON();if(!captured&&payload.components?.some(c=>Object.keys(c.updates??{}).some(k=>k.includes('sections')))){captured=true;const response=await route.fetch();arrive();await new Promise(r=>release=r);await route.fulfill({response});}else await route.continue();});
        await p.getByLabel('Question title',{exact:true}).fill('First pending');await arrived;
        await p.locator('#question-dependent').click();
        const merges=await p.evaluate(()=>window.orderTrace.filter(t=>t.phase==='after-merge').length);release();
        await expect.poll(()=>p.evaluate(()=>window.orderTrace.filter(t=>t.phase==='after-merge').length)).toBeGreaterThan(merges);
        await expect(p.locator('#question-dependent')).toHaveAttribute('aria-pressed','true');
    } else for(const [key,value] of [['source','First v1'],['dependent','Second v1'],['source','First v2'],['dependent','Second v2'],['simple','Third v1']]){
        await p.locator('#question-'+key).click();await expect(p.locator('#question-'+key)).toHaveAttribute('aria-pressed','true');await p.getByLabel('Question title',{exact:true}).fill(value);
    }
}catch(e){failure=String(e);process.exitCode=1;}
writeFileSync('docs/screenshots/phase-3d7-e/ordering-trace.json',JSON.stringify({failure,trace:await p.evaluate(()=>window.orderTrace)},null,2));await b.close();
