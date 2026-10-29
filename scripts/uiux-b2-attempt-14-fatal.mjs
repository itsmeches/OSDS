import {createFirstFatalCandidate} from './uiux-b2-first-fatal-candidate.mjs';

// Attempt-specific persistence orchestration; the validated latch is unchanged.
export function createAttempt14Fatal({signal,context,clock,result,writeFirst}) {
  const attribution=createFirstFatalCandidate({signal,context,clock});
  let attempted=false,pending;
  const snapshot=()=>{
    if(attribution.first)result.firstFatal=attribution.first;
    result.fatalObservations=attribution.events;
  };
  const persistOnce=()=>{
    if(attempted||!attribution.first)return;
    attempted=true;
    pending=attribution.persistFirst(writeFirst).then(written=>{
      if(!written){result.firstFatalEvidencePersistenceFailed=true;result.status='BLOCKED';}
    });
  };
  return {
    fatal:(reason,error)=>{
      attribution.fatal(reason,error); // Latch precedes the original fatal signal.
      snapshot();
      persistOnce(); // Begins on this fatal invocation, before returning to its producer.
    },
    startCleanup:()=>attribution.startCleanup(),
    settle:async()=>{
      if(pending)await pending;
      snapshot();
      if(result.firstFatalEvidencePersistenceFailed)result.status='BLOCKED';
    },
  };
}
