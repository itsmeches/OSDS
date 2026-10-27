import fs from 'node:fs';
import {execFileSync} from 'node:child_process';
import {chromium,expect as baseExpect} from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
const expect=baseExpect.configure({timeout:60000}),base='http://127.0.0.1:8042',phase=process.argv[2]??'before';
if(!['before','after'].includes(phase))throw Error('Explicit diagnostic phase required');
const privateDir='storage/app/private/b4-accessibility-remediation',dir='docs/screenshots/b4-accessibility-remediation/notifications-'+phase;fs.mkdirSync(dir,{recursive:true});
const f=JSON.parse(fs.readFileSync(privateDir+'/fixtures.json','utf8')),ledger=privateDir+'/notification-cases.json';
const cases=fs.existsSync(ledger)?JSON.parse(fs.readFileSync(ledger,'utf8')):{};
const control=()=>JSON.parse(execFileSync(process.env.TEMP+'/osds-phase1-tools/php/php.exe',['scripts/Prepare-B4Accessibility.php','inspect'],{encoding:'utf8'}));
const browser=await chromium.launch({channel:'chrome',headless:true}),contexts=[],results={phase,checks:[],errors:[]};let page;
async function idle(p){await p.waitForTimeout(800);await p.waitForLoadState('networkidle');}
async function login(role,theme='light',width=1440){const c=await browser.newContext({viewport:{width,height:900}});contexts.push(c);await c.route('**/*',r=>new URL(r.request().url()).origin===base?r.continue():r.abort());await c.addInitScript(t=>{localStorage.setItem('theme',t);},theme);const p=await c.newPage();page=p;p.setDefaultTimeout(60000);p.on('dialog',d=>d.accept());p.on('pageerror',e=>results.errors.push(e.message));await p.goto(base+(role==='applicant'?'/applicant/login':'/admin/login'));await idle(p);await p.locator('input[type=email]').fill(f.accounts[role].email);await p.locator('input[type=password]').fill('UiBrowserOnly-2026!');await p.getByRole('button',{name:'Sign in',exact:true}).click();await p.waitForURL(u=>!u.pathname.endsWith('/login'));await idle(p);return p;}
async function fill(p,label,value){await p.getByLabel(label,{exact:false}).fill(value);await p.waitForTimeout(1000);await idle(p);}
try{
 for(const code of ['registration','acsa']){
  if(!cases[code]){
   const p=await login('applicant');await p.goto(base+'/applicant/start');await idle(p);await p.locator('select[name=institution_id]').selectOption(String(f.institutions.auxiliary));await p.locator('select[name=manifest_id]').selectOption({label:code==='registration'?'Certificate of Registration':'Authority to Conduct Sports Activities'});await p.getByRole('button',{name:'Create draft',exact:true}).click();await p.waitForURL(/applicant\/drafts\//);await idle(p);
   for(const [label,value] of [['Organization name','Fictional notification '+code],['Organization address','8 Diagnostic Lane']].concat(code==='acsa'?[['Activity name','Fictional diagnostic sports day'],['Activity date','2026-12-10'],['Activity venue','Fictional campus']]:[]))await fill(p,label,value);
   await p.getByRole('button',{name:'Save draft',exact:true}).click();await idle(p);await p.getByRole('button',{name:'Next: documents',exact:true}).click();await idle(p);await fill(p,'Google file link','https://drive.google.com/file/d/NotificationSynthetic20261027/view');await p.getByRole('button',{name:'Add link',exact:true}).click();await idle(p);await p.getByRole('button',{name:'Next: review',exact:true}).click();await idle(p);await p.getByRole('button',{name:'Submit test application',exact:true}).click();await p.waitForURL(/applicant\/applications\//);cases[code]=new URL(p.url()).pathname.split('/').pop();fs.writeFileSync(ledger,JSON.stringify(cases,null,2));
  }
  for(const [theme,width] of [['light',1440],['dark',1440],['light',375],['dark',375]]){
   const name=code+'-'+theme+'-'+width;let observation=control().cases.find(c=>c.public_id===cases[code]);
   if(observation.decision_stages.at(-1)?.action==='evaluated'){
    const chief=await login('chief');await chief.goto(base+'/admin/synthetic-application-intake?application='+cases[code]);await idle(chief);await chief.getByRole('textbox',{name:'Applicant-facing stage summary (required)',exact:true}).fill('Return for synthetic notification presentation verification.');await chief.getByRole('button',{name:'Return for reevaluation',exact:true}).click();await expect(chief.getByText('Demonstration decision stage recorded',{exact:true}).first()).toBeVisible();await idle(chief);
   }
   const p=await login('evaluator',theme,width);await p.goto(base+'/admin/synthetic-application-intake?application='+cases[code]);await idle(p);
   observation=control().cases.find(c=>c.public_id===cases[code]);if(observation.state==='submitted'){await p.getByRole('button',{name:'Start screening',exact:true}).click();await idle(p);}
   await p.getByRole('textbox',{name:'Applicant-facing stage summary (required)',exact:true}).fill('Synthetic notification diagnostic '+phase+' '+name);await p.getByRole('checkbox',{name:/I reviewed this exact submission/}).check();await idle(p);
   await p.evaluate(()=>{
    const colorCanvas=document.createElement('canvas');colorCanvas.width=colorCanvas.height=1;const colorContext=colorCanvas.getContext('2d',{willReadFrequently:true}),colorCache=new Map();
    const parse=v=>{if(colorCache.has(v))return colorCache.get(v);colorContext.clearRect(0,0,1,1);colorContext.fillStyle=v;colorContext.fillRect(0,0,1,1);const [r,g,b,a]=colorContext.getImageData(0,0,1,1).data,value=[r,g,b,a/255];colorCache.set(v,value);return value;},mix=(fg,bg,a)=>fg.slice(0,3).map((x,i)=>x*a+bg[i]*(1-a));
    const lum=c=>c.slice(0,3).map(x=>{const n=x/255;return n<=0.04045?n/12.92:((n+0.055)/1.055)**2.4;}).reduce((s,x,i)=>s+x*[.2126,.7152,.0722][i],0);
    window.__noticeFrames=[];window.__noticeStop=performance.now()+8500;
    function sample(){const root=[...document.querySelectorAll('.fi-no-notification')].find(n=>n.querySelector('.fi-no-notification-title')?.textContent.trim()==='Demonstration decision stage recorded');
     if(root){const title=root.querySelector('.fi-no-notification-title'),t=getComputedStyle(title),s=getComputedStyle(root),rect=title.getBoundingClientRect(),ancestors=[];let alpha=1;
      for(let e=title;e;e=e.parentElement){const x=getComputedStyle(e);alpha*=Number(x.opacity);ancestors.push({tag:e.tagName,class:e.className,opacity:x.opacity,background:x.backgroundColor});}
      let under=[255,255,255],backdropSource='white fallback';for(let e of document.elementsFromPoint(Math.max(0,rect.x+2),Math.max(0,rect.y+2))){if(root.contains(e)||e.contains(root))continue;for(let n=e;n;n=n.parentElement){const bg=parse(getComputedStyle(n).backgroundColor);if(bg.length>=3&&(bg.length===3||bg[3]===1)){under=bg;backdropSource=n.tagName+'.'+n.className;break;}}if(backdropSource!=='white fallback')break;}
      const foreground=parse(t.color),background=parse(s.backgroundColor),fg=mix(foreground,under,alpha*(foreground[3]??1)),bg=mix(background,under,alpha*(background[3]??1)),l1=lum(fg),l2=lum(bg);
      window.__noticeFrames.push({time:performance.now(),theme:document.documentElement.classList.contains('dark')?'dark':'light',classes:root.className,foreground:t.color,background:s.backgroundColor,backdrop:under,backdropSource,effectiveForeground:fg,effectiveBackground:bg,contrast:(Math.max(l1,l2)+.05)/(Math.min(l1,l2)+.05),ancestorOpacity:alpha,ancestors,visibility:s.visibility,transition:{property:s.transitionProperty,duration:s.transitionDuration},animations:root.getAnimations().map(a=>({playState:a.playState,time:a.currentTime,progress:a.effect?.getComputedTiming().progress}))});
     }if(performance.now()<window.__noticeStop)requestAnimationFrame(sample);
    }requestAnimationFrame(sample);
   });
   await p.getByRole('button',{name:'Record technical evaluation',exact:true}).click();await expect(p.getByText('Demonstration decision stage recorded',{exact:true}).first()).toBeVisible();
   await p.screenshot({path:dir+'/'+name+'-visible.png',fullPage:true});
   const axe=await new AxeBuilder({page:p}).options({rules:{'target-size':{enabled:true}}}).analyze();fs.writeFileSync(dir+'/'+name+'-axe.json',JSON.stringify(axe,null,2));
   await p.waitForTimeout(9000);const frames=await p.evaluate(()=>window.__noticeFrames);fs.writeFileSync(dir+'/'+name+'-frames.json',JSON.stringify(frames,null,2));
   const visible=frames.filter(x=>x.visibility==='visible'&&x.ancestorOpacity>0);const low=visible.filter(x=>x.contrast<4.5);results.checks.push({name,theme,width,actualTheme:[...new Set(frames.map(x=>x.theme))],samples:frames.length,minimumContrast:visible.length?Math.min(...visible.map(x=>x.contrast)):null,lowContrastFrames:low.length,stableMinimum:visible.filter(x=>x.ancestorOpacity===1).length?Math.min(...visible.filter(x=>x.ancestorOpacity===1).map(x=>x.contrast)):null,axeViolations:axe.violations.map(x=>({id:x.id,targets:x.nodes.map(n=>n.target)})),lowClasses:[...new Set(low.map(x=>x.classes))]});
   fs.writeFileSync(dir+'/results.json',JSON.stringify(results,null,2));console.log(JSON.stringify(results.checks.at(-1)));
  }
 }
}catch(e){process.exitCode=1;results.failure=String(e);if(page)await page.screenshot({path:dir+'/failure.png',fullPage:true});}
finally{for(const c of contexts)await c.close();await browser.close();fs.writeFileSync(dir+'/results.json',JSON.stringify(results,null,2));}
