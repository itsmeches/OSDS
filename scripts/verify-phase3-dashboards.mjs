// Standalone Blade component previews. No Laravel application/bootstrap or DB.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';

const output = 'docs/screenshots/phase3-dashboards';
const privateDir = 'storage/app/private/phase3-dashboards';
fs.mkdirSync(output, { recursive: true });
fs.mkdirSync(privateDir, { recursive: true });
const php = process.env.PHASE3_PHP || path.join(process.env.TEMP, 'osds-phase1-tools/php/php.exe');
const renderer = String.raw`<?php
require getcwd().'/vendor/autoload.php';
use App\Filament\Widgets\TaskOrientation;
use App\Support\ApplicantApplicationGuidance as Guidance;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
$dir=getcwd().'/storage/app/private/phase3-dashboards';
@mkdir($dir.'/views',0777,true);
@mkdir($dir.'/compiled',0777,true);
$files=new Illuminate\Filesystem\Filesystem;
$container=new Container;
Container::setInstance($container);
$compiler=new Illuminate\View\Compilers\BladeCompiler($files,$dir.'/compiled','',false);
$compiler->withoutComponentTags();
$engines=new Illuminate\View\Engines\EngineResolver;
$engines->register('blade',fn()=>new Illuminate\View\Engines\CompilerEngine($compiler,$files));
$factory=new Illuminate\View\Factory($engines,new Illuminate\View\FileViewFinder($files,[$dir.'/views']),new Illuminate\Events\Dispatcher($container));
$factory->setContainer($container);
$container->instance('view',$factory);
$container->instance('url',new class {
    public function route($name,$parameters=[],$absolute=true) { return '#preview-'.str_replace('.','-',$name); }
});
$render=function($name,$source,$data=[]) use($dir,$factory) {
    $source=preg_replace('/@extends\([^\n]+\)|@section\(\x27content\x27\)|@endsection/','',$source);
    $source=str_replace(['<x-filament-widgets::widget>','</x-filament-widgets::widget>'],['<section>','</section>'],$source);
    $source=preg_replace('/<x-applicant-status :state="([^"]+)" \/>/','<span class="status">{{ \\App\\Support\\ApplicantApplicationGuidance::label($1) }}</span>',$source);
    $source=preg_replace('/<x-filament::icon[^>]+\/>/', '{!! $previewIcon($task[\'icon\']) !!}', $source);
    $data['previewIcon']=static function($icon) {
        $asset=getcwd().'/vendor/blade-ui-kit/blade-heroicons/resources/svg/'.substr($icon,9).'.svg';
        return str_replace('<svg ', '<svg aria-hidden="true" ', file_get_contents($asset));
    };
    file_put_contents($dir.'/views/'.$name.'.blade.php',$source);
    return $factory->make($name,$data)->render();
};
$catalogue=TaskOrientation::catalogue();
$entries=[];
foreach($catalogue as $heading=>$items) foreach($items as [$class,$label,$description,$icon]) {
    if(!class_exists($class) || !method_exists($class,'canAccess')) throw new RuntimeException('Invalid destination: '.$class);
    $entries[]=['class'=>$class,'label'=>$label,'group'=>$heading];
}
$widgetSource=file_get_contents('app/Filament/Widgets/TaskOrientation.php');
foreach(['$destination::canAccess()','static::canView()','auth()->user()->can($permission)'] as $required) if(!str_contains($widgetSource,$required)) throw new RuntimeException('Missing access-contract call');
if(preg_match('/::query\(|::where\(|DB::|hasRole\(/',$widgetSource)) throw new RuntimeException('Unexpected task query/role branch');
$cases=[];
foreach(['none','review-only','configuration-only','combined'] as $profile) {
    $groups=[];
    foreach($catalogue as $heading=>$items) {
        $include=$profile==='combined' || ($profile==='review-only' && $heading==='Applications and Reviews') || ($profile==='configuration-only' && $heading==='Forms and Demonstration Documents');
        if(!$include) continue;
        foreach($items as [$class,$label,$description,$icon]) {
            $responsibilities=$profile==='combined' && str_ends_with($class,'SyntheticApplicationIntake') ? array_values(TaskOrientation::responsibilityDescriptions()) : [];
            $groups[$heading][]=['label'=>$label,'description'=>$description,'icon'=>$icon,'responsibilities'=>$responsibilities,'url'=>'#preview'];
        }
    }
    $html=$render('tasks_'.str_replace('-','_',$profile),file_get_contents('resources/views/filament/widgets/task-orientation.blade.php'),['groups'=>$groups]);
    $cases[]=['name'=>$profile,'kind'=>'staff','html'=>$html];
}
$makeCase=function($state,$ended=false) {
    $draft=(object)['public_id'=>'synthetic-draft','last_saved_at'=>Carbon::parse('2026-10-27 09:00:00','Asia/Manila')];
    return (object)['state'=>$state,'public_id'=>'synthetic-'.$state,'institution'=>(object)['name'=>'Synthetic Learning Institution and Association for Demonstration Purposes','display_name'=>'Synthetic Learning Institution and Association for Demonstration Purposes'],'manifest'=>(object)['retirement'=>$ended ? (object)[] : null,'effective_until'=>null,'module'=>(object)['name'=>'COR / ACSA synthetic application']],'module'=>(object)['name'=>'COR / ACSA synthetic application'],'currentWorkingRevision'=>in_array($state,['draft','correction_requested']) ? (object)['draft'=>$draft] : null,'updated_at'=>Carbon::parse('2026-10-27 09:00:00','Asia/Manila'),'currentSubmission'=>(object)['submission_sequence'=>2],'history'=>collect([])];
};
$states=['draft','submitted','under_screening','correction_requested','resubmitted','approved','rejected','unknown'];
$applications=collect(array_map($makeCase,$states));
$applications->push($makeCase('approved',true),$makeCase('correction_requested',true));
$home=$render('home',file_get_contents('resources/views/applicant/home.blade.php'),compact('applications'));
$tracking='';
foreach(['correction_requested','approved','unknown'] as $state) {
    $application=$makeCase($state);
    $correction=(object)['applicant_summary'=>'Synthetic correction: update the organization address.','items'=>collect([(object)['instruction'=>'Replace the fictional address with the corrected fictional address.']])];
    $tracking.=$render('tracking_'.$state,file_get_contents('resources/views/applicant/track.blade.php'),compact('application','correction'));
}
$empty=$render('empty',file_get_contents('resources/views/applicant/home.blade.php'),['applications'=>collect([])]);
$cases[]=['name'=>'applicant-states','kind'=>'applicant','html'=>$home.'<hr>'.$tracking];
$cases[]=['name'=>'applicant-empty','kind'=>'applicant','html'=>$empty];
$requestSource=file_get_contents('resources/views/livewire/applicant/issuance-requests.blade.php');
$requestSource=preg_replace('/@php\(\$document=.+?\)\r?\n/','',$requestSource);
$requestSource=str_replace("\\Illuminate\\Support\\Facades\\DB::table('issuance_history')->where('request_id',\$saved->id)->orderBy('id')->get()",'$previewHistory',$requestSource);
$requestSource=str_replace("\\App\\Models\\IssuanceRequest::where('application_id',\$case->id)->where('actor_id',auth()->id())->orderByDesc('id')->get()",'$previewVersions',$requestSource);
$requestSource=str_replace('\\App\\Models\\IssuanceDefinition::find($prior->definition_id)->version','1',$requestSource);
if(str_contains($requestSource,'::where(') || str_contains($requestSource,'DB::') || str_contains($requestSource,'Facades\\DB::')) throw new RuntimeException('Preview retained a query');
$requests='';
foreach(['draft','submitted','processing','failed','completed','unknown','unavailable'] as $scenario) {
    $case=$makeCase('approved');
    $saved=(object)['state'=>$scenario==='draft' ? 'draft' : 'submitted','public_id'=>'synthetic-request','id'=>1,'definition_snapshot'=>['questions'=>[]],'answers'=>[]];
    $definition=$scenario==='unavailable' ? null : (object)['title'=>'Synthetic request form','version'=>1,'instructions'=>'Demonstration request only.','definition'=>['questions'=>[],'requirements'=>[]]];
    $reason=$scenario==='unavailable' ? 'OSDS must publish an issuance definition matching this exact application questionnaire.' : null;
    $identity=null;
    $document=in_array($scenario,['processing','failed','completed','unknown']) ? (object)['state'=>$scenario,'public_id'=>'synthetic-document'] : null;
    $data=compact('case','saved','definition','reason','identity','document')+['cases'=>collect([]),'feedback'=>'','errors'=>new Illuminate\Support\ViewErrorBag,'previewHistory'=>collect([]),'previewVersions'=>collect([])];
    $requests.=$render('requests_'.$scenario,$requestSource,$data).'<hr>';
}
$cases[]=['name'=>'request-pdf-states','kind'=>'applicant','html'=>$requests];
echo json_encode(['cases'=>$cases,'catalogue'=>$entries,'guidance'=>array_map(fn($state)=>['state'=>$state,'label'=>Guidance::label($state),'description'=>Guidance::description($state)],$states)],JSON_THROW_ON_ERROR);
`;
fs.writeFileSync(privateDir + '/render.php', renderer);
const prepared = JSON.parse(execFileSync(php, [privateDir + '/render.php'], { encoding: 'utf8', maxBuffer: 8000000 }));
fs.writeFileSync(output + '/catalogue-and-guidance.json', JSON.stringify({ catalogue: prepared.catalogue, guidance: prepared.guidance, scope: 'Metadata and source contracts only; no actual permission evaluation.' }, null, 2));
const manifest = JSON.parse(fs.readFileSync('public/build/manifest.json', 'utf8'));
const styles = {};
for (const [kind, entry] of [['staff', 'resources/css/filament/admin/theme.css'], ['applicant', 'resources/css/app.css']]) styles[kind] = fs.readFileSync('public/build/' + manifest[entry].file, 'utf8');
const results = { scope: 'Standalone Blade previews of changed templates with synthetic in-memory data and simulated task visibility. Shared component wrappers/icons and route helpers are substituted; existing request queries are replaced with inert collections. No Laravel bootstrap, account, application server, database or actual authorization is exercised.', captures: [], supplemental: [] };
const browser = await chromium.launch({ headless: true });
async function inspect(sample, width, mode, textScale = false) {
    const context = await browser.newContext({ viewport: { width, height: 1000 } });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await context.route('**/*', route => route.abort());
    const html = sample.html;
    await page.setContent(`<!doctype html><html lang="en" class="${mode === 'dark' ? 'dark' : ''}"><head><title>Phase 3 component preview</title><style>${styles[sample.kind]} body { background: var(--osds-color-canvas); color: var(--osds-color-text); font-family: var(--osds-font-family); margin: 0; } .preview-container { max-width: 72rem; margin-inline: auto; padding: 1rem; } .preview-container > h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; } hr { margin-block: 2rem; border-color: var(--osds-color-border); }</style></head><body><main class="preview-container"><h1>Component preview: ${sample.name}</h1><p>Simulated data and visibility; no authenticated account or live case.</p>${html}</main></body></html>`);
    if (textScale) await page.addStyleTag({ content: 'html { font-size: 200%; }' });
    const axe = await new AxeBuilder({ page }).options({ rules: { 'target-size': { enabled: true } } }).analyze();
    await page.keyboard.press('Tab');
    const keyboard = await page.evaluate(() => {
        const el = document.activeElement;
        const style = getComputedStyle(el);
        return { tag: el.tagName, label: el.textContent.trim().slice(0, 100), focusVisible: style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0 };
    });
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
    const capture = `${sample.name}-${mode}-${width}${textScale ? '-text200' : ''}.png`;
    await page.screenshot({ path: output + '/' + capture, fullPage: true });
    const result = { profile: sample.name, width, mode, textScale, capture, overflow, keyboard, errors, violations: axe.violations, incomplete: axe.incomplete };
    await context.close();
    return result;
}
try {
    for (const sample of prepared.cases) {
        for (const width of [375, 768, 1024, 1440]) for (const mode of ['light', 'dark']) results.captures.push(await inspect(sample, width, mode));
        console.log('Completed component profile: ' + sample.name);
    }
    for (const sample of prepared.cases.filter(s => ['combined', 'applicant-states'].includes(s.name))) {
        for (const mode of ['light', 'dark']) for (const [width, scale] of [[320, false], [375, true], [1440, true]]) results.supplemental.push(await inspect(sample, width, mode, scale));
    }
} finally {
    await browser.close();
    fs.writeFileSync(output + '/results.json', JSON.stringify(results, null, 2));
}
const all = [...results.captures, ...results.supplemental];
const failures = all.filter(r => r.overflow || r.errors.length || r.violations.length || (r.profile !== 'none' && !r.keyboard.focusVisible));
console.log(JSON.stringify({ captures: results.captures.length, supplemental: results.supplemental.length, failingSamples: failures.length, incompleteSamples: all.filter(r => r.incomplete.length).length }));
if (failures.length) process.exitCode = 1;
