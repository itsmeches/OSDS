import {readFileSync,writeFileSync,unlinkSync} from 'node:fs';
import {execFileSync} from 'node:child_process';

// Existing seven journeys/assertions, serial fixtures, separate evidence.
const temp='scripts/.k1-preservation-runner.mjs';
const source=readFileSync('scripts/verify-g-int-007-preservation.mjs','utf8')
 .replaceAll('phase-3d7-g-int007/preservation','phase-3d7-k1-remediation/preservation')
 .replaceAll('.gint007-preservation-current.mjs','.k1-preservation-current.mjs');
try{writeFileSync(temp,source);execFileSync(process.execPath,[temp],{stdio:'inherit'});}finally{unlinkSync(temp);}
