// Verification-only, no I/O, browser launch or authentication.
export const loginRuntimePins = Object.freeze({
  laravel: 'v13.35.0', filament: 'v5.10.1', livewire: 'v4.4.7',
  alpine: '3.17.4',
  livewireHash: 'fa03bcdfae501c8ef5c93e525ce0f5a658929accd98758651203c1e242dab3d3',
  supportHash: '31c6fb14c605144dbce0235ee32879d27ac78ddb52a4e5ddc74b12ada548acb3',
  textInputHash: '53c885e033ea8ba24dc8cc3680e3bef3bab0f9bbb77a39a75641b96fda672776',
  loginHash: '944120c5edce2c1b1e2f8fc8c5353a919a270cbeb29252198472aa1df43341ab',
  passwordHash: '3ec263cf243077557155d8ceddc0e1ec3b7073419cb441ae79ea37e5506c8cc0',
});
export function assertLoginRuntimePins(observed) {
  if (!observed || Object.keys(observed).length !== Object.keys(loginRuntimePins).length ||
      Object.entries(loginRuntimePins).some(([key, value]) => observed[key] !== value)) throw Error('Login readiness runtime changed');
}
export function loginReadiness(meta) {
  const keys = ['loginCount', 'wrapperCount', 'connected', 'sameComponent', 'sameWrapper', 'loader', 'ignored', 'initialized', 'revealPresent', 'revealBoolean', 'alpineVersion'];
  if (!meta || Object.keys(meta).length !== keys.length || keys.some(key => !Object.hasOwn(meta, key))) return 'INVALID';
  if (meta.loginCount !== 1 || meta.wrapperCount !== 1 || meta.alpineVersion !== loginRuntimePins.alpine ||
      ['connected', 'sameComponent', 'sameWrapper', 'ignored', 'initialized', 'revealPresent', 'revealBoolean'].some(key => typeof meta[key] !== 'boolean') ||
      !meta.connected || !meta.sameComponent || !meta.sameWrapper || !['init', 'await', 'loaded'].includes(meta.loader)) return 'INVALID';
  if (meta.loader !== 'loaded') return 'PENDING';
  return !meta.ignored && meta.initialized && meta.revealPresent && meta.revealBoolean ? 'READY' : 'INVALID';
}

// Serialized by Playwright. Only capture returns opaque DOM-node references via
// evaluateHandle; sampling returns this closed metadata, never component state.
export function inspectLoginReadiness({ nodes = null, mode = 'sample' } = {}) {
  const roots = [...document.querySelectorAll('[wire\\:snapshot]')].filter(el => {
    try { return JSON.parse(el.getAttribute('wire:snapshot')).memo?.name === 'Filament\\Auth\\Pages\\Login'; }
    catch { throw Error('Invalid login component metadata'); }
  });
  if (roots.length !== 1) throw Error('Login component count changed');
  const wrappers = [...roots[0].querySelectorAll('[x-data]')].filter(el => el.getAttribute('x-data') === 'passwordRevealFormComponent()');
  if (wrappers.length !== 1) throw Error('Login wrapper count changed');
  if (mode === 'capture') return { component: roots[0], wrapper: wrappers[0] };
  const wrapper = wrappers[0], scope = Array.isArray(wrapper._x_dataStack) ? wrapper._x_dataStack[0] : null;
  if (wrapper._x_ignore !== undefined && typeof wrapper._x_ignore !== 'boolean') throw Error('Unknown login ignore flag');
  const meta = {
    loginCount: 1, wrapperCount: 1,
    connected: Boolean(wrapper.isConnected && roots[0].isConnected),
    sameComponent: Boolean(nodes && nodes.component === roots[0]),
    sameWrapper: Boolean(nodes && nodes.wrapper === wrapper),
    loader: ['init', 'await', 'loaded'].includes(wrapper._x_async) ? wrapper._x_async : 'unknown',
    ignored: wrapper._x_ignore !== false && wrapper._x_ignore !== undefined,
    initialized: Boolean(scope && typeof scope === 'object'),
    revealPresent: Boolean(scope && Object.hasOwn(scope, 'isPasswordRevealed')),
    revealBoolean: Boolean(scope && Object.hasOwn(scope, 'isPasswordRevealed') && typeof scope.isPasswordRevealed === 'boolean'),
    alpineVersion: window.Alpine?.version === '3.17.4' ? '3.17.4' : 'unknown',
  };
  if (mode === 'sample') return meta;
  if (mode !== 'wait' || !meta.connected || !meta.sameComponent || !meta.sameWrapper || meta.loader === 'unknown' || meta.alpineVersion === 'unknown') throw Error('Login readiness identity changed');
  // Wait only for the installed loader's post-init marker. Node then validates
  // the complete predicate before permitting any credential/action step.
  return meta.loader === 'loaded';
}

export async function requireLoginReadiness({ sample, waitUntilLoaded, failed, hasFailed }) {
  const assertNoFailure = () => { if (hasFailed()) throw Error('Login readiness aborted'); };
  assertNoFailure();
  const race = task => Promise.race([task, failed]);
  let status = loginReadiness(await race(sample()));
  assertNoFailure();
  if (status === 'PENDING') {
    await race(waitUntilLoaded());
    assertNoFailure();
    status = loginReadiness(await race(sample()));
  }
  assertNoFailure();
  if (status !== 'READY') throw Error('Login component not ready');
}
