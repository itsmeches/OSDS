import {verifySharedDashboardCandidate} from './uiux-b2-shared-dashboard-readiness.mjs';

// Diagnostics participate in the existing budget; they create no separate wait.
export async function verifyAttempt14Navigation({observeRoots, persist, mark, ...options}) {
  return verifySharedDashboardCandidate({...options, persist: async record => {
    if (record.phase === 'DESTINATION_MATCHED') {
      mark('navigation-check-complete');
      await observeRoots('AFTER_ADMIN_DESTINATION');
      mark('auth-link-check-start');
    }
    if (record.phase === 'FAILURE' && ['DASHBOARD_METADATA', 'HEADING'].includes(record.stage)) {
      try {await observeRoots('DASHBOARD_FAILURE');}
      catch {options.result.rootEvidencePersistenceFailed = true; options.result.status = 'BLOCKED';}
    }
    await persist(record);
  }});
}
