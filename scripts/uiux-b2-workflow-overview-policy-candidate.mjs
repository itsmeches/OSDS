// Isolated candidate: historical validators/attempts remain unchanged.
import {validateRequestAttributed} from './uiux-b2-first-fatal-candidate.mjs';
export const workflowWidget='App\\Filament\\Widgets\\WorkflowOverview';
const object=value=>value!==null&&typeof value==='object'&&!Array.isArray(value);
const blocked=()=>{throw Error('Workflow request contract blocked');};
const snapshot=value=>{
  if(typeof value!=='string'||value.length>262144)blocked();
  const s=JSON.parse(value);
  if(!object(s)||!object(s.memo)||!object(s.data)||typeof s.memo.id!=='string'||!s.memo.id||s.memo.id.length>128||!/^[a-f0-9]{64}$/.test(s.checksum??''))blocked();
  return s;
};
const comparable=s=>{const copy=JSON.parse(JSON.stringify(s));delete copy.memo.children;return JSON.stringify(copy);};

export function validateWorkflowRequest(body,phase,witness,rejected=()=>{}) {
  let s;
  try{s=Array.isArray(body?.components)&&body.components.length===1?JSON.parse(body.components[0].snapshot):null;}catch{}
  if(s?.memo?.name!==workflowWidget){
    const kind=validateRequestAttributed(body,phase,rejected);
    if(kind==='advance'){rejected('COMPONENT_ACTION_BLOCKED');blocked();}
    return kind;
  }
  try{
    const c=body.components[0];s=snapshot(c.snapshot);
    if(!['login','idle'].includes(phase)||!witness?.dashboardVerified||!witness.documentVerified||witness.widgetCount!==1)blocked();
    const current=snapshot(witness.currentSnapshot);
    if(current.memo.name!==workflowWidget||current.memo.id!==s.memo.id||comparable(s)!==comparable(current))blocked();
    if(!object(c.updates)||Object.keys(c.updates).length||!Array.isArray(c.calls)||c.calls.length!==1)blocked();
    const call=c.calls[0];if(!object(call)||!Array.isArray(call.params))blocked();
    if(call.method==='$refresh'&&call.params.length===0&&s.memo.lazyLoaded===true)return 'workflow-refresh';
    if(call.method!=='__lazyLoad'||s.memo.lazyLoaded!==false||s.memo.lazyIsolated!==true||call.params.length!==1)blocked();
    const token=call.params[0];
    if(typeof token!=='string'||token.length>131072||token!==witness.lazyToken||!token.length||token.length%4||!/^[A-Za-z0-9+/]*={0,2}$/.test(token))blocked();
    const decoded=Buffer.from(token,'base64');if(decoded.toString('base64')!==token)blocked();
    const container=snapshot(decoded.toString('utf8'));
    if(container.memo.name!=='__mountParamsContainer'||container.data.forComponent!==workflowWidget||!Array.isArray(container.data.forMount)||Object.keys(container.data).sort().join()!=='forComponent,forMount')blocked();
    return 'workflow-lazy';
  }catch{rejected('COMPONENT_ACTION_BLOCKED');blocked();}
}

// Private in-memory witness only. No snapshot, ID or token is persisted.
export function inspectWorkflowWitness({id}) {
  const expected='App\\Filament\\Widgets\\WorkflowOverview';
  let dashboard=0,login=0,widgetCount=0,malformed=false,currentSnapshot=null,lazyToken=null;
  for(const node of document.querySelectorAll('[wire\\:id]')){
    try{
      const encoded=node.__livewire?.snapshotEncoded??node.getAttribute('wire:snapshot'),s=JSON.parse(encoded);
      if(typeof s?.memo?.name!=='string'){malformed=true;continue;}
      if(s.memo.name==='App\\Filament\\Pages\\Dashboard')dashboard++;
      if(s.memo.name==='Filament\\Auth\\Pages\\Login')login++;
      if(s.memo.name===expected){
        widgetCount++;
        if(s.memo.id===id&&node.getAttribute('wire:id')===id){
          currentSnapshot=encoded;
          const expression=node.getAttribute('x-intersect')??node.getAttribute('x-init');
          const match=expression?.match(/^\$wire\.__lazyLoad\('([A-Za-z0-9+/=]+)'\)$/);
          lazyToken=match?.[1]??null;
        }
      }
    }catch{malformed=true;}
  }
  const loginForm=Array.from(document.querySelectorAll('form')).some(f=>f.querySelector('input[type=email]')&&f.querySelector('input[type=password]'));
  return {dashboard,login,widgetCount,malformed,loginForm,currentSnapshot,lazyToken};
}

export function createWorkflowDashboardObserver({page,base,hasFailed,getSubmitted}) {
  let pinned;
  const atAdmin=()=>{try{const u=new URL(page.url());return u.origin===base&&u.pathname==='/admin';}catch{return false;}};
  const safe=()=>{if(hasFailed()||!getSubmitted()||!atAdmin())blocked();};
  return {
    observe:async id=>{
      safe();pinned??=await page.evaluateHandle(()=>document);
      if(!await page.evaluate(p=>document===p,pinned))blocked();
      const w=await page.evaluate(inspectWorkflowWitness,{id});
      const heading=page.getByRole('heading',{name:'Your workspace',exact:true,level:1,includeHidden:true});
      const visible=await heading.count()===1&&await heading.isVisible();
      safe();if(!await page.evaluate(p=>document===p,pinned))blocked();safe();
      if(w.dashboard!==1||w.login!==0||w.malformed||w.loginForm||w.widgetCount!==1||!visible||!w.currentSnapshot)blocked();
      return {...w,dashboardVerified:true,documentVerified:true};
    },
    dispose:async()=>{if(pinned){await pinned.dispose();pinned=null;}},
  };
}

export function createWorkflowRouteCandidate({base,getEndpoint,getPhase,getSubmitted,setSubmitted,onLoginPost,mark,fatal,hasFailed,observer,onWidget=()=>{}}) {
  // Five-second widget polling over the reviewed <=60s Dashboard allowance:
  // shared roots 30s + heading 5s + sidebar assertions/trial <=25s. No wait added.
  let lazyCount=0,refreshCount=0,boundId=null;
  return async route=>{
    let reason='ROUTE_HANDLER_EXCEPTION';
    try{
      const req=route.request(),url=new URL(req.url());
      if(url.origin!==base){fatal('EXTERNAL_ORIGIN_BLOCKED',Error('External request blocked'));return route.abort();}
      if(req.method()==='POST'){
        if(url.pathname!==getEndpoint()){reason='POST_ENDPOINT_BLOCKED';blocked();}
        let body;try{body=req.postDataJSON();}catch(error){reason='POST_BODY_INVALID';throw error;}
        let widget=false,id;
        try{const memo=body.components.length===1?JSON.parse(body.components[0].snapshot).memo:null;widget=memo?.name===workflowWidget;id=memo?.id;}catch{}
        let witness;
        if(widget){reason='COMPONENT_ACTION_BLOCKED';if(hasFailed())blocked();witness=await observer.observe(id);if(hasFailed())blocked();}
        const kind=validateWorkflowRequest(body,getPhase(),witness,code=>{reason=code;});
        if(kind.startsWith('workflow-')){
          if(hasFailed()||!getSubmitted()||(boundId!==null&&boundId!==id))blocked();boundId??=id;
          if(kind==='workflow-lazy'){if(lazyCount>=1)blocked();lazyCount++;}
          else{if(refreshCount>=12)blocked();refreshCount++;}
          onWidget(kind); // Separate closed traffic kind, never a notification.
        }else{
          if(kind==='login')onLoginPost();
          if(kind!=='notification'){if(getSubmitted()){reason='CONSEQUENTIAL_REPEAT_BLOCKED';blocked();}setSubmitted(true);}
          mark(kind==='login'?'login-post-forward':'notification-post-forward');
        }
      }else if(req.method()!=='GET'){reason='HTTP_METHOD_BLOCKED';blocked();}
      if(req.method()==='GET'&&url.pathname.startsWith('/admin')&&!['/admin','/admin/login','/admin/case-demonstration-documents'].includes(url.pathname)){reason='ADMIN_PATH_BLOCKED';blocked();}
      return route.continue();
    }catch(error){fatal(reason,error);return route.abort();}
  };
}
