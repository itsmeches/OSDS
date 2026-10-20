// Existing assertions, isolated fixtures, new evidence paths; serialized runs.
import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync,unlinkSync,mkdirSync} from 'node:fs';
const staffRetry=process.argv.includes('--staff-retry');
const root='docs/screenshots/phase-3d7-g-int006/preservation'+(staffRetry?'/staff-retry':'');mkdirSync(root,{recursive:true});
const cases=[
 ['reproduce-applicant-answer-race.mjs','phase-3d7-c'],
 ['verify-applicant-answer-matrix.mjs','phase-3d7-c'],
 ['reproduce-builder-selection-integrity.mjs','phase-3d7-e'],
 ['verify-builder-selection-matrix.mjs','phase-3d7-e'],
 ['verify-builder-journey.mjs','phase-3d7-e','scripts/Prepare-BuilderJourney.php'],
 ['verify-chedro-journey.mjs','phase-3d7-d','scripts/Prepare-ChedroJourney.php'],
 ['verify-staff-journey.mjs','phase-3d7-f'],
];
const results=[];
for(const [file,old,prepare] of cases){
 if(staffRetry&&file!=='verify-staff-journey.mjs')continue;
 const dest=root+'/'+file.replace('.mjs','');mkdirSync(dest,{recursive:true});const temp='scripts/.gint006-preservation-current.mjs';
 try{if(prepare)execFileSync('php',[prepare,'prepare'],{stdio:'pipe'});
 let source=readFileSync('scripts/'+file,'utf8').replaceAll('docs/screenshots/'+old,dest);
 if(staffRetry)source=source.replace("await p.locator('.fi-no-notification-close-btn').evaluateAll(es=>es.forEach(e=>e.click()));await expect(p.locator('.fi-no-notification')).toHaveCount(0);", "await expect.poll(async()=>{await p.locator('.fi-no-notification-close-btn').evaluateAll(es=>es.forEach(e=>e.click()));return p.locator('.fi-no-notification').count();},{timeout:15000}).toBe(0);");
 writeFileSync(temp,source);
 execFileSync(process.execPath,[temp],{stdio:'inherit'});results.push({file,passed:true});
 }catch(e){results.push({file,passed:false,message:String(e).slice(0,700)});throw e;}
 finally{unlinkSync(temp);writeFileSync(root+'/results.json',JSON.stringify(results,null,2));}
}
