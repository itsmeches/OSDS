import {chromium,expect} from '@playwright/test';
import {readFileSync,writeFileSync} from 'node:fs';
const fixture=JSON.parse(readFileSync('storage/app/private/builder-journey.json','utf8'));
const browser=await chromium.launch({channel:'chrome',headless:true});
const context=await browser.newContext({viewport:{width:1440,height:1000},colorScheme:'light'});
await context.route('**/*',r=>new URL(r.request().url()).hostname==='127.0.0.1'?r.continue():r.abort());
const page=await context.newPage(),result={checks:[],errors:[]};
page.on('pageerror',e=>result.errors.push(e.message));
try{
    await page.goto('http://127.0.0.1:8017/admin/login');
    await page.locator('input[type=email]').fill('osds@example.invalid');
    await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await page.getByRole('button',{name:'Sign in',exact:true}).click();
    await page.waitForURL(u=>!u.pathname.endsWith('/login'));
    await page.evaluate(()=>localStorage.setItem('theme','system'));
    await page.goto('http://127.0.0.1:8017'+fixture.builder);
    await expect(page.locator('html')).not.toHaveClass(/dark/);
    await page.emulateMedia({colorScheme:'dark'});
    await expect(page.locator('html')).toHaveClass(/dark/);
    await page.emulateMedia({colorScheme:'light'});
    await expect(page.locator('html')).not.toHaveClass(/dark/);
    result.checks.push('System follows actual light/dark media preference changes');
    await page.getByRole('button',{name:'Preview',exact:true}).focus();
    result.focus=await page.getByRole('button',{name:'Preview',exact:true}).evaluate(e=>({focused:document.activeElement===e,outlineStyle:getComputedStyle(e).outlineStyle,outlineWidth:getComputedStyle(e).outlineWidth,outlineColor:getComputedStyle(e).outlineColor,boxShadow:getComputedStyle(e).boxShadow}));
    expect(result.focus.focused).toBe(true);
    expect(result.focus.outlineStyle!=='none'||result.focus.boxShadow!=='none').toBe(true);
    result.checks.push('Keyboard-focus target has a rendered outline or focus ring');
    expect(result.errors).toEqual([]);
}catch(e){result.failure=String(e);process.exitCode=1;console.error(e);}
finally{writeFileSync('docs/screenshots/phase-3d7-e/system-results.json',JSON.stringify(result,null,2));await browser.close();}
