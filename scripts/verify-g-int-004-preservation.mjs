// Reuse existing assertions; route only new artifacts to this checkpoint so
// earlier verification evidence is not overwritten. Runs serially on isolated fixtures.
import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync,unlinkSync,mkdirSync} from 'node:fs';
const root='docs/screenshots/phase-3d7-g-int004/preservation';mkdirSync(root,{recursive:true});
const cases=[
 ['reproduce-applicant-answer-race.mjs','phase-3d7-c'],
 ['verify-applicant-answer-matrix.mjs','phase-3d7-c'],
 ['reproduce-builder-selection-integrity.mjs','phase-3d7-e'],
 ['verify-builder-selection-matrix.mjs','phase-3d7-e'],
 ['verify-builder-journey.mjs','phase-3d7-e','scripts/Prepare-BuilderJourney.php'],
 ['verify-chedro-journey.mjs','phase-3d7-d','scripts/Prepare-ChedroJourney.php'],
];
const results=[];
for(const [file,old,prepare] of cases){
 const dest=root+'/'+file.replace('.mjs','');mkdirSync(dest,{recursive:true});
 const temp='scripts/.gint004-preservation-current.mjs';
 try{if(prepare)execFileSync('php',[prepare,'prepare'],{stdio:'pipe'});
 const source=readFileSync('scripts/'+file,'utf8').replaceAll('docs/screenshots/'+old,dest);
 writeFileSync(temp,source);execFileSync(process.execPath,[temp],{stdio:'inherit'});results.push({file,passed:true});
 }catch(e){results.push({file,passed:false,message:String(e).slice(0,500)});throw e;
 }finally{unlinkSync(temp);writeFileSync(root+'/results.json',JSON.stringify(results,null,2));}
}
