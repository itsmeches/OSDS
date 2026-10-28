// Offline-reviewed candidate only. Not imported by any protected driver.
import {inspectDashboardContract} from './uiux-b2-sidebar-navigation.mjs';

export function dashboardMetadataFailure(value) {
  if (!value || Object.keys(value).sort().join() !== 'dashboard,login,loginForm,malformed'
      || ![0, 1, 2].includes(value.dashboard) || ![0, 1, 2].includes(value.login)
      || typeof value.loginForm !== 'boolean' || typeof value.malformed !== 'boolean') return 'INVALID_DIAGNOSTIC_METADATA';
  if (value.malformed) return 'MALFORMED_COMPONENT_METADATA';
  if (value.login) return 'UNEXPECTED_LOGIN_COMPONENT';
  if (value.loginForm) return 'LOGIN_FORM_PRESENT';
  if (value.dashboard === 0) return 'DASHBOARD_COMPONENT_MISSING';
  if (value.dashboard === 2) return 'DASHBOARD_COMPONENT_DUPLICATED';
  return 'NONE';
}

export async function diagnoseDashboardCandidate({page, expect, base, failed, hasFailed, result}) {
  // Last observed sample only; do not retain poll histories or raw errors.
  const diagnostic = {metadataObserved: false, dashboard: 0, login: 0, loginForm: false, malformed: false,
    headingObserved: false, headingCount: 0, headingVisible: false, code: 'METADATA_OBSERVATION_UNAVAILABLE'};
  result.dashboardDiagnostic = diagnostic;
  const atAdmin = () => {try {const u = new URL(page.url()); return u.origin === base && u.pathname === '/admin';} catch {return false;}};
  const stopCheck = () => {
    if (hasFailed()) {diagnostic.code = 'EXISTING_FATAL_EVENT'; throw Error('Dashboard diagnostic blocked');}
    if (!atAdmin()) {diagnostic.code = 'DESTINATION_CHANGED'; throw Error('Dashboard diagnostic blocked');}
  };
  const stage = async operation => {
    try {stopCheck(); await Promise.race([operation(), failed]); stopCheck();}
    catch {
      if (hasFailed()) diagnostic.code = 'EXISTING_FATAL_EVENT';
      else if (!atAdmin()) diagnostic.code = 'DESTINATION_CHANGED';
      result.verificationConditionFailure = diagnostic.code;
      throw Error('Dashboard diagnostic blocked');
    }
  };
  await stage(async () => {
    await expect.poll(async () => {
      stopCheck();
      const value = await page.evaluate(inspectDashboardContract);
      stopCheck();
      const code = dashboardMetadataFailure(value);
      diagnostic.code = code;
      if (code !== 'INVALID_DIAGNOSTIC_METADATA') {
        diagnostic.metadataObserved = true;
        for (const key of ['dashboard', 'login', 'loginForm', 'malformed']) diagnostic[key] = value[key];
      }
      return code;
    }).toBe('NONE');
  });
  diagnostic.code = 'HEADING_OBSERVATION_UNAVAILABLE';
  await stage(async () => {
    const heading = page.getByRole('heading', {name: 'Your workspace', exact: true, level: 1, includeHidden: true});
    await expect.poll(async () => {
      stopCheck();
      const count = await heading.count();
      const visible = count === 1 && await heading.isVisible();
      stopCheck();
      diagnostic.headingObserved = true;
      diagnostic.headingCount = Math.min(count, 2);
      diagnostic.headingVisible = visible;
      diagnostic.code = count === 0 ? 'HEADING_MISSING' : count > 1 ? 'HEADING_DUPLICATED' : !visible ? 'HEADING_INVISIBLE' : 'NONE';
      return diagnostic.code;
    }).toBe('NONE');
  });
  // This function never sets authenticationConfirmed; the caller must retain
  // sidebar validation, final destination/fatal checks and the existing tail.
}
