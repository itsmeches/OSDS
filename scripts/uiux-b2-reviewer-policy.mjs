// Pure policy: importable without Playwright, credentials, HTTP or database access.
export const pageName='App\\Filament\\Pages\\CaseDemonstrationDocuments';
export function validateRequest(body,phase){
 if(!Array.isArray(body?.components)||body.components.length!==1)throw Error('Expected one component');
 const c=body.components[0],s=JSON.parse(c.snapshot);
 if(!s?.memo?.id||typeof s.memo.name!=='string'||!Array.isArray(c.calls)||c.calls.length!==1)throw Error('Invalid snapshot/calls');
 const call=c.calls[0];
 if(s.memo.name==='Filament\\Livewire\\Notifications'&&call.method==='__dispatch'&&JSON.stringify(call.params)===JSON.stringify(['notificationsSent',[]])&&Object.keys(c.updates??{}).length===0)return 'notification';
 if(phase==='login'&&s.memo.name==='Filament\\Auth\\Pages\\Login'&&call.method==='authenticate'&&JSON.stringify(call.params)==='[]'&&Object.keys(c.updates??{}).every(k=>['data.email','data.password','data.remember'].includes(k)))return 'login';
 if(phase&&typeof phase==='object'&&s.memo.name===pageName&&call.method==='advance'&&JSON.stringify(call.params)===JSON.stringify([phase.id,phase.lock,phase.to])&&[47,48].includes(phase.id)&&((phase.lock===1&&phase.to==='reviewed')||(phase.lock===2&&phase.to==='published'))&&Object.keys(c.updates??{}).length===0)return 'advance';
 throw Error('Disallowed component/action: '+s.memo.name+'/'+call.method);
}
export function componentSnapshots(values){
 const matches=values.map(v=>JSON.parse(v)).filter(s=>s.memo?.name===pageName);
 if(matches.length!==1)throw Error('Expected exactly one personnel page component');
 return matches[0];
}
