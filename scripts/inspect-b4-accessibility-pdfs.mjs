import fs from 'node:fs/promises';
import {getDocument} from 'pdfjs-dist/legacy/build/pdf.mjs';
import {createCanvas} from '@napi-rs/canvas';
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {execFileSync} from 'node:child_process';

const dir='docs/screenshots/b4-accessibility-remediation/final-matrix';
const observation=JSON.parse(execFileSync(process.env.TEMP+'/osds-phase1-tools/php/php.exe',['scripts/Prepare-B4AccessibilityFinal.php','inspect'],{encoding:'utf8'}));
const results=[];
for(const module of ['registration','acsa']){
 const bytes=await fs.readFile(dir+'/'+module+'-DEMONSTRATION.pdf');
 const checksum=createHash('sha256').update(bytes).digest('hex');
 const doc=observation.documents.find(d=>d.checksum===checksum);
 assert(doc&&doc.state==='completed');assert.equal(checksum,doc.actual_checksum);
 const request=observation.requests.find(r=>r.id===doc.request_id);
 const application=observation.cases.find(c=>c.id===doc.application_id);
 const loading=getDocument({data:new Uint8Array(bytes),useSystemFonts:true,isEvalSupported:false});
 const pdf=await loading.promise,pages=[],texts=[];
 assert.equal(pdf.numPages,doc.page_count);assert(pdf.numPages>=2);
 for(let n=1;n<=pdf.numPages;n++){
  const page=await pdf.getPage(n),text=(await page.getTextContent()).items.map(i=>i.str).join(' ').replace(/\s+/g,' ');
  assert(text.includes('DRAFT \u2014 DEMONSTRATION ONLY'));
  assert(text.includes('Synthetic test document. Not valid as an official CHED certificate or authorization.'));
  texts.push(text);const viewport=page.getViewport({scale:1}),canvas=createCanvas(viewport.width,viewport.height);
  await page.render({canvasContext:canvas.getContext('2d'),viewport}).promise;
  await fs.writeFile(dir+'/'+module+'-pdf-page-'+n+'.png',canvas.toBuffer('image/png'));
  pages.push({page:n,watermark:true,disclaimer:true});
 }
 const text=texts.join(' ');
 assert(text.includes(module==='registration'?'CERTIFICATE OF RECOGNITION':'AUTHORITY TO CONDUCT SPORTS ACTIVITY'));
 assert(text.includes('Fictional '+module+' B4 HEI R2'));assert(text.includes('2 Revised Lane'));assert(!text.includes('1 Draft Lane'));
 assert(text.includes('Demonstration case '+application.id));assert(text.includes('Exact submission '+request.submission_id));assert(text.includes('Director decision reference '+request.decision_id));
 assert(text.includes('Template version '+doc.template_id));assert(text.includes('Personnel mapping version '+doc.personnel_id));
 assert(text.includes('Dr. B4 Fictional Director'));
 results.push({module,document:doc.public_id,request:request.public_id,case:application.public_id,submission:request.submission_id,decision:request.decision_id,checksum,pages,correctedR2:true,moduleTitle:true,immutableLinkage:true,fictionalPersonnel:true,status:'PASS'});
 await loading.destroy();
}
await fs.writeFile(dir+'/pdf-inspection.json',JSON.stringify(results,null,2));
console.log(JSON.stringify(results));
