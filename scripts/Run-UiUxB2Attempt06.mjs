// Future execution entry point. Source-only preparation must use --check, never run.
import fs from 'node:fs';
import path from 'node:path';
import net from 'node:net';
import crypto from 'node:crypto';
import {spawn} from 'node:child_process';
import {provisionAttempt} from './uiux-b2-attempt-files.mjs';
const root=process.cwd(),dir='storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-06';
if(process.argv.length!==3||process.argv[2]!=='run')throw Error('Only the fixed run operation exists');
const historical='storage/app/private/uiux-consolidated-acceptance/gate-b2-ui-personnel/identities.jsonl';
const hash=()=>crypto.createHash('sha256').update(fs.readFileSync(historical)).digest('hex').toUpperCase();
const expected='5152846D0C16625BB743F1BA06E6FC233AF842D4FBC2BF53EA5483C72EF7064B';
if(hash()!==expected)throw Error('Historical identity log differs before execution');
for(const pin of JSON.parse(fs.readFileSync('scripts/uiux-b2-attempt-06-pins.json')).sources)if(crypto.createHash('sha256').update(fs.readFileSync(pin.path)).digest('hex')!==pin.sha256)throw Error('Attempt 06 source changed');
const provisioned=provisionAttempt(path.dirname(dir),'attempt-06');
fs.writeFileSync(dir+'/reserved.json',JSON.stringify({attempt:'b2-reviewer-only-attempt-06',started:new Date().toISOString(),historical_sha256:expected,sessions:provisioned.sessions}),{flag:'wx'});
const php=path.join(process.env.TEMP,'osds-phase1-tools/php/php.exe');
const node=path.join(process.env.TEMP,'osds-phase1-tools/node/node-v24.20.0-win-x64/node.exe');
let server;
const child=(exe,args,label)=>new Promise((resolve,reject)=>{
 const log=fs.openSync(dir+'/'+label+'.txt','wx');const p=spawn(exe,args,{cwd:root,stdio:['ignore',log,log]});
 p.once('error',reject);p.once('exit',code=>{fs.closeSync(log);resolve(code);});
});
const freePort=()=>new Promise((resolve,reject)=>{const probe=net.createServer();probe.once('error',reject);probe.listen(8044,'127.0.0.1',()=>probe.close(resolve));});
const listening=()=>new Promise((resolve,reject)=>{
 const deadline=Date.now()+10000;
 function probe(){const s=net.connect(8044,'127.0.0.1');s.once('connect',()=>{s.destroy();resolve();});s.once('error',()=>{s.destroy();if(Date.now()>deadline)reject(Error('Server did not listen'));else setTimeout(probe,100);});}probe();
});
const result={status:'BLOCKED'};
try{
 await freePort();
 if(await child(php,['scripts/Inspect-UiUxB2Attempt06.php','preflight'],'preflight-command')!==0)throw Error('Preflight blocked');
 const log=fs.openSync(dir+'/server.txt','wx');server=spawn(php,['-S','127.0.0.1:8044','-t','public','scripts/uiux-b2-attempt-06-server.php'],{cwd:root,stdio:['ignore',log,log]});
 server.once('error',()=>{});await listening();
 try{result.browser_exit=await child(node,['scripts/verify-uiux-b2-attempt-06.mjs'],'browser-command');}
 finally{
  if(server&&server.exitCode===null){await new Promise(resolve=>{server.once('exit',resolve);server.kill();});}fs.closeSync(log);server=null;
  result.postflight_exit=await child(php,['scripts/Inspect-UiUxB2Attempt06.php','postflight'],'postflight-command');
 }
 result.status=result.browser_exit===0&&result.postflight_exit===0?'PASS':'BLOCKED';
}catch(error){result.error=error.message;}
finally{
 if(server&&server.exitCode===null)server.kill();
 result.historical_log_unchanged=hash()===expected;if(!result.historical_log_unchanged)result.status='FAIL';
 fs.writeFileSync(dir+'/launcher-result.json',JSON.stringify(result,null,2),{flag:'wx'});
}
console.log(JSON.stringify(result));if(result.status!=='PASS')process.exitCode=1;
