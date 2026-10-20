import { chromium, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';

const dir='docs/screenshots/phase-3d7-e';
mkdirSync(dir,{recursive:true});
const fixture=()=>JSON.parse(readFileSync('storage/app/private/builder-journey.json','utf8'));
const control=action=>{execFileSync('php',['scripts/Prepare-BuilderJourney.php',action],{stdio:'pipe'});return fixture();};
const data=control('prepare');
const browser=await chromium.launch({channel:'chrome',headless:true});
const context=await browser.newContext({viewport:{width:1440,height:1000}});
await context.route('**/*',r=>new URL(r.request().url()).hostname==='127.0.0.1'?r.continue():r.abort());
const page=await context.newPage();
const trace={before:data,steps:[]};
await page.addInitScript(() => {
    window.builderEvents=[];
    const add=EventTarget.prototype.addEventListener, remove=EventTarget.prototype.removeEventListener;
    const wrappers=new WeakMap();
    EventTarget.prototype.addEventListener=function(type,listener,options){
        if(!listener || !['input','change'].includes(type) || !(this instanceof HTMLInputElement || this instanceof HTMLTextAreaElement || this instanceof HTMLSelectElement))return add.call(this,type,listener,options);
        const registered=this.getAttributeNames().find(a=>a.startsWith('wire:model'));
        const path=registered?this.getAttribute(registered):null;
        if(!path)return add.call(this,type,listener,options);
        const wrapped=function(event){
            const labels=()=>{
                const root=this.closest('[wire\\:id]');
                const wire=root&&window.Livewire?.find(root.getAttribute('wire:id'));
                return wire?.sections?.[0]?.questions?.map(q=>({key:q.key,label:q.label}));
            };
            const entry={registeredPath:path,currentPath:this.getAttribute(registered),type,before:labels(),value:this.value};
            const result=typeof listener==='function'?listener.call(this,event):listener.handleEvent(event);
            entry.after=labels();
            if(this.closest('.focused-editor'))window.builderEvents.push(entry);
            return result;
        };
        let entries=wrappers.get(this);if(!entries)wrappers.set(this,entries=new Map());
        entries.set(listener,wrapped);
        return add.call(this,type,wrapped,options);
    };
    EventTarget.prototype.removeEventListener=function(type,listener,options){return remove.call(this,type,wrappers.get(this)?.get(listener)||listener,options);};
});
trace.requests=[];
page.on('request',request=>{
    if(!request.url().includes('livewire') || request.method()!=='POST')return;
    const payload=request.postDataJSON();
    const components=payload.components?.filter(c=>typeof JSON.parse(c.snapshot).data.formLock==='number');
    if(components?.length)trace.requests.push(components.map(c=>({updates:c.updates,calls:c.calls,locks:[JSON.parse(c.snapshot).data.formLock,JSON.parse(c.snapshot).data.requirementLock]})));
});
trace.responses=[];
page.on('response',async response=>{
    if(!response.url().includes('livewire') || response.request().method()!=='POST')return;
    try{const payload=await response.json();const entries=payload.components?.map(c=>({data:JSON.parse(c.snapshot).data,returns:c.effects.returns})).filter(c=>typeof c.data.formLock==='number');if(entries?.length)trace.responses.push(entries);}catch{}
});
const state=async name=>{
    trace.steps.push(await page.evaluate(name=>{
        const root=document.querySelector('.form-builder').closest('[wire\\:id]');
        const wire=window.Livewire.find(root.getAttribute('wire:id'));
        const input=document.querySelector('.focused-editor input[wire\\:model\\.live\\.debounce\\.400ms]');
        return {name,selected:wire.selectedQuestionKey,labels:wire.sections[0].questions.map(q=>({key:q.key,label:q.label})),inputValue:input?.value,binding:input?.getAttribute('wire:model.live.debounce.400ms')};
    },name));
};
try{
    await page.goto('http://127.0.0.1:8017/admin/login');
    await page.locator('input[type=email]').fill('osds@example.invalid');
    await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await page.getByRole('button',{name:'Sign in',exact:true}).click();
    await page.waitForURL(url=>!url.pathname.endsWith('/login'));
    await page.goto('http://127.0.0.1:8017'+data.builder);
    await expect(page.getByLabel('Question title',{exact:true})).toHaveValue('Organization category');
    await state('Initial source selected');
    await page.locator('#question-dependent').click();
    await expect(page.getByLabel('Question title',{exact:true})).toHaveValue('Conditional information');
    await state('Dependent selected and displayed');
    await page.getByLabel('Question title',{exact:true}).fill('Intended dependent title');
    trace.nativeEvents=await page.evaluate(()=>window.builderEvents);
    await page.getByLabel('Question title',{exact:true}).blur();
    await state('Typed into selected dependent editor');
    await page.getByRole('button',{name:'Save draft',exact:true}).click();
    await expect.poll(()=>control('inspect').locks[0]).toBe(data.locks[0]+1);
    trace.after=control('inspect');
    await page.reload();
    await state('Reloaded confirmed save');
    await page.screenshot({path:`${dir}/selection-integrity-after-reload.png`});
    expect(trace.after.questionLabels.source).toBe('Organization category');
    expect(trace.after.questionLabels.dependent).toBe('Intended dependent title');
    trace.result='passed';
}catch(error){trace.result='failed';trace.failure=String(error);console.error(error);process.exitCode=1;}
finally{writeFileSync(`${dir}/selection-integrity.json`,JSON.stringify(trace,null,2));await browser.close();}
