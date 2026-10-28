// Pure diagnostics. Raw exception text, request payloads and storage are never exported.
const names=new Set(['Error','TypeError','ReferenceError','SyntaxError','RangeError','URIError','EvalError','AggregateError','TimeoutError']);
// Exact public-source identifiers only. Never interpolate an observed identifier.
const publicSymbols=new Map([
 ['passwordRevealFormComponent','PASSWORD_REVEAL_FACTORY'],
 ['isPasswordRevealed','PASSWORD_REVEAL_STATE'],
 ['Alpine','ALPINE_GLOBAL'],
 ['Livewire','LIVEWIRE_GLOBAL'],
]);
const publicErrors=new Map();
for(const [symbol,code] of publicSymbols){
 publicErrors.set(`${symbol} is not defined`,`${code}_UNDEFINED`);
 publicErrors.set(`Cannot access '${symbol}' before initialization`,`${code}_BEFORE_INITIALIZATION`);
}
export function assetFamily(value,base='http://127.0.0.1:8044'){
 try{
  if(typeof value!=='string'||value.length>2048)return 'unknown';
  const u=new URL(value,base);if(u.origin!==base||u.username||u.password)return 'unknown';
  if(/^\/livewire-[a-f0-9]+\/livewire(?:\.min)?\.js$/.test(u.pathname))return 'livewire-core';
  return new Map([
   ['/js/filament/support/support.js','filament-support'],
   ['/js/filament/filament/app.js','filament-panel'],
   ['/js/filament/tables/tables.js','filament-tables'],
   ['/js/filament/forms/components/text-input/password-reveal.js','filament-password-reveal'],
  ]).get(u.pathname)??'unknown';
 }catch{return 'unknown';}
}
export function safePath(value,base='http://127.0.0.1:8044'){
 try{const u=new URL(value,base);if(u.origin!==base)return '/[external]';
  if(['/admin','/admin/login','/admin/case-demonstration-documents'].includes(u.pathname))return u.pathname;
  if(/^\/livewire-[a-f0-9]+\/update$/.test(u.pathname))return '/[livewire-update]';
  if(/^\/(build|js|css|fonts|livewire-[a-f0-9]+)\//.test(u.pathname))return '/[asset]';
 }catch{}return '/[restricted]';
}
export function safePhase(phase){
 if(['login-get','login-ready','login-submit','login-verifying','idle'].includes(phase))return phase;
 if(phase&&typeof phase==='object'&&[47,48].includes(phase.id)&&((phase.lock===1&&phase.to==='reviewed')||(phase.lock===2&&phase.to==='published')))return `${phase.id}/${phase.lock}/${phase.to}`;
 return 'restricted-phase';
}
export function sanitizeException(error,context={},secrets=[]){
 const out={name:'RestrictedError',category:'RESTRICTED',message:'[restricted exception details]',stack:[],phase:safePhase(context.phase),pathname:safePath(context.url),loginFormDisplayed:context.loginFormDisplayed===true,loginPostAttempted:context.loginPostAttempted===true,authenticationConfirmed:context.authenticationConfirmed===true};
 try{
  if(names.has(error?.name))out.name=error.name;
  const message=typeof error?.message==='string'?error.message:'';
  const knownSecret=value=>secrets.some(s=>typeof s==='string'&&s.length>0&&value.includes(s));
  const sensitive=value=>knownSecret(value)||/password|credential|authorization|bearer|cookie|session|csrf|token|[\w.+-]+@[\w.-]+|eyJ[A-Za-z0-9_-]{12,}/i.test(value);
  if(message.length<=2048&&!knownSecret(message)&&out.name==='ReferenceError'&&publicErrors.has(message)){
   out.category=publicErrors.get(message); // Only a fixed category is exported.
  }else if(message.length<=2048&&!sensitive(message)){
   const match=message.match(/^Cannot read properties of (undefined|null)(?: \(reading '[^'\r\n]{1,80}'\))?$/);
   if(match)out.message=`Cannot read properties of ${match[1]} (property restricted)`;
   else if(/^[A-Za-z_$][\w$]{0,80} is not defined$/.test(message))out.message='Identifier is not defined (identifier restricted)';
   else if(message==='Cannot convert undefined or null to object')out.message=message;
   else if(/^Identifier '[^'\r\n]{1,80}' has already been declared$/.test(message))out.message='Identifier has already been declared (identifier restricted)';
  }
  const stack=typeof error?.stack==='string'?error.stack:'';
  if(stack.length<=8192){
   for(const line of stack.split(/\r?\n/).slice(1,9)){
    const location=line.match(/(https?:\/\/[^\s)]+):(\d{1,7}):(\d{1,7})\)?$/);
    const family=location?assetFamily(location[1]):'unknown';
    if(!location||knownSecret(location[1])||(family==='unknown'&&sensitive(location[1]))){out.stack.push({category:'restricted-frame'});continue;}
    // Family classification uses the query-free public path. Raw paths stay hidden.
    out.stack.push({pathname:safePath(location[1]),assetFamily:family,line:Number(location[2]),column:Number(location[3])});
   }
  }else out.stack=[{category:'oversized-stack'}];
 }catch{out.name='RestrictedError';out.category='RESTRICTED';out.message='[restricted exception details]';out.stack=[];}
 return out;
}

const eventTypes=new Set(['login-get-start','login-get-complete','controls-visible','component-check-start','component-check-complete','email-fill-start','email-fill-complete','password-fill-start','password-fill-complete','sign-in-click-start','sign-in-click-complete','login-post-forward','notification-post-forward','lifecycle-post-forward','http-response','page-error','request-failed','navigation-check-start','navigation-check-complete','auth-link-check-start','auth-link-check-complete','authentication-confirmed','workflow-failure']);
export function createEventRecorder({clock=()=>performance.now(),limit=256}={}){
 if(!Number.isInteger(limit)||limit<1||limit>512)throw Error('Invalid diagnostic event bound');
 const origin=clock();if(!Number.isFinite(origin))throw Error('Invalid diagnostic clock');
 let sequence=0,last=0,dropped=0;const events=[];
 return {
  events,
  get dropped(){return dropped;},
  record(type,context={}){
   if(!eventTypes.has(type))throw Error('Unknown diagnostic operation marker');
   const now=clock()-origin;if(Number.isFinite(now))last=Math.max(last,now);
   const event={sequence:++sequence,elapsed_ms:Math.round(last*1000)/1000,type,phase:safePhase(context.phase),pathname:safePath(context.url)};
   if(type==='http-response'){
    event.method=['GET','POST'].includes(context.method)?context.method:'OTHER';
    event.status=Number.isInteger(context.status)&&context.status>=100&&context.status<=599?context.status:null;
    event.assetFamily=assetFamily(context.url);
   }
   if(events.length<limit)events.push(event);else dropped++;
   return event; // First-error metadata remains available even after the event cap.
  },
 };
}
