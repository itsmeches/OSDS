// Verification only. Exports no DOM, snapshot contents, URLs or account data.
export function inspectDashboardContract() {
  let dashboards = 0, logins = 0, malformed = false;
  for (const node of document.querySelectorAll('[wire\\:snapshot]')) {
    try {
      const name = JSON.parse(node.getAttribute('wire:snapshot')).memo?.name;
      if (typeof name !== 'string') malformed = true;
      if (name === 'App\\Filament\\Pages\\Dashboard') dashboards++;
      if (name === 'Filament\\Auth\\Pages\\Login') logins++;
    } catch { malformed = true; }
  }
  const loginForm = [...document.forms].some(form => form.querySelector('input[type="email"]') && form.querySelector('input[type="password"]'));
  return { dashboard: Math.min(dashboards, 2), login: Math.min(logins, 2), loginForm, malformed };
}

export async function verifySidebarDashboard({ page, expect, base, failed, hasFailed, result }) {
  const atAdmin = () => { try { const u = new URL(page.url()); return u.origin === base && u.pathname === '/admin'; } catch { return false; } };
  const sidebar = page.locator('#fi-main-sidebar');
  const link = sidebar.getByRole('link', { name: 'Case-linked demonstration PDFs', exact: true });
  const check = async (code, operation) => {
    try {
      if (hasFailed()) throw Error('Fatal');
      await Promise.race([operation(), failed]);
      if (hasFailed()) throw Error('Fatal');
      if (!atAdmin()) { result.verificationConditionFailure = 'DESTINATION_CHANGED'; throw Error('Destination'); }
    } catch {
      result.verificationConditionFailure = hasFailed() ? 'EXISTING_FATAL_EVENT' : (result.verificationConditionFailure ?? code);
      throw Error('Authorized dashboard verification blocked');
    }
  };
  await check('DASHBOARD_CONDITION_FAILED', async () => {
    await expect.poll(() => page.evaluate(inspectDashboardContract)).toEqual({ dashboard: 1, login: 0, loginForm: false, malformed: false });
    await expect(page.getByRole('heading', { name: 'Your workspace', exact: true, level: 1 })).toBeVisible();
  });
  await check('SIDEBAR_CONDITION_FAILED', async () => {
    await expect(sidebar).toHaveCount(1);
    await expect(link).toHaveCount(1);
    await expect(link).toBeVisible();
    await expect.poll(async () => {
      const href = await link.getAttribute('href');
      try { return href !== null && new URL(href, base).href === base + '/admin/case-demonstration-documents'; } catch { return false; }
    }).toBe(true);
    await link.click({ trial: true, timeout: 5000 });
  });
  return { click: async () => {
   try {
    if (hasFailed() || !atAdmin()) throw Error('Fatal or destination');
    await expect(link).toHaveCount(1);
    const href = await link.getAttribute('href');
    if (href === null || new URL(href, base).href !== base + '/admin/case-demonstration-documents') throw Error('Destination');
    // Do not require /admin after the actual authorized navigation.
    if (hasFailed() || !atAdmin()) throw Error('Fatal or destination');
    await Promise.race([link.click(), failed]);
    if (hasFailed()) throw Error('Fatal');
   } catch {
    result.verificationConditionFailure = hasFailed() ? 'EXISTING_FATAL_EVENT' : 'SIDEBAR_CONDITION_FAILED';
    throw Error('Authorized sidebar navigation blocked');
   }
  } };
}
