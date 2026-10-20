import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync,mkdirSync,unlinkSync} from 'node:fs';
const root='docs/screenshots/phase-3d7-g-final/builder-timing';mkdirSync(root,{recursive:true});
const source=readFileSync('scripts/verify-builder-selection-matrix.mjs','utf8');
const results=[];
for(const hold of [0,250,1000]){
 const dir=root+'/hold-'+hold;mkdirSync(dir,{recursive:true});const temp='scripts/.batch-g-builder-timing.mjs';
 let s=source.replaceAll('docs/screenshots/phase-3d7-e',dir).replace('if(from<=14){','if(false){');
 const needle="release();await expect(page.locator('#question-dependent')).toHaveAttribute('aria-pressed','true',{timeout:15000});";
 if(!s.includes(needle))throw Error('Original held-model assertion changed');
 s=s.replace(needle,`await new Promise(r=>setTimeout(r,${hold}));const releasedAt=performance.now();release();await expect(page.locator('#question-dependent')).toHaveAttribute('aria-pressed','true',{timeout:15000});(result.timing??=[]).push({kind:'model-held',hold_ms:${hold},selection_after_release_ms:Math.round(performance.now()-releasedAt)});`);
 s=s.replace('release();await page.unrouteAll',`await new Promise(r=>setTimeout(r,${hold}));release();await page.unrouteAll`);
 const select="await page.locator('#question-dependent').click();await expect(page.getByLabel('Question title',{exact:true})).toHaveValue('Conditional information');";
 if(!s.includes(select))throw Error('Original held-save selection assertion changed');
 s=s.replace(select,`const selectedAt=performance.now();${select}(result.timing??=[]).push({kind:'save-held',hold_ms:${hold},selection_ms:Math.round(performance.now()-selectedAt)});`);
 const start=Date.now();writeFileSync(temp,s);
 try{execFileSync(process.execPath,[temp,'--from=12'],{stdio:'inherit'});results.push({hold_ms:hold,passed:true,total_ms:Date.now()-start});}
 catch(e){results.push({hold_ms:hold,passed:false,total_ms:Date.now()-start,message:String(e).slice(0,600)});throw e;}
 finally{unlinkSync(temp);writeFileSync(root+'/results.json',JSON.stringify(results,null,2));}
}
