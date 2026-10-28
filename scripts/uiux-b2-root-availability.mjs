// Diagnostic only. No acceptance, navigation, polling, persistence or auth state.
// Self-contained so Playwright may serialize this function in a reviewed caller.
export function inspectRootAvailability({phase} = {}) {
  if (!['AFTER_ADMIN_DESTINATION', 'DASHBOARD_FAILURE'].includes(phase)) throw Error('Invalid observation phase');
  const result = {
    idRoots: 0, snapshotRoots: 0, idRootsWithoutSnapshot: 0,
    dashboardSnapshots: 0, loginSnapshots: 0, otherSnapshots: 0,
    malformed: false, readyState: 'unavailable', destinationMatch: false,
    fatalEvent: false, observationAvailable: false, phase,
  };
  try {
    const ids = document.querySelectorAll('[wire\\:id]');
    const snapshots = document.querySelectorAll('[wire\\:snapshot]');
    const cap = count => Math.min(count, 2);
    result.idRoots = cap(ids.length);
    result.snapshotRoots = cap(snapshots.length);
    for (const node of ids) if (!node.hasAttribute('wire:snapshot')) result.idRootsWithoutSnapshot = cap(result.idRootsWithoutSnapshot + 1);
    for (const node of snapshots) {
      try {
        const name = JSON.parse(node.getAttribute('wire:snapshot'))?.memo?.name;
        if (typeof name !== 'string') {result.malformed = true; continue;}
        const key = name === 'App\\Filament\\Pages\\Dashboard' ? 'dashboardSnapshots'
          : name === 'Filament\\Auth\\Pages\\Login' ? 'loginSnapshots' : 'otherSnapshots';
        result[key] = cap(result[key] + 1);
      } catch {result.malformed = true;}
    }
    result.readyState = ['loading', 'interactive', 'complete'].includes(document.readyState) ? document.readyState : 'unavailable';
    result.destinationMatch = location.origin === 'http://127.0.0.1:8044' && location.pathname === '/admin';
    result.observationAvailable = true;
    return result;
  } catch {
    // Discard any partial sample and all exception text.
    return {idRoots: 0, snapshotRoots: 0, idRootsWithoutSnapshot: 0, dashboardSnapshots: 0,
      loginSnapshots: 0, otherSnapshots: 0, malformed: false, readyState: 'unavailable',
      destinationMatch: false, fatalEvent: false, observationAvailable: false, phase};
  }
}

export function createRootAvailabilityObserver({page, hasFailed}) {
  const seen = new Set();
  const empty = phase => ({idRoots: 0, snapshotRoots: 0, idRootsWithoutSnapshot: 0,
    dashboardSnapshots: 0, loginSnapshots: 0, otherSnapshots: 0, malformed: false,
    readyState: 'unavailable', destinationMatch: false, fatalEvent: false, observationAvailable: false, phase});
  const counts = ['idRoots', 'snapshotRoots', 'idRootsWithoutSnapshot', 'dashboardSnapshots', 'loginSnapshots', 'otherSnapshots'];
  const booleans = ['malformed', 'destinationMatch', 'fatalEvent', 'observationAvailable'];
  return async phase => {
    if (!['AFTER_ADMIN_DESTINATION', 'DASHBOARD_FAILURE'].includes(phase) || seen.has(phase)) throw Error('Invalid or repeated observation phase');
    seen.add(phase); // A failed observation is not retried.
    if (hasFailed()) return {...empty(phase), fatalEvent: true};
    let record;
    try {
      const value = await page.evaluate(inspectRootAvailability, {phase});
      const expectedKeys = [...counts, ...booleans, 'readyState', 'phase'].sort().join();
      if (!value || Object.keys(value).sort().join() !== expectedKeys || value.phase !== phase
          || counts.some(key => ![0, 1, 2].includes(value[key]))
          || booleans.some(key => typeof value[key] !== 'boolean')
          || !['loading', 'interactive', 'complete', 'unavailable'].includes(value.readyState)) throw Error('Invalid observation representation');
      record = {...value};
      if (!record.observationAvailable) record = empty(phase);
    } catch {record = empty(phase);}
    // Node's existing fatal flag is authoritative, including events during evaluate.
    record.fatalEvent = Boolean(hasFailed());
    return record;
  };
}
