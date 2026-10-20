import {chromium} from '@playwright/test';
import {readFileSync} from 'node:fs';
const data=JSON.parse(readFileSync('storage/app/private/chedro-journey.json','utf8'));
const browser=await chromium.launch({channel:'chrome',headless:true});
const page=await browser.newPage({viewport:{width:375,height:1000}});
await page.goto('http://127.0.0.1:8017/admin/login');
await page.locator('input[type="email"]').fill(data.reviewer);
await page.locator('input[type="password"]').fill('UiBrowserOnly-2026!');
await page.getByRole('button',{name:'Sign in',exact:true}).click();
await page.waitForURL(url=>!url.pathname.endsWith('/login'));
await page.goto('http://127.0.0.1:8017/admin/synthetic-application-intake');
for(const width of [1440,768,375]) {
await page.setViewportSize({width,height:1000}); await page.waitForTimeout(500);
console.log(await page.evaluate(()=>({viewport:innerWidth,doc:document.documentElement.scrollWidth,nodes:[...document.querySelectorAll('body *')].filter(e=>e.getBoundingClientRect().right>innerWidth+1&&!e.closest('.staff-queue-table')).map(e=>({class:e.className,right:e.getBoundingClientRect().right,position:getComputedStyle(e).position})).slice(0,8)})));
}
await browser.close();
