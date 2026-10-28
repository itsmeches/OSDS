// Pure schema/analysis only. No browser hooks, globals, I/O or attempt paths.
const definitions = Object.freeze({
  LOADER_OBSERVED: { installed: 'boolean' },
  WRAPPER_OBSERVED: { count: 'count', state: 'state', initialized: 'boolean' },
  IMPORT_STARTED: {},
  IMPORT_SETTLED: { outcome: 'outcome' },
  PROVIDER_REGISTERED: {},
  WRAPPER_INITIALIZED: {},
  MORPH_STARTED: {},
  CLONE_OBSERVED: { sourceInitialized: 'boolean', awaiting: 'boolean', transferredState: 'boolean' },
  FIRST_PAGE_ERROR: { category: 'category' },
});
const enums = {
  state: ['init', 'await', 'loaded', 'unknown'],
  outcome: ['success', 'failure'],
  category: ['PASSWORD_REVEAL_FACTORY_UNDEFINED', 'RESTRICTED'],
};
export function createInitializationObservations() {
  const events = [];
  let firstError = false;
  return Object.freeze({
    record(type, fields = {}) {
      if (!Object.hasOwn(definitions, type) || fields === null || typeof fields !== 'object' || Array.isArray(fields)) throw Error('Invalid observation');
      const schema = definitions[type];
      if (Object.keys(fields).length !== Object.keys(schema).length || Object.keys(fields).some(k => !Object.hasOwn(schema, k))) throw Error('Invalid observation fields');
      const safe = {};
      for (const [key, kind] of Object.entries(schema)) {
        const value = fields[key];
        if (kind === 'boolean' ? typeof value !== 'boolean' : kind === 'count' ? !Number.isInteger(value) || value < 0 || value > 2 : !enums[kind].includes(value)) throw Error('Invalid observation value');
        safe[key] = value;
      }
      if (type === 'FIRST_PAGE_ERROR' && firstError) return false;
      if (events.length === 64) throw Error('Observation capacity exceeded');
      if (type === 'FIRST_PAGE_ERROR') firstError = true;
      events.push(Object.freeze({ sequence: events.length + 1, type, ...safe }));
      return true;
    },
    snapshot() { return events.map(e => ({ ...e })); },
    assess() {
      const index = type => events.findIndex(e => e.type === type);
      const registration = index('PROVIDER_REGISTERED');
      const error = events.find(e => e.type === 'FIRST_PAGE_ERROR');
      const factoryError = error?.category === 'PASSWORD_REVEAL_FACTORY_UNDEFINED';
      const failure = events.find(e => e.type === 'IMPORT_SETTLED' && e.outcome === 'failure');
      const success = events.find(e => e.type === 'IMPORT_SETTLED' && e.outcome === 'success');
      const initialized = index('WRAPPER_INITIALIZED');
      const clones = events.filter(e => e.type === 'CLONE_OBSERVED');
      // Findings describe supplied observations, never server commits or proven causes.
      return {
        pendingCloneObserved: clones.some(e => e.awaiting && !e.sourceInitialized && !e.transferredState && (registration < 0 || e.sequence < registration + 1)),
        initializedCloneObserved: clones.some(e => e.sourceInitialized && e.transferredState && initialized >= 0 && e.sequence > initialized + 1),
        importFailureWithoutRegistration: Boolean(failure && registration < 0),
        registeredWithoutObservedClone: Boolean(success && registration >= 0 && success.sequence < registration + 1 && initialized > registration && clones.length === 0),
        undefinedObservedAfterRegistration: Boolean(factoryError && registration >= 0 && error.sequence > registration + 1),
        causalConclusion: 'UNRESOLVED',
      };
    },
  });
}
