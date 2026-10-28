// Pure filesystem provisioning; no process, framework, browser or database imports.
import fs from 'node:fs';
import path from 'node:path';

export function provisionAttempt(parent, name, io=fs){
 if(!/^attempt-\d{2}$/.test(name))throw Error('Invalid fixed attempt name');
 const absolute=path.resolve(parent);
 io.mkdirSync(absolute,{recursive:true,mode:0o700});
 const realParent=io.realpathSync(absolute);
 const equal=(a,b)=>process.platform==='win32'?a.toLowerCase()===b.toLowerCase():a===b;
 if(!equal(absolute,realParent))throw Error('Attempt parent resolves outside its explicit path');
 const attempt=path.join(realParent,name);
 io.mkdirSync(attempt,{mode:0o700}); // Atomic; existing directories are never reused.
 const realAttempt=io.realpathSync(attempt);
 if(!equal(realAttempt,attempt))throw Error('Attempt root path escape');
 const sessions=path.join(attempt,'sessions');
 io.mkdirSync(sessions,{mode:0o700});
 const realSessions=io.realpathSync(sessions);
 if(!equal(realSessions,sessions)||path.relative(realAttempt,realSessions)!=='sessions')throw Error('Session path escape');
 io.accessSync(realSessions,fs.constants.W_OK);
 // Actual exclusive write verifies effective access, including Windows ACLs.
 const probe=path.join(realSessions,'.provision-check');
 const handle=io.openSync(probe,'wx',0o600);
 try{io.writeFileSync(handle,'session provisioning check');}finally{io.closeSync(handle);}
 io.unlinkSync(probe);
 return {attempt:realAttempt,sessions:realSessions};
}
