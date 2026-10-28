import fs from 'node:fs';
import assert from 'node:assert/strict';
import {inspectDashboardContract} from './uiux-b2-sidebar-navigation.mjs';
const d='storage/app/private/uiux-consolidated-acceptance/gate-b2-sidebar-validation/';
const original=fs.readFileSync('scripts/verify-uiux-b2-attempt-07.mjs','utf8'),candidate=fs.readFileSync(d+'candidate-driver.mjs','utf8'),helper=fs.readFileSync('scripts/uiux-b2-sidebar-navigation.mjs','utf8');
let checks=0;const ok=v=>{assert.ok(v);checks++;};
ok(candidate.split('await ready();').length===3);ok(candidate.split("name:'Sign in',exact:true}).click()").length===2);
const request=s=>s.slice(s.indexOf(" await context.route("),s.indexOf(" const endpointScript"));
// Compare the complete pre-navigation workflow after removing the sole new import.
const marker=" mark('navigation-check-start');";
ok(candidate.slice(0,candidate.indexOf(marker)).replace("import {verifySidebarDashboard} from './uiux-b2-sidebar-navigation.mjs';\n",'')===original.slice(0,original.indexOf(marker)));
ok(candidate.includes('await verifiedNavigation.click();'));ok(!candidate.includes("getByRole('link',{name:'Case-linked demonstration PDFs'"));
ok(helper.includes("page.locator('#fi-main-sidebar')"));ok(helper.includes("name: 'Case-linked demonstration PDFs', exact: true"));ok(helper.includes('await expect(link).toHaveCount(1)'));
ok(helper.includes("new URL(href, base).href === base + '/admin/case-demonstration-documents'"));ok(helper.includes('trial: true'));ok(!helper.includes('.first(')&&!helper.includes('.nth('));
ok(candidate.slice(candidate.indexOf(' await expect(page).toHaveURL(base+'))===original.slice(original.indexOf(' await expect(page).toHaveURL(base+')));
function inspect(names,form=false){globalThis.document={querySelectorAll:()=>names.map(name=>({getAttribute:()=>name===null?'invalid':JSON.stringify({memo:{name}})})),forms:form?[{querySelector:()=>true}]:[]};try{return inspectDashboardContract();}finally{delete globalThis.document;}}
assert.deepEqual(inspect(['App\\Filament\\Pages\\Dashboard']),{dashboard:1,login:0,loginForm:false,malformed:false});checks++;
ok(inspect(['Filament\\Auth\\Pages\\Login']).login===1);ok(inspect(['App\\Filament\\Pages\\Dashboard'],true).loginForm);ok(inspect([null]).malformed);ok(inspect(Array(3).fill('App\\Filament\\Pages\\Dashboard')).dashboard===2);
ok(helper.includes('EXISTING_FATAL_EVENT'));ok(helper.includes('hasFailed()'));ok(candidate.indexOf('await verifySidebarDashboard')<candidate.indexOf('loginState.authenticationConfirmed=true'));
console.log(JSON.stringify({status:'PASS',checks}));
