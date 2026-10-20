// Existing recovery assertions, serialized fixtures, separate final evidence.
import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync,unlinkSync,mkdirSync} from 'node:fs';
const root='docs/screenshots/phase-3d7-g-final/recovery';mkdirSync(root,{recursive:true});
const cases=[
 ['verify-applicant-journey.mjs','phase-3d7-c','scripts/Prepare-ApplicantJourney.php'],
 ['verify-builder-recovery.mjs','phase-3d7-e'],
 ['verify-chedro-journey.mjs','phase-3d7-d','scripts/Prepare-ChedroJourney.php'],
 ['verify-staff-journey.mjs','phase-3d7-f'],
];
const results=[];
for(const [file,old,prepare] of cases){
 const dest=root+'/'+file.replace('.mjs','');mkdirSync(dest,{recursive:true});const temp='scripts/.batch-g-recovery-current.mjs';
 try{if(prepare)execFileSync('php',[prepare,'prepare'],{stdio:'pipe'});
 writeFileSync(temp,readFileSync('scripts/'+file,'utf8').replaceAll('docs/screenshots/'+old,dest));
 execFileSync(process.execPath,[temp],{stdio:'inherit'});results.push({file,passed:true});
 }catch(e){results.push({file,passed:false,message:String(e).slice(0,700)});throw e;}
 finally{unlinkSync(temp);writeFileSync(root+'/results.json',JSON.stringify(results,null,2));}
}
