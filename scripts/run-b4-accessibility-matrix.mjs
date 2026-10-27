import fs from 'node:fs';
import {execFileSync} from 'node:child_process';

const final=process.argv[2]==='final';
const privateDir='storage/app/private/b4-accessibility-remediation'+(final?'/final':'');
const driver=final?'scripts/rehearse-b4-accessibility-final.mjs':'scripts/rehearse-b4-accessibility.mjs';
const phases=['fresh-author','approve','publish','applicant','correction','resubmit','evaluation','chief','director','supplementary','approve','definitions','publish-definitions','issuance','document-config','publish-document-config','generate-download'];
const progress=[];
for(let i=0;i<phases.length;i++){
 const phase=phases[i];
 console.log('Starting '+i+' '+phase);
 try{
  const output=execFileSync(process.execPath,[driver,phase],{encoding:'utf8',timeout:900000,maxBuffer:4000000});
  fs.writeFileSync(privateDir+'/phase-'+i+'-'+phase+'.txt',output);
  progress.push({phase,index:i,status:'PASS'});
 }catch(error){
  fs.writeFileSync(privateDir+'/phase-'+i+'-'+phase+'-failure.txt',String(error.stdout??'')+'\n'+String(error.stderr??''));
  progress.push({phase,index:i,status:'FAIL'});
  fs.writeFileSync(privateDir+'/matrix-progress.json',JSON.stringify(progress,null,2));
  throw error;
 }
 fs.writeFileSync(privateDir+'/matrix-progress.json',JSON.stringify(progress,null,2));
 console.log('Completed '+i+' '+phase);
}
