<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Explore the CHED OSDS synthetic-data demonstration for COR and ACSA application workflows. Access requires an authorized account; documents are nonofficial.">
    <title>CHED OSDS — Application Workflow Demonstration</title>
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-page">
    <a class="landing-skip" href="#landing-content">Skip to main content</a>
    <header class="public-header landing-header">
        <div class="landing-container landing-header-inner">
            <div class="landing-brand">
                <p class="eyebrow">CHED &middot; OSDS</p>
                <p>Office of Student Development and Services</p>
            </div>
            <nav class="landing-navigation" aria-label="Page sections">
                <a href="#about">About</a>
                <a href="#process">Demonstration process</a>
                <a href="#access">Access</a>
            </nav>
            <div class="landing-theme"><x-theme-switcher /></div>
        </div>
    </header>
    <main id="landing-content" class="landing-container landing-main" tabindex="-1">
        <section id="about" class="landing-introduction" aria-labelledby="landing-heading">
            <div class="landing-intro-copy">
                <p class="landing-eyebrow">COR &amp; ACSA &middot; Synthetic-data demonstration</p>
                <h1 id="landing-heading">OSDS application workflows, in one place</h1>
                <p class="landing-lead">A demonstration platform for authorized higher education institution and association representatives and CHED personnel to prepare, review and track COR and ACSA applications using synthetic data.</p>
            </div>
            <aside class="landing-notice" aria-labelledby="demonstration-heading">
                <h2 id="demonstration-heading">Demonstration only</h2>
                <p>Uses synthetic data. This platform is not open for official public applications, and generated demonstration documents are not valid certificates.</p>
                <p>Use only fictional details in an authorized demonstration environment.</p>
            </aside>
        </section>

        <section id="access" class="landing-section" aria-labelledby="access-heading">
            <div class="landing-section-heading">
                <h2 id="access-heading">Choose your workspace</h2>
                <p>Sign in with an authorized account. The tasks available to you depend on your assigned access.</p>
            </div>
            <div class="landing-card-grid">
                <article class="landing-access-card" aria-labelledby="applicant-access-heading">
                    <p class="landing-eyebrow">Applicants and representatives</p>
                    <h3 id="applicant-access-heading">Prepare and follow your application</h3>
                    <p>Complete available questionnaires, respond to requested corrections and follow case-linked demonstration requests.</p>
                    <a href="{{ route('applicant.login') }}" class="primary">Applicant sign in</a>
                </article>
                <article class="landing-access-card" aria-labelledby="staff-access-heading">
                    <p class="landing-eyebrow">CHED personnel</p>
                    <h3 id="staff-access-heading">Review and manage assigned work</h3>
                    <p>Access assigned reviews, recommendations, decisions and configuration tasks according to your existing permissions.</p>
                    <a href="{{ url('/admin') }}" class="primary">Staff sign in</a>
                </article>
            </div>
        </section>

        <section class="landing-section" aria-labelledby="capabilities-heading">
            <div class="landing-section-heading">
                <h2 id="capabilities-heading">Current demonstration capabilities</h2>
                <p>Two application workflows share a traceable process while retaining their separate requirements.</p>
            </div>
            <div class="landing-card-grid">
                <article class="landing-service-card" aria-labelledby="cor-heading">
                    <h3 id="cor-heading">COR workflow</h3>
                    <p>Prepare a synthetic application, address corrections and follow its evaluation and decision history. Approved demonstration cases can proceed to case-linked requests and nonofficial documents when permitted.</p>
                </article>
                <article class="landing-service-card" aria-labelledby="acsa-heading">
                    <h3 id="acsa-heading">ACSA workflow</h3>
                    <p>Demonstrate an Authority to Conduct Sports Activity application, from submission and review to a decision and permitted case-linked document preparation, using synthetic data.</p>
                </article>
            </div>
        </section>

        <section id="process" class="landing-section" aria-labelledby="process-heading">
            <div class="landing-section-heading">
                <h2 id="process-heading">How the demonstration works</h2>
                <p>Applications may require corrections or reevaluation. Each next step depends on the case and the authorized staff responsible for it.</p>
            </div>
            <ol class="landing-process">
                <li><h3>Prepare and submit</h3><p>The applicant completes an available application and its documentary requirements.</p></li>
                <li><h3>Review and correct</h3><p>Assigned staff review the submission. The applicant revises and resubmits if corrections are requested.</p></li>
                <li><h3>Technical evaluation</h3><p>An authorized evaluator assesses the current submitted revision.</p></li>
                <li><h3>Chief recommendation</h3><p>An authorized Chief recommends approval or returns the case for reevaluation.</p></li>
                <li><h3>Regional Director decision</h3><p>An authorized Director records the final demonstration approval or rejection.</p></li>
                <li><h3>Demonstration document preparation</h3><p>When permitted, the applicant submits a case-linked request and authorized staff explicitly generate a nonofficial demonstration PDF.</p></li>
            </ol>
        </section>

        <section class="landing-assistance" aria-labelledby="assistance-heading">
            <h2 id="assistance-heading">Need access or help signing in?</h2>
            <p>Contact the project coordinator who provided your account. Access to this demonstration is limited to authorized users.</p>
        </section>
    </main>
    <footer class="public-footer landing-footer">
        <div class="landing-container">
            <p>Commission on Higher Education &middot; Office of Student Development and Services</p>
            <p>Synthetic-data demonstration &middot; No official certificate issuance</p>
        </div>
    </footer>
</body>
</html>
