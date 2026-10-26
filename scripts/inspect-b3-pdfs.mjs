import fs from 'node:fs/promises';
import {getDocument} from 'pdfjs-dist/legacy/build/pdf.mjs';
import {createCanvas} from '@napi-rs/canvas';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
const dir='docs/screenshots/b3-demonstration-pdfs',results=[];
for(const module of ['registration','acsa']) {
 const bytes=await fs.readFile(dir+'/'+module+'-DEMONSTRATION.pdf');
 const task=getDocument({data:new Uint8Array(bytes),useSystemFonts:true,isEvalSupported:false});const pdf=await task.promise,pages=[];
 for(let n=1;n<=pdf.numPages;n++) {
  const page=await pdf.getPage(n),text=(await page.getTextContent()).items.map(i=>i.str).join(' ').replace(/\s+/g,' ');
  assert(text.includes('DRAFT — DEMONSTRATION ONLY'));assert(text.includes('Synthetic test document. Not valid as an official CHED certificate or authorization.'));
  if(n===1)assert(text.includes(module==='registration'?'CERTIFICATE OF RECOGNITION':'AUTHORITY TO CONDUCT SPORTS ACTIVITY'));
  const view=page.getViewport({scale:1}),canvas=createCanvas(view.width,view.height);
  await page.render({canvasContext:canvas.getContext('2d'),viewport:view}).promise;
  await fs.writeFile(dir+'/'+module+'-pdf-page-'+n+'.png',canvas.toBuffer('image/png'));
  pages.push({page:n,watermark:true,disclaimer:true,width:view.width,height:view.height});
 }
 results.push({module,pages,checksum:createHash('sha256').update(bytes).digest('hex'),size:bytes.length,passed:true});await task.destroy();
}
await fs.writeFile(dir+'/pdf-inspection.json',JSON.stringify(results,null,2));console.log('Actual COR/ACSA PDF inspection passed');
