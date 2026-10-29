import assert from 'node:assert/strict';import fs from 'node:fs';
import {createPersonnelDiagnostic} from './uiux-b2-personnel-diagnostic.mjs';
let checks=0;const eq=(a,b)=>{assert.deepEqual(a,b);checks++;};
const boundaries=['PERSONNEL_URL','PERSONNEL_COMPONENT','COR_47_HEADING','ACSA_48_HEADING'];
const source=fs.readFileSync('scripts/verify-uiux-b2-attempt-14.mjs','utf8');
eq(source.includes("await expect(page).toHaveURL(base+'/admin/case-demonstration-documents');"),true);
eq(source.includes("name:'Personnel #'+v.id+' · version 1 · draft',exact:true"),true);
function fixture(fail,writer=()=>{},variant='missing'){
 const calls=[],original=Error('PRIVATE_SYNTHETIC_ERROR');
 const page={locator:selector=>{eq(selector,'[wire\\:snapshot]');return {evaluateAll:async()=>{
   calls.push('PERSONNEL_COMPONENT');if(fail==='PERSONNEL_COMPONENT')return variant==='malformed'?['{']:variant==='duplicate'?Array(2).fill(JSON.stringify({memo:{name:'App\\Filament\\Pages\\CaseDemonstrationDocuments'}})):[];
   return [JSON.stringify({memo:{name:'App\\Filament\\Pages\\CaseDemonstrationDocuments'},data:{private:'PRIVATE_SYNTHETIC_PAYLOAD'}})];
 }};},getByRole:(role,options)=>{eq(role,'heading');eq(options.exact,true);eq(options.name,/^Personnel #(?:47|48) · version 1 · draft$/[Symbol.match](options.name)?.[0]);return {id:options.name.includes('#47')?47:48};}};
 const expect=target=>target===page?{toHaveURL:async url=>{eq(url,'http://synthetic.invalid/admin/case-demonstration-documents');calls.push('PERSONNEL_URL');if(fail==='PERSONNEL_URL')throw original;}}:{toHaveCount:async count=>{eq(count,1);const b=target.id===47?'COR_47_HEADING':'ACSA_48_HEADING';calls.push(b);if(fail===b)throw original;}};
 return {d:createPersonnelDiagnostic({page,expect,base:'http://synthetic.invalid',onRecord:writer}),calls,original};
}
const success=fixture();await success.d.run();eq(success.calls,boundaries);eq(success.d.records.length,8);eq(success.d.records.filter(r=>r.state==='PASS').map(r=>r.boundary),boundaries);
await assert.rejects(success.d.run());checks++;eq(success.d.records.length,8);
for(const [index,boundary] of boundaries.entries()){
 const x=fixture(boundary);let error;try{await x.d.run();}catch(e){error=e;}
 eq(Boolean(error),true);if(boundary!=='PERSONNEL_COMPONENT')eq(error,x.original);
 eq(x.calls,boundaries.slice(0,index+1));eq(x.d.records.at(-1),{boundary,state:'FAIL',passed:false});
 eq(x.d.records.filter(r=>r.state==='PASS').length,index);eq(x.d.records.length,(index+1)*2);
 eq(JSON.stringify(x.d.records).includes('PRIVATE_'),false);
 for(const r of x.d.records){eq(Object.keys(r),['boundary','state','passed']);eq(boundaries.includes(r.boundary),true);eq(['ENTERED','PASS','FAIL'].includes(r.state),true);eq(typeof r.passed,'boolean');}
}
for(const variant of ['malformed','duplicate']){const x=fixture('PERSONNEL_COMPONENT',()=>{},variant);await assert.rejects(x.d.run());checks++;eq(x.d.records.at(-1).boundary,'PERSONNEL_COMPONENT');eq(x.calls.length,2);}
const writeFail=fixture('COR_47_HEADING',r=>{if(r.state==='FAIL')throw Error('PRIVATE_WRITE');});let caught;try{await writeFail.d.run();}catch(e){caught=e;}
eq(caught,writeFail.original);eq(writeFail.d.persistenceFailed,true);eq(writeFail.d.records.at(-1).state,'FAIL');
const enteredFail=fixture(undefined,()=>{throw Error('PRIVATE_WRITE');});await assert.rejects(enteredFail.d.run());checks++;eq(enteredFail.calls.length,0);eq(enteredFail.d.persistenceFailed,true);
const passFail=fixture(undefined,r=>{if(r.state==='PASS')throw Error('PRIVATE_WRITE');});await assert.rejects(passFail.d.run());checks++;eq(passFail.calls,['PERSONNEL_URL']);eq(passFail.d.persistenceFailed,true);
const copy=success.d.records;copy.length=0;eq(success.d.records.length,8);eq(Object.isFrozen(success.d.records[0]),true);
console.log(JSON.stringify({status:'PASS',checks,boundaries:4,negativeCases:9,scope:'offline synthetic dependencies; no browser/application/database'}));
