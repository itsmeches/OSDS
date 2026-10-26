<!doctype html><html lang="en"><head><meta charset="UTF-8"><title>Demonstration document</title>
<style>
@page {size:letter portrait;} * {box-sizing:border-box;} body {font-family:Arial,sans-serif;color:#111;font-size:14px;line-height:1.5;overflow-wrap:anywhere;} h1 {font-size:28px;line-height:1.2;} main{text-align:center;padding-top:65px;} .recipient{font-size:24px;margin-top:45px;} .address{font-size:18px;} .body{white-space:pre-wrap;margin:45px 0;} .personnel{margin-top:55px;} .appendix{break-before:page;} dd{white-space:pre-wrap;margin-bottom:20px;} dt{font-weight:bold;} .small{font-size:11px;}
</style></head><body><main>
<h1>{{ $title }}</h1><p>Demonstration layout prepared for:</p>
<p class="recipient">{{ $identity['organization'] }}</p><p class="address">{{ $identity['address'] }}</p>
<p class="body">{{ $template['wording'] }}</p>
<p><strong>This synthetic sample makes no finding of compliance.</strong></p>
<p>Demonstration generation date: {{ $date }} (not an issuance date)</p>
<p><strong>NO LEGAL VALIDITY — NOT ISSUED</strong></p>
<div class="personnel">{{ $personnel['office']['director'] }}<br>{{ $personnel['office']['designation'] }}<br>{{ $personnel['office']['label'] }}<br><strong>No signature / not issued</strong></div>
<p class="small">Demonstration case {{ $caseReference }}<br>Template version {{ $templateVersion }} · Personnel mapping version {{ $personnelVersion }}<br>Director decision reference {{ $decisionId }} · Exact submission {{ $submissionId }}</p>
</main>@if(count($details))<section class="appendix"><h2>Submitted supplementary demonstration details</h2><p>These request answers do not establish a new substantive approval.</p><dl>@foreach($details as $detail)<dt>{{ $detail['label'] }}</dt><dd>{{ $detail['value'] }}</dd>@endforeach</dl></section>@endif
</body></html>
