import {readFileSync,writeFileSync,unlinkSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
const temp='scripts/.k5-preservation-runner.mjs';
const source=readFileSync('scripts/verify-g-int-007-preservation.mjs','utf8')
 .replaceAll('phase-3d7-g-int007/preservation','phase-3d7-k5-remediation/preservation')
 .replaceAll('.gint007-preservation-current.mjs','.k5-preservation-current.mjs');
try{writeFileSync(temp,source);execFileSync(process.execPath,[temp],{stdio:'inherit'});}finally{unlinkSync(temp);}
