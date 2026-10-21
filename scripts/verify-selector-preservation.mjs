import {readFileSync,writeFileSync,unlinkSync} from 'node:fs';
import {execFileSync} from 'node:child_process';

// Preserve earlier checkpoint evidence. Reuse the existing actual-action journeys
// with their former target-size allowance removed, serially on isolated fixtures.
for(const [file,checkpoint,name] of [
 ['verify-representation-creation.mjs','phase-3d7-g-int007','representation'],
 ['verify-regional-grant-creation.mjs','phase-3d7-g-int006','regional'],
]){
 const source=readFileSync('scripts/'+file,'utf8').split('\n')
  .filter(line=>!line.startsWith("prefix=prefix.replace('expect(axe.violations).toEqual([]);',"))
  .join('\n').replaceAll(checkpoint,'phase-3d7-selector-accessibility/preservation/'+name);
 const temp='scripts/.selector-preservation-current.mjs';
 try{writeFileSync(temp,source);execFileSync(process.execPath,[temp],{stdio:'inherit'});}finally{unlinkSync(temp);}
}
