// Isolated diagnostic candidate. No protected attempt imports this module.
import {validateRequest} from './uiux-b2-reviewer-policy.mjs';

export const fatalReasons=Object.freeze([
  'EXTERNAL_ORIGIN_BLOCKED','POST_ENDPOINT_BLOCKED','POST_BODY_INVALID',
  'COMPONENT_COUNT_INVALID','SNAPSHOT_OR_CALL_INVALID','COMPONENT_ACTION_BLOCKED',
  'CONSEQUENTIAL_REPEAT_BLOCKED','HTTP_METHOD_BLOCKED','ADMIN_PATH_BLOCKED',
  'ROUTE_HANDLER_EXCEPTION','PAGE_ERROR','HTTP_ERROR','REQUEST_FAILED',
]);
const phases=new Set(['login-get','login-ready','login-submit','login-verifying','idle']);
const paths=new Set(['/admin','/admin/login','/admin/case-demonstration-documents']);

export function createFirstFatalCandidate({signal,context,clock=()=>performance.now()}) {
  let first=null,sequence=0,lastTime=0,cleanupStarted=false,persistenceFailed=false;
  const events=[];
  function fatal(reason,error) {
    if(!fatalReasons.includes(reason))throw Error('Unknown fatal category');
    // Capture and latch synchronously before the caller's existing fatal signal.
    let value={};try{value=context();}catch{}
    let time;try{time=clock();}catch{}
    if(Number.isFinite(time))lastTime=Math.min(86400000,Math.max(lastTime,Math.round(time),0));
    let pathname='/[other]';
    try{const u=new URL(value.url);if(u.origin===value.base&&paths.has(u.pathname))pathname=u.pathname;}catch{}
    const record=Object.freeze({reason,sequence:Math.min(++sequence,65535),elapsedMs:lastTime,
      phase:phases.has(value.phase)?value.phase:'restricted-phase',pathname,cleanupStarted});
    first??=record;
    if(events.length<64)events.push(record);
    signal(error); // Every valid invocation still signals, including cleanup events.
    return record;
  }
  return {
    fatal,startCleanup:()=>{cleanupStarted=true;},
    get first(){return first;},get events(){return Object.freeze([...events]);},
    get persistenceFailed(){return persistenceFailed;},
    async persistFirst(write){
      if(!first)return false;
      try{await write(first);return true;}catch{persistenceFailed=true;return false;}
    },
  };
}

// This classifier is consulted ONLY after the original policy has rejected.
// It never grants a request and never classifies by exception message.
function rejectedPolicyReason(body) {
  try{
    if(!Array.isArray(body?.components)||body.components.length!==1)return 'COMPONENT_COUNT_INVALID';
    const c=body.components[0];let snapshot;
    try{snapshot=JSON.parse(c.snapshot);}catch{return 'POST_BODY_INVALID';}
    if(!snapshot?.memo?.id||typeof snapshot.memo.name!=='string'||!Array.isArray(c.calls)||c.calls.length!==1)
      return 'SNAPSHOT_OR_CALL_INVALID';
    if(!c.calls[0]||typeof c.calls[0]!=='object')return 'SNAPSHOT_OR_CALL_INVALID';
    return 'COMPONENT_ACTION_BLOCKED';
  }catch{return 'SNAPSHOT_OR_CALL_INVALID';}
}

export function validateRequestAttributed(body,phase,rejected) {
  try{return validateRequest(body,phase);}
  catch(error){rejected(rejectedPolicyReason(body));throw error;}
}

export function createAttributedRoute({base,getEndpoint,getPhase,getSubmitted,setSubmitted,
  onLoginPost,mark,fatal}) {
  return async route=>{
    let reason='ROUTE_HANDLER_EXCEPTION';
    try{
      const req=route.request(),url=new URL(req.url());
      if(url.origin!==base){fatal('EXTERNAL_ORIGIN_BLOCKED',Error('External request blocked'));return route.abort();}
      if(req.method()==='POST'){
        if(url.pathname!==getEndpoint()){reason='POST_ENDPOINT_BLOCKED';throw Error('Unexpected POST endpoint');}
        let body;try{body=req.postDataJSON();}catch(error){reason='POST_BODY_INVALID';throw error;}
        const kind=validateRequestAttributed(body,getPhase(),code=>{reason=code;});
        if(kind==='login')onLoginPost();
        if(kind!=='notification'){
          if(getSubmitted()){reason='CONSEQUENTIAL_REPEAT_BLOCKED';throw Error('Repeated consequential request blocked');}
          setSubmitted(true);
        }
        mark(kind==='login'?'login-post-forward':kind==='notification'?'notification-post-forward':'lifecycle-post-forward');
      }else if(req.method()!=='GET'){reason='HTTP_METHOD_BLOCKED';throw Error('Unexpected HTTP method');}
      if(req.method()==='GET'&&url.pathname.startsWith('/admin')&&!['/admin','/admin/login','/admin/case-demonstration-documents'].includes(url.pathname)){
        reason='ADMIN_PATH_BLOCKED';throw Error('Unexpected admin navigation');
      }
      return route.continue();
    }catch(error){fatal(reason,error);return route.abort();}
  };
}

export function installAttributedListeners({page,base,mark,fatal,onPageError}) {
  const pageerror=error=>{onPageError(error);fatal('PAGE_ERROR',Error('JavaScript page error'));};
  const response=value=>{if(new URL(value.url()).origin===base){
    mark('http-response',{url:value.url(),method:value.request().method(),status:value.status()});
    if(value.status()>=400)fatal('HTTP_ERROR',Error('HTTP error '+value.status()));
  }};
  const requestfailed=()=>{mark('request-failed');fatal('REQUEST_FAILED',Error('Browser request failed'));};
  page.on('pageerror',pageerror);page.on('response',response);page.on('requestfailed',requestfailed);
  return ()=>{page.off('pageerror',pageerror);page.off('response',response);page.off('requestfailed',requestfailed);};
}
