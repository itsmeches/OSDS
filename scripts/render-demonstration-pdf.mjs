import fs from 'node:fs/promises';
import {chromium} from 'playwright';
import {PDFDocument, StandardFonts, rgb, degrees} from 'pdf-lib';
import {getDocument} from 'pdfjs-dist/legacy/build/pdf.mjs';

const MARK='DRAFT — DEMONSTRATION ONLY';
const DISCLAIMER='Synthetic test document. Not valid as an official CHED certificate or authorization.';
const [mode,input,output,chrome]=process.argv.slice(2);
let browser;
const deadline=setTimeout(()=>process.exit(124),55000);
try {
  if(mode==='render') {
    const html=await fs.readFile(input,'utf8');
    browser=await chromium.launch({...chrome?{executablePath:chrome}:{channel:'chrome'},headless:true,timeout:15000});
    const context=await browser.newContext({javaScriptEnabled:false,serviceWorkers:'block',offline:true});
    await context.route('**/*',route=>route.abort());
    const page=await context.newPage();
    await page.setContent(html,{waitUntil:'load',timeout:15000});
    await page.evaluate(()=>document.fonts.ready);
    const fontOkay=await page.evaluate(()=>document.fonts.check('12px Arial'));
    if(!fontOkay)throw Error('Required font unavailable');
    const raw=await page.pdf({format:'Letter',printBackground:true,margin:{top:'48px',bottom:'68px',left:'48px',right:'48px'},timeout:15000});
    await browser.close();browser=null;
    const pdf=await PDFDocument.load(raw);
    if(pdf.getPageCount()<1||pdf.getPageCount()>20)throw Error('Page limit');
    const font=await pdf.embedFont(StandardFonts.Helvetica);
    for(const p of pdf.getPages()) {
      const {width,height}=p.getSize();
      p.drawText(MARK,{x:65,y:height/2-80,size:24,font,rotate:degrees(30),color:rgb(.65,.65,.65),opacity:.28});
      p.drawRectangle({x:0,y:0,width,height:44,color:rgb(1,1,1)});
      p.drawText(MARK,{x:48,y:27,size:10,font,color:rgb(0,0,0)});
      p.drawText(DISCLAIMER,{x:48,y:13,size:7,font,color:rgb(0,0,0)});
    }
    await fs.writeFile(output,await pdf.save());
  } else if(mode!=='inspect')throw Error('Unknown operation');
  const bytes=await fs.readFile(mode==='render'?output:input);
  if(bytes.length<100||bytes.length>20*1024*1024||bytes.subarray(0,5).toString()!=='%PDF-')throw Error('PDF size/header');
  const loading=getDocument({data:new Uint8Array(bytes),useSystemFonts:true,isEvalSupported:false});
  const parsed=await loading.promise;
  if(parsed.numPages<1||parsed.numPages>20)throw Error('PDF page count');
  const pages=[];
  for(let n=1;n<=parsed.numPages;n++) {
    const text=(await(await parsed.getPage(n)).getTextContent()).items.map(i=>i.str).join(' ').replace(/\s+/g,' ');
    if(!text.includes(MARK)||!text.includes(DISCLAIMER))throw Error('Missing per-page markings');
    pages.push(text);
  }
  const result={pages:parsed.numPages,markings:true,bytes:bytes.length,text:pages.join(' ')};
  await loading.destroy();
  process.stdout.write(JSON.stringify(result));
} catch(e) {process.stderr.write('Demonstration PDF operation failed\n');process.exitCode=1;}
finally {if(browser)await browser.close();clearTimeout(deadline);}
