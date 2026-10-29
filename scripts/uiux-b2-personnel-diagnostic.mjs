import {componentSnapshots} from './uiux-b2-reviewer-policy.mjs';

// Isolated diagnostic candidate. No navigation, authentication or persistence API.
export function createPersonnelDiagnostic({page,expect,base,onRecord=()=>{}}) {
  const records=[];let used=false,persistenceFailed=false;
  function emit(boundary,state) {
    const record=Object.freeze({boundary,state,passed:state==='PASS'});
    records.push(record);
    try{onRecord(record);}catch{persistenceFailed=true;throw Error('Personnel diagnostic persistence blocked');}
  }
  async function check(boundary,operation) {
    emit(boundary,'ENTERED');
    try{await operation();}
    catch(error){try{emit(boundary,'FAIL');}catch{}throw error;}
    emit(boundary,'PASS');
  }
  return {
    get records(){return [...records];},
    get persistenceFailed(){return persistenceFailed;},
    run:async()=>{
      if(used)throw Error('Personnel diagnostic already used');used=true;
      await check('PERSONNEL_URL',async()=>{
        await expect(page).toHaveURL(base+'/admin/case-demonstration-documents');
      });
      await check('PERSONNEL_COMPONENT',async()=>{
        const snapshots=await page.locator('[wire\\:snapshot]').evaluateAll(es=>es.map(e=>e.getAttribute('wire:snapshot')));
        componentSnapshots(snapshots);
      });
      for(const id of [47,48]){
        await check(id===47?'COR_47_HEADING':'ACSA_48_HEADING',async()=>{
          const heading=page.getByRole('heading',{name:'Personnel #'+id+' · version 1 · draft',exact:true});
          await expect(heading).toHaveCount(1);
        });
      }
    },
  };
}
