<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — DRAFT / DEMONSTRATION ONLY</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9edf2; color: #222; font-family: Arial, sans-serif; }
        .notice { max-width: 816px; margin: 16px auto; padding: 16px; background: white; border: 2px solid #475569; line-height: 1.5; }
        .sheet { position: relative; isolation: isolate; overflow: hidden; width: min(100%, 816px); min-height: 1056px; margin: 0 auto 24px; padding: 110px 64px 48px; background: white; text-align: center; overflow-wrap: anywhere; }
        .watermark { position: absolute; inset: 0; z-index: -1; display: flex; flex-direction: column; justify-content: space-evenly; color: #8e8e8e; font-size: 32px; font-weight: bold; transform: rotate(-25deg); pointer-events: none; }
        h1 { font-size: 36px; margin: 16px 0 0; }
        .recipient { font-size: 28px; margin: 56px 0 14px; }
        .address { font-size: 21px; }
        .wording { margin: 56px 0; line-height: 1.45; font-size: 18px; }
        .reference { margin: 40px 0; font-size: 20px; }
        .signatory { margin-top: 64px; line-height: 1.5; }
        footer { margin-top: 48px; font-size: 12px; line-height: 1.6; border-top: 1px solid #777; padding-top: 12px; }
        @page { size: letter portrait; margin: 0; }
        @media print { body { background: white; } .notice { display: none; } .sheet { width: 100%; min-height: 0; margin: 0; padding-top: 90px; } .watermark { position: fixed; } }
        @media screen and (max-width: 600px) { .sheet { padding: 44px 24px; min-height: 0; } h1 { font-size: 27px; } .recipient { font-size: 24px; } .watermark { font-size: 22px; } }
    </style>
</head>
<body>
    <aside class="notice"><strong>DRAFT / DEMONSTRATION ONLY</strong><br>Demonstration form preview. No approval, registration or activity authorization is granted. {{ $terminology }} Use your browser’s Print / Save as PDF to review the layout.</aside>
    <main class="sheet">
        <div class="watermark" aria-hidden="true"><span>DRAFT / DEMONSTRATION ONLY</span><span>DRAFT / DEMONSTRATION ONLY</span><span>DRAFT / DEMONSTRATION ONLY</span></div>
        <h1>{{ $title }}</h1>
        <p>Demonstration layout prepared for:</p>
        <p class="recipient">{{ $organization }}</p>
        <p class="address">{{ $address }}</p>
        <p class="wording">Sample reference wording: CHED Memorandum Order No. 13, Series of 2026, “Policies and Guidelines on Student-Athletes Protection,” and Republic Act No. 10676, “Student-Athletes Protection Act.”<br><strong>This fictional sample makes no finding of compliance.</strong></p>
        <p class="reference">Internal sample reference: {{ $reference }}<br><small>Not an official serial number</small></p>
        <p>Demonstration date: {{ $date }}</p>
        <p><strong>NO LEGAL VALIDITY — validity wording pending OSDS confirmation</strong></p>
        <div class="signatory">{{ $director }}<br>{{ $designation }}<br>{{ $office }}<br><strong>No signature / not issued</strong></div>
        <footer><strong>DRAFT / DEMONSTRATION ONLY</strong><br>Template {{ $version }} · Directory revision {{ $directoryRevision }} · Unapproved demonstration adaptation<br>Not linked to an application, submission revision or decision.</footer>
    </main>
</body>
</html>
