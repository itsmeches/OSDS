// Isolated verification candidate. No credentials, login, routes or auth flag.
import {inspectDashboardContract} from './uiux-b2-sidebar-navigation.mjs';
import {dashboardMetadataFailure} from './uiux-b2-dashboard-diagnostics-candidate.mjs';

export function createSharedDashboardBudget(clock = () => performance.now()) {
  const start = clock(), deadline = start + 30000;
  return {remaining: () => Math.max(0, deadline - clock()), elapsed: () => Math.max(0, clock() - start)};
}

export async function verifySharedDashboardCandidate({page, expect, base, failed, hasFailed, result, persist, clock}) {
  const budget = createSharedDashboardBudget(clock);
  const state = {stage: 'DESTINATION', lastCondition: 'DESTINATION_PENDING', metadataObserved: false,
    dashboard: 0, login: 0, loginForm: false, malformed: false, rootElapsedMs: 0};
  result.sharedReadiness = state;
  let documentHandle, destinationSeen = false, awayObserved = false, stopped = false, timer;
  const atAdmin = () => {try {const u = new URL(page.url()); return u.origin === base && u.pathname === '/admin';} catch {return false;}};
  const stop = code => {if (!stopped) result.verificationConditionFailure = code; throw Error('Shared dashboard verification blocked');};
  const basic = bounded => {
    if (stopped) throw Error('Shared dashboard verification stopped');
    if (hasFailed()) stop('EXISTING_FATAL_EVENT');
    if (destinationSeen && (awayObserved || !atAdmin())) stop('DESTINATION_CHANGED');
    if (bounded && budget.remaining() <= 0) stop('SHARED_DEADLINE_EXHAUSTED');
  };
  const onNavigation = frame => {if (frame === page.mainFrame() && destinationSeen && !atAdmin()) awayObserved = true;};
  page.on('framenavigated', onNavigation);
  const currentDocument = async bounded => {
    basic(bounded);
    if (documentHandle) {
      let same;
      try {same = await page.evaluate(pinned => document === pinned, documentHandle);}
      catch {basic(bounded); stop('DOCUMENT_CHANGED');}
      basic(bounded);
      if (!same) stop('DOCUMENT_CHANGED');
    }
  };
  const emit = async phase => {
    if (['DESTINATION', 'DASHBOARD_METADATA'].includes(state.stage)) state.rootElapsedMs = Math.min(30000, Math.round(budget.elapsed()));
    try {await persist({phase, ...state, code: result.verificationConditionFailure ?? 'NONE'});}
    catch {result.evidencePersistenceFailed = true; if (!result.verificationConditionFailure) stop('EVIDENCE_PERSISTENCE_FAILED'); throw Error('Shared evidence persistence blocked');}
  };
  const dispose = async () => {page.off('framenavigated', onNavigation); if (documentHandle) {await documentHandle.dispose(); documentHandle = null;}};
  const check = async (code, operation) => {
    try {await currentDocument(false); await Promise.race([operation(), failed]); await currentDocument(false);}
    catch {if (hasFailed()) stop('EXISTING_FATAL_EVENT'); if (!result.verificationConditionFailure) stop(code); throw Error('Shared sidebar verification blocked');}
  };
  const sidebar = page.locator('#fi-main-sidebar');
  const link = sidebar.getByRole('link', {name: 'Case-linked demonstration PDFs', exact: true});
  async function sidebarStage() {
    await check('SIDEBAR_CONDITION_FAILED', async () => {
      await expect(sidebar).toHaveCount(1);
      await expect(link).toHaveCount(1);
      await expect(link).toBeVisible();
      await expect.poll(async () => {
        const href = await link.getAttribute('href');
        try {return href !== null && new URL(href, base).href === base + '/admin/case-demonstration-documents';} catch {return false;}
      }).toBe(true);
      await link.click({trial: true, timeout: 5000});
    });
    return {click: async () => {
      try {
        if (hasFailed() || !atAdmin()) throw Error('Fatal or destination');
        await expect(link).toHaveCount(1);
        const href = await link.getAttribute('href');
        if (href === null || new URL(href, base).href !== base + '/admin/case-demonstration-documents') throw Error('Destination');
        if (hasFailed() || !atAdmin()) throw Error('Fatal or destination');
        await Promise.race([link.click(), failed]);
        if (hasFailed()) throw Error('Fatal');
      } catch {
        result.verificationConditionFailure = hasFailed() ? 'EXISTING_FATAL_EVENT' : 'SIDEBAR_CONDITION_FAILED';
        throw Error('Authorized sidebar navigation blocked');
      }
    }};
  }
  try {
    const expired = new Promise((_, reject) => {timer = setTimeout(() => {
      if (!stopped) result.verificationConditionFailure = 'SHARED_DEADLINE_EXHAUSTED';
      reject(Error('Shared deadline exhausted'));
    }, budget.remaining());});
    const rootStage = async () => {
      basic(true);
      await expect.poll(() => {basic(true); const match = atAdmin(); if (match) destinationSeen = true; return match;}, {timeout: budget.remaining()}).toBe(true);
      basic(true);
      documentHandle = await page.evaluateHandle(() => document);
      await currentDocument(true);
      await emit('DESTINATION_MATCHED');
      await currentDocument(true);
      state.stage = 'DASHBOARD_METADATA';
      basic(true);
      await expect.poll(async () => {
        await currentDocument(true);
        const metadata = await page.evaluate(inspectDashboardContract);
        await currentDocument(true);
        const code = dashboardMetadataFailure(metadata);
        state.lastCondition = code;
        if (code !== 'INVALID_DIAGNOSTIC_METADATA') {
          state.metadataObserved = true;
          for (const key of ['dashboard', 'login', 'loginForm', 'malformed']) state[key] = metadata[key];
        }
        if (!['NONE', 'DASHBOARD_COMPONENT_MISSING'].includes(code)) stop(code);
        return code;
      }, {timeout: budget.remaining()}).toBe('NONE');
      await currentDocument(true);
      await emit('DASHBOARD_READY');
      await currentDocument(true);
    };
    await Promise.race([rootStage(), failed, expired]);
    await currentDocument(true);
    clearTimeout(timer);
    state.rootElapsedMs = Math.min(30000, Math.round(budget.elapsed()));
    state.stage = 'HEADING'; state.lastCondition = 'HEADING_PENDING';
    const heading = page.getByRole('heading', {name: 'Your workspace', exact: true, level: 1, includeHidden: true});
    await Promise.race([expect.poll(async () => {
      await currentDocument(false);
      const count = await heading.count(), visible = count === 1 && await heading.isVisible();
      await currentDocument(false);
      state.lastCondition = count === 0 ? 'HEADING_MISSING' : count > 1 ? 'HEADING_DUPLICATED' : visible ? 'NONE' : 'HEADING_INVISIBLE';
      return state.lastCondition;
    }).toBe('NONE'), failed]);
    state.stage = 'SIDEBAR';
    const navigation = await sidebarStage();
    await emit('ACCEPTANCE_READY');
    await currentDocument(false);
    state.stage = 'COMPLETE';
    // The caller still owns authentication confirmation and must retain fatal
    // handling. dispose() releases opaque handles/listeners in its finally.
    return {...navigation, dispose};
  } catch {
    clearTimeout(timer);
    if (hasFailed()) result.verificationConditionFailure = 'EXISTING_FATAL_EVENT';
    else if (destinationSeen && (awayObserved || !atAdmin())) result.verificationConditionFailure = 'DESTINATION_CHANGED';
    else result.verificationConditionFailure ??= budget.remaining() <= 0 ? 'SHARED_DEADLINE_EXHAUSTED'
      : state.stage === 'HEADING' ? state.lastCondition
      : ['DESTINATION', 'DASHBOARD_METADATA'].includes(state.stage) ? 'SHARED_DEADLINE_EXHAUSTED' : 'SHARED_READINESS_FAILED';
    stopped = true;
    if (['DESTINATION', 'DASHBOARD_METADATA'].includes(state.stage)) state.rootElapsedMs = Math.min(30000, Math.round(budget.elapsed()));
    await emit('FAILURE').catch(() => {}); // Original closed failure remains authoritative.
    await dispose().catch(() => {result.cleanupFailed = true;});
    throw Error('Shared dashboard verification blocked');
  }
}
