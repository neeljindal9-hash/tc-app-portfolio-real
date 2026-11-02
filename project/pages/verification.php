<?php

$email = $request->session['email'] ?? '';
$name = $request->session['name'] ?? '';

// Determine View State  (unchanged)
$isAuth = !empty($email);
$isValidStage = ($request->session['stage'] ?? "") === "Stage 2: School Verification" && ($request->session['secure'] ?? 0) === 1;

if (!$isAuth) {
    $viewState = 'lame_blocked';
} elseif (!$isValidStage) {
    $viewState = 'cool_blocked';
} else {
    $viewState = 'valid';
    $request->session['secure'] = 100;
    $request->session['verify-attempts'] = 0;
}

// Keep in sync with whatever expiry the API enforces for this stage.
$sessionSeconds = 240;
$sessionMinutes = intdiv($sessionSeconds, 60);

/*
 * ---------------------------------------------------------------------------
 * SCHOOL REGISTRY
 * One entry per supported school; the page renders from it and the same data
 * is handed to the browser. The client-side checks are only a convenience:
 * the API must re-validate everything against its own copy of this registry
 * (suggestion: move this array into classes/SchoolRegistry.php and use it in
 * both places).
 *
 * fields : which identifiers this school's flow asks for ('id', 'email')
 * Research status, per school:
 *   mvhs / lahs  CONFIRMED by existing behaviour: 9-digit ID -> school email.
 *   gunn         PARTIAL. PAUSD says the student username is the student ID
 *                number (gunn.pausd.org/connecting/tech-help-for-students) but
 *                not its length, so the pattern is deliberately loose. The
 *                "FL..." pattern on that page is the default PASSWORD
 *                (first letter of first name + first letter of last name +
 *                "!" + MMDDYY), not an ID. We never ask for passwords.
 *   stuy         PARTIAL. NYC DOE: 9-digit OSIS; student email is
 *                <username>@nycstudents.net, where the username is NOT
 *                derivable from the OSIS, so the student enters it.
 *                Domain proves DOE enrolment, not Stuyvesant: the API needs a
 *                roster match on OSIS.
 *   loyola       ASSUMED. Loyola High School of Los Angeles uses loyolahs.edu;
 *                the student address format wasn't published, so only the
 *                domain is enforced (subdomains allowed).
 *   tunis        PLACEHOLDER. No school by this name turned up; generic
 *                ID + email flow until real details exist.
 * ---------------------------------------------------------------------------
 */
$schools = [
    'mvhs' => [
        'name' => 'Mountain View High School',
        'location' => 'Mountain View, CA',
        'method' => 'Student ID',
        'fields' => ['id'],
        'id' => [
            'label' => 'Student ID', 'placeholder' => '9-digit ID', 'maxlength' => 9, 'numeric' => true,
            'pattern' => '^\d{9}$',
            'hint' => 'Your 9-digit student ID number.',
            'error' => 'Enter your 9-digit student ID.',
        ],
        'destination' => 'A one-time code will be sent to the school email associated with your student ID.',
        'middleSchools' => ['Blach Intermediate School', 'Graham Middle School', 'Egan Junior High', 'Crittenden Middle School'],
    ],
    'lahs' => [
        'name' => 'Los Altos High School',
        'location' => 'Los Altos, CA',
        'method' => 'Student ID',
        'fields' => ['id'],
        'id' => [
            'label' => 'Student ID', 'placeholder' => '9-digit ID', 'maxlength' => 9, 'numeric' => true,
            'pattern' => '^\d{9}$',
            'hint' => 'Your 9-digit student ID number.',
            'error' => 'Enter your 9-digit student ID.',
        ],
        'destination' => 'A one-time code will be sent to the school email associated with your student ID.',
        'middleSchools' => ['Blach Intermediate School', 'Graham Middle School', 'Egan Junior High', 'Crittenden Middle School'],
    ],
    'gunn' => [
        'name' => 'Henry M. Gunn High School',
        'location' => 'Palo Alto, CA',
        'method' => 'Student ID',
        'fields' => ['id'],
        'id' => [
            'label' => 'Student ID number', 'placeholder' => 'Student ID', 'maxlength' => 10, 'numeric' => true,
            'pattern' => '^\d{4,10}$',   // length unconfirmed: tighten once known
            'hint' => 'The number you use as your username when signing in to ClassLink. Do not enter a password.',
            'error' => 'Enter your student ID number (digits only).',
        ],
        'destination' => 'A one-time code will be sent to the school account associated with your student ID.',
        'middleSchools' => ['Ellen Fletcher Middle School', 'Frank S. Greene Jr. Middle School', 'Jane Lathrop Stanford Middle School'],
    ],
    'stuy' => [
        'name' => 'Stuyvesant High School',
        'location' => 'Fullerton, CA',
        'method' => 'OSIS number + DOE email',
        'fields' => ['id', 'email'],
        'id' => [
            'label' => 'OSIS number', 'placeholder' => '9-digit OSIS', 'maxlength' => 9, 'numeric' => true,
            'pattern' => '^\d{9}$',
            'hint' => 'Your 9-digit student ID, printed on your report card and student ID card.',
            'error' => 'Enter your 9-digit OSIS number.',
        ],
        'email' => [
            'label' => 'NYC DOE student email', 'placeholder' => 'username@nycstudents.net',
            'pattern' => '^[A-Za-z0-9._-]+@nycstudents\.net$',
            'hint' => 'Your DOE student account address ends in @nycstudents.net.',
            'error' => 'Enter your @nycstudents.net email address.',
        ],
        'destination' => 'A one-time code will be sent to the DOE student email you enter.',
        'middleSchools' => null,
    ],
    'loyola' => [
        'name' => 'Loyola High School',
        'location' => 'Los Angeles, CA',
        'method' => 'School email',
        'fields' => ['email'],
        'email' => [
            'label' => 'Loyola school email', 'placeholder' => 'name@loyolahs.edu',
            'pattern' => '^[^@\s]+@([A-Za-z0-9-]+\.)*loyolahs\.edu$',
            'hint' => 'Use the @loyolahs.edu address issued by the school, not a personal email.',
            'error' => 'Enter your @loyolahs.edu school email address.',
        ],
        'destination' => 'A one-time code will be sent to the Loyola email you enter.',
        'middleSchools' => null,
    ],
    'tunis' => [
        'name' => 'Tunis Lake High School',
        'location' => 'Chula Vista, CA',
        'method' => 'Student ID + school email',
        'fields' => ['id', 'email'],
        'id' => [
            'label' => 'Student ID', 'placeholder' => 'Student ID', 'maxlength' => 10, 'numeric' => true,
            'pattern' => '^\d{4,10}$',
            'hint' => 'The ID number issued by your school.',
            'error' => 'Enter your student ID (digits only).',
        ],
        'email' => [
            'label' => 'School email', 'placeholder' => 'name@yourschool.edu',
            'pattern' => '^[^@\s]+@[^@\s]+\.[^@\s]+$',   // PLACEHOLDER: replace with the real domain
            'hint' => 'The email address issued by your school.',
            'error' => 'Enter your school email address.',
        ],
        'destination' => 'A one-time code will be sent to the school email you enter.',
        'middleSchools' => null,
    ],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= $viewState === 'lame_blocked' ? '403 Forbidden' : 'Verify your school · The Carve' ?></title>

<?php if ($viewState !== 'lame_blocked'): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<style>
:root {
    --bg: #ECE8E1;
    --surface: #FFFFFF;
    --surface-2: #FAF8F4;
    --line: #D6CFC2;
    --line-strong: #B9B1A2;
    --ink: #231F1A;
    --ink-2: #5B544A;
    --ink-3: #7F7769;
    --pine: #2B4A3C;
    --pine-dark: #203829;
    --rail: #24352C;
    --cinnamon: #B5532A;
    --error: #9B2F1F;
    --warn: #8A5A00;
    --ok: #2E6B45;
    --radius: 6px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
    font-size: 15px;
    line-height: 1.5;
    color: var(--ink);
    background: var(--bg);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

a { color: inherit; }
.hidden { display: none !important; }

/* ------------------------------------------------------------------ layout */
.shell { display: grid; grid-template-columns: minmax(300px, 380px) 1fr; min-height: 100vh; }

/* ------------------------------------------------------------------ left rail */
.rail {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 40px;
    background: var(--rail);
    color: #F1E8DA;
}
.rail-logo { display: block; height: 40px; width: auto; align-self: flex-start; position: relative; z-index: 1; }
.rail-main { position: relative; z-index: 1; margin: auto 0; padding: 48px 0; }
.rail h1 { font: 700 34px/1.12 'Playfair Display', Georgia, serif; letter-spacing: -.01em; color: #FBF5EA; }
.rail-rule { width: 32px; height: 3px; margin: 18px 0; background: var(--cinnamon); }
.rail-lead { max-width: 34ch; color: rgba(241,232,218,.78); }

.progress-steps { list-style: none; display: grid; gap: 18px; margin-top: 36px; }
.progress-steps li { display: grid; grid-template-columns: 24px 1fr; gap: 12px; }
.ps-mark {
    width: 24px; height: 24px; display: grid; place-items: center; border-radius: 50%;
    font-size: 12px; font-weight: 600; border: 1.5px solid rgba(241,232,218,.38); color: rgba(241,232,218,.7);
}
.ps-title { font-weight: 600; color: rgba(251,245,234,.72); }
.ps-desc { font-size: 13px; color: rgba(241,232,218,.58); }
.ps-done .ps-mark { background: rgba(241,232,218,.92); color: var(--rail); border-color: transparent; }
.ps-current .ps-mark { border-color: #F1E8DA; color: #F1E8DA; box-shadow: 0 0 0 3px rgba(241,232,218,.14); }
.ps-current .ps-title, .ps-done .ps-title { color: #FBF5EA; }

.rail-foot { position: relative; z-index: 1; font-size: 12.5px; color: rgba(241,232,218,.5); }

/* growth rings: the stump from the logo, barely there */
.rail-rings { position: absolute; left: -150px; bottom: -150px; width: 460px; height: 460px; pointer-events: none; }

/* ------------------------------------------------------------------ right panel */
.panel { display: flex; flex-direction: column; min-height: 100vh; padding: 20px 32px 24px; }
.panel-top { display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 6px 8px; font-size: 13px; color: var(--ink-2); }
.panel-top strong { font-weight: 600; color: var(--ink); overflow-wrap: anywhere; }
.panel-top a { color: var(--pine); font-weight: 600; text-underline-offset: 2px; }
.panel-main { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px 0; }

.card {
    width: 100%; max-width: 560px;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(35,31,26,.05), 0 10px 28px -18px rgba(35,31,26,.22);
}

.session-bar {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 12px 24px; border-radius: 8px 8px 0 0;
    background: var(--surface-2); border-bottom: 1px solid var(--line);
    font-size: 13px; color: var(--ink-2);
}
.session-bar strong { font-weight: 600; font-variant-numeric: tabular-nums; color: var(--ink); }
.session-bar.is-warning, .session-bar.is-warning strong { color: var(--warn); }

/* thin activity line under the session bar (toggled by the existing is-waiting logic) */
.progress { position: relative; height: 2px; overflow: hidden; }
.progress::after { content: ''; position: absolute; top: 0; bottom: 0; left: -40%; width: 40%; background: var(--pine); opacity: 0; }
.progress.is-waiting::after { opacity: 1; animation: indeterminate 1.2s ease-in-out infinite; }
@keyframes indeterminate { to { left: 100%; } }

/* ------------------------------------------------------------------ stages */
.stage { padding: 20px 24px; border-bottom: 1px solid var(--line); }
.stage:last-child { border-bottom: 0; }
.stage-head { display: flex; align-items: center; gap: 12px; min-height: 28px; }
.stage-head h2 { font-size: 16px; font-weight: 600; line-height: 1.3; }
.stage-num {
    flex: none; width: 24px; height: 24px; display: grid; place-items: center; border-radius: 50%;
    font-size: 12px; font-weight: 600; color: var(--ink-3); border: 1.5px solid var(--line-strong);
}
.stage-num .tick { display: none; }
.stage-summary { margin-left: auto; min-width: 0; font-size: 13px; color: var(--ink-2); text-align: right; overflow-wrap: anywhere; }
.stage-summary:empty { display: none; }
.stage-body { margin-top: 16px; }

.stage.is-locked .stage-head h2 { color: var(--ink-3); font-weight: 500; }
.stage.is-active .stage-num { background: var(--pine); border-color: var(--pine); color: #fff; }
.stage.is-done .stage-num { border-color: var(--ok); color: var(--ok); }
.stage.is-done .stage-num .num { display: none; }
.stage.is-done .stage-num .tick { display: block; }

.link-btn { background: none; border: 0; padding: 2px 0; font: 600 13px Inter, sans-serif; color: var(--pine); text-decoration: underline; text-underline-offset: 2px; cursor: pointer; }
.link-btn:focus-visible { outline: 2px solid var(--pine); outline-offset: 2px; border-radius: 2px; }

/* school list */
.school-list { border: 0; }
.school-list legend { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
.school-opt {
    position: relative; display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border: 1px solid var(--line); border-radius: var(--radius);
    background: var(--surface); cursor: pointer; transition: border-color .15s, background .15s;
}
.school-opt + .school-opt { margin-top: 8px; }
.school-opt:hover { border-color: var(--line-strong); }
.school-opt:has(.sr-radio:checked) { border-color: var(--pine); background: #F4F7F5; }
.school-opt:has(.sr-radio:disabled) { cursor: not-allowed; }
.sr-radio { position: absolute; opacity: 0; width: 1px; height: 1px; }
.radio-dot { flex: none; width: 18px; height: 18px; border-radius: 50%; border: 1.5px solid var(--line-strong); background: #fff; }
.sr-radio:checked + .radio-dot { border-color: var(--pine); background: var(--pine); box-shadow: inset 0 0 0 3.5px #fff; }
.sr-radio:focus-visible + .radio-dot { outline: 2px solid var(--pine); outline-offset: 2px; }
.sr-radio:disabled ~ .school-text { opacity: .6; }
.school-text { display: flex; flex-direction: column; min-width: 0; }
.school-name { font-weight: 600; }
.school-meta { font-size: 13px; color: var(--ink-3); }
.school-missing { margin-top: 14px; font-size: 13px; color: var(--ink-3); }
.school-missing a { color: var(--pine); font-weight: 600; text-underline-offset: 2px; }

/* form fields */
.school-note { font-size: 13.5px; color: var(--ink-2); margin-bottom: 4px; }
.group { margin-top: 16px; }
.group > label, .label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; }
.input {
    width: 100%; height: 44px; padding: 0 12px;
    background: #fff; color: var(--ink);
    border: 1px solid var(--line-strong); border-radius: var(--radius);
    font: inherit; transition: border-color .15s, box-shadow .15s;
}
.input::placeholder { color: #9A9283; }
.input:hover:not(:disabled):not([readonly]) { border-color: #8F8777; }
.input:focus { outline: none; border-color: var(--pine); box-shadow: 0 0 0 3px rgba(43,74,60,.18); }
.input:disabled, .input[readonly] { background: #F3F0EA; color: var(--ink-2); cursor: not-allowed; }
.input[aria-invalid="true"] { border-color: var(--error); }
select.input {
    appearance: none; -webkit-appearance: none; padding-right: 36px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5l5 5 5-5' fill='none' stroke='%235B544A' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
}
.help { margin-top: 6px; font-size: 13px; color: var(--ink-3); }
.help:empty { display: none; }
.field-error, .errorstuff { margin-top: 6px; font-size: 13px; color: var(--error); }
.field-error:empty, .errorstuff:empty { display: none; }
.errorstuff { margin-top: 12px; font-size: 13.5px; }

details.optional summary { font-size: 13.5px; color: var(--ink-2); cursor: pointer; width: fit-content; }
details.optional summary:focus-visible { outline: 2px solid var(--pine); outline-offset: 2px; border-radius: 2px; }
details.optional[open] summary { margin-bottom: 8px; }

.button {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; height: 46px; margin-top: 20px;
    background: var(--pine); color: #fff; border: 0; border-radius: var(--radius);
    font: 600 15px Inter, sans-serif; text-decoration: none; cursor: pointer;
    transition: background .15s;
}
.button:hover:not(:disabled) { background: var(--pine-dark); }
.button:focus-visible { outline: 2px solid var(--pine); outline-offset: 2px; }
.button:disabled { background: #C9C3B6; color: #F5F2EC; cursor: not-allowed; }

.code-status { font-size: 13.5px; color: var(--ink-2); }
.code-input { letter-spacing: .08em; }

.spin { animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ------------------------------------------------------------------ assurances + footer */
.assure { list-style: none; display: grid; gap: 8px; width: 100%; max-width: 560px; margin-top: 20px; font-size: 13px; color: var(--ink-2); }
.assure li { display: grid; grid-template-columns: 16px 1fr; gap: 10px; align-items: start; }
.assure svg { margin-top: 3px; color: var(--ink-3); }
.panel-foot { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px 20px; padding-top: 12px; font-size: 12.5px; color: var(--ink-3); }
.panel-foot a { color: var(--ink-3); text-decoration: none; }
.panel-foot a:hover { color: var(--ink); text-decoration: underline; }

/* ------------------------------------------------------------------ notices (expired / invalid) */
.notice-wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
.notice {
    width: 100%; max-width: 460px; padding: 32px;
    background: var(--surface); border: 1px solid var(--line); border-radius: 8px;
    box-shadow: 0 1px 2px rgba(35,31,26,.05), 0 10px 28px -18px rgba(35,31,26,.22);
}
.wordmark { font: 700 20px 'Playfair Display', Georgia, serif; color: var(--rail); margin-bottom: 24px; }
.notice h1 { font: 700 24px/1.25 'Playfair Display', Georgia, serif; margin-bottom: 10px; }
.notice p { color: var(--ink-2); }
.notice .help-link { display: block; margin-top: 16px; text-align: center; font-size: 13px; color: var(--ink-3); }

/* ------------------------------------------------------------------ small screens */
@media (max-width: 920px) {
    .shell { grid-template-columns: 1fr; min-height: 0; }
    .rail { padding: 20px 24px; }
    .rail-main { margin: 0; padding: 14px 0 0; }
    .rail h1 { font-size: 22px; }
    .rail-rule, .rail-lead, .progress-steps, .rail-foot, .rail-rings { display: none; }
    .panel { min-height: 0; padding: 16px; }
    .panel-top { justify-content: flex-start; }
    .stage { padding: 18px 16px; }
    .session-bar { padding: 12px 16px; }
}

@media (prefers-reduced-motion: reduce) {
    .progress.is-waiting::after, .spin { animation: none; }
    .school-opt, .input, .button { transition: none; }
}
</style>
<?php else: ?>
<style>
    /* Extremely lame, boring CSS for snooping users */
    body { background-color: #fff; color: #000; font-family: monospace; padding: 20px; }
    h1 { font-size: 24px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
    p { margin-top: 10px; }
</style>
<?php endif; ?>
</head>
<body>

<?php if ($viewState === 'valid'): ?>

<div class="shell" id="mainUI">

    <aside class="rail">
        <img class="rail-logo" src="/project/public/assets/logo/white-trans-logo.png" alt="The Carve">

        <div class="rail-main">
            <h1>Verify your enrollment</h1>
            <div class="rail-rule" aria-hidden="true"></div>
            <p class="rail-lead">The Carve is a private network for students. We confirm that you attend a participating school before granting access.</p>

            <ol class="progress-steps">
                <li class="ps-done">
                    <span class="ps-mark" aria-hidden="true"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                    <span><span class="ps-title">Signed in</span><br><span class="ps-desc">Google account confirmed</span></span>
                </li>
                <li class="ps-current" aria-current="step">
                    <span class="ps-mark" aria-hidden="true">2</span>
                    <span><span class="ps-title">Verify your school</span><br><span class="ps-desc">Confirm enrollment with a one-time code</span></span>
                </li>
                <li class="ps-pending">
                    <span class="ps-mark" aria-hidden="true">3</span>
                    <span><span class="ps-title">Enter The Carve</span><br><span class="ps-desc">Join your school's class hubs</span></span>
                </li>
            </ol>
        </div>

        <div class="rail-foot">&copy; 2026 The Carve</div>

        <svg class="rail-rings" viewBox="0 0 460 460" aria-hidden="true" focusable="false">
            <g fill="none" stroke="rgba(241,232,218,.07)" stroke-width="1.2">
                <ellipse cx="230" cy="230" rx="30" ry="27"/>
                <ellipse cx="230" cy="230" rx="62" ry="58"/>
                <ellipse cx="231" cy="230" rx="94" ry="90"/>
                <ellipse cx="231" cy="231" rx="128" ry="121"/>
                <ellipse cx="232" cy="231" rx="160" ry="154"/>
                <ellipse cx="232" cy="232" rx="194" ry="186"/>
                <ellipse cx="233" cy="232" rx="226" ry="218"/>
            </g>
        </svg>
    </aside>

    <div class="panel">
        <div class="panel-top">
            <span>Signed in as <strong><?= htmlspecialchars($email) ?></strong></span>
            <span aria-hidden="true">&middot;</span>
            <a href="/logout">Not you? Sign out</a>
        </div>

        <main class="panel-main">
            <div class="card">
                <div class="session-bar" id="sessionBar" role="timer">
                    <span>Secure verification session</span>
                    <span>Expires in <strong id="countdownTimer"><?= sprintf('%02d:%02d', intdiv($sessionSeconds, 60), $sessionSeconds % 60) ?></strong></span>
                </div>
                <div class="progress" id="connectionFlow" aria-hidden="true"></div>

                <noscript><p class="errorstuff" style="padding:16px 24px 0">JavaScript is required to verify your school.</p></noscript>

                <!-- 1 · SCHOOL -->
                <section class="stage is-active" id="stageSchool" aria-current="step">
                    <div class="stage-head">
                        <span class="stage-num" aria-hidden="true"><span class="num">1</span><svg class="tick" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <h2>Choose your school</h2>
                        <span class="stage-summary" id="schoolSummary"></span>
                        <button type="button" class="link-btn hidden" id="changeSchool">Change</button>
                    </div>
                    <div class="stage-body" id="schoolBody">
                        <fieldset class="school-list lockable">
                            <legend>School</legend>
                            <?php foreach ($schools as $key => $s): ?>
                            <label class="school-opt">
                                <input type="radio" name="highSchool" class="school-radio sr-radio" value="<?= htmlspecialchars($key) ?>">
                                <span class="radio-dot" aria-hidden="true"></span>
                                <span class="school-text">
                                    <span class="school-name"><?= htmlspecialchars($s['name']) ?></span>
                                    <span class="school-meta"><?= htmlspecialchars(implode(' · ', array_filter([$s['location'], $s['method']]))) ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <button type="button" class="button" id="continueSchool" disabled>Continue</button>
                        <p class="school-missing">School not listed? <a href="/help">Request campus onboarding</a></p>
                    </div>
                </section>

                <!-- 2 · IDENTITY -->
                <section class="stage is-locked" id="stageDetails">
                    <div class="stage-head">
                        <span class="stage-num" aria-hidden="true"><span class="num">2</span><svg class="tick" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <h2>Confirm your identity</h2>
                        <span class="stage-summary" id="detailsSummary"></span>
                    </div>
                    <form class="stage-body hidden" id="detailsBody" novalidate>
                        <p class="school-note" id="schoolNote"></p>

                        <div class="group lockable" id="idGroup">
                            <label for="studentID" id="idLabel">Student ID</label>
                            <input id="studentID" class="input" inputmode="numeric" autocomplete="off" maxlength="9" placeholder="Student ID" aria-describedby="idHelp idError">
                            <p class="help" id="idHelp"></p>
                            <p class="field-error" id="idError" role="alert"></p>
                        </div>

                        <div class="group lockable hidden" id="emailGroup">
                            <label for="schoolEmail" id="emailLabel">School email</label>
                            <input id="schoolEmail" type="email" class="input" autocomplete="off" autocapitalize="off" spellcheck="false" aria-describedby="emailHelp emailError">
                            <p class="help" id="emailHelp"></p>
                            <p class="field-error" id="emailError" role="alert"></p>
                        </div>

                        <div class="group lockable">
                            <label for="grade">Current grade</label>
                            <select class="input" id="grade">
                                <option>Select...</option><option>9</option><option>10</option><option>11</option><option>12</option>
                            </select>
                        </div>

                        <div class="group lockable hidden" id="schoolSelectgroup">
                            <label for="school">Middle school</label>
                            <select class="input" id="school">
                                <option>Select...</option>
                            </select>
                            <input id="otherSchoolInput" class="input hidden" style="margin-top: 10px;" placeholder="Middle school name" autocomplete="off">
                        </div>

                        <details class="group lockable optional" id="customMessagegroup">
                            <summary>Add a message (optional)</summary>
                            <input id="customMessage" maxlength="100" class="input" placeholder="Up to 100 characters" aria-label="Optional message">
                        </details>

                        <button type="submit" class="button" id="submitRequest" disabled>
                            <span id="btnText">Send verification code</span>
                        </button>
                        <div class="errorstuff" id="error_place" role="alert"></div>
                    </form>
                </section>

                <!-- 3 · CODE -->
                <section class="stage is-locked" id="stageCode">
                    <div class="stage-head">
                        <span class="stage-num" aria-hidden="true"><span class="num">3</span><svg class="tick" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <h2>Enter your verification code</h2>
                    </div>
                    <form class="stage-body hidden" id="verifyArea" novalidate>
                        <p class="code-status" id="codeStatus" role="status"></p>
                        <div class="group">
                            <label for="verificationCodeInput">Verification code</label>
                            <input class="input code-input" id="verificationCodeInput" placeholder="Enter code" autocomplete="one-time-code" autocapitalize="off" spellcheck="false">
                        </div>
                        <button type="submit" class="button" id="verificationCodeButton">
                            <span id="verifyBtnText">Verify and continue</span>
                        </button>
                        <div class="errorstuff" id="error_place_verify" role="alert"></div>
                    </form>
                </section>
            </div>

            <ul class="assure">
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>A one-time code is sent only to your school-issued email.</span></li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>We never ask for your school password.</span></li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><span>Code attempts are limited, and this session expires after <?= $sessionMinutes ?> minutes.</span></li>
            </ul>
        </main>

        <footer class="panel-foot">
            <a href="/help">Help</a>
            <a href="/privacy-policy">Privacy</a>
            <a href="/about" target="_blank" rel="noopener">About The Carve</a>
        </footer>
    </div>
</div>

<div class="notice-wrap hidden" id="blockedUI">
    <div class="notice" role="alert">
        <div class="wordmark">The Carve</div>
        <h1>Your session has expired</h1>
        <p>For your security, verification sessions expire after <?= $sessionMinutes ?> minutes. Sign in again to start over.</p>
        <a class="button" href="/logout">Sign in again</a>
        <a class="help-link" href="/help">Need help?</a>
    </div>
</div>

<script>
const SESSION_SECONDS = <?= (int)$sessionSeconds ?>;
const SCHOOLS = <?= json_encode($schools, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const SPINNER = '<svg class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.55"/></svg>';

// TIMER LOGIC
const endTime = Date.now() + (SESSION_SECONDS * 1000); // exact expiration timestamp
const timerDisplay = document.getElementById("countdownTimer");
const sessionBar = document.getElementById("sessionBar");
const mainUI = document.getElementById("mainUI");
const blockedUI = document.getElementById("blockedUI");

function tickTimer() {
    // Check the actual system clock right now
    const timeLeft = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;

    timerDisplay.textContent = String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    sessionBar.classList.toggle("is-warning", timeLeft <= 60);

    if (timeLeft <= 0) {
        clearInterval(countdownInterval);
        mainUI.classList.add("hidden");
        blockedUI.classList.remove("hidden");
    }
}
const countdownInterval = setInterval(tickTimer, 1000);
tickTimer();

// ELEMENTS
const id = document.getElementById("studentID");
const schoolEmail = document.getElementById("schoolEmail");
const customMsg = document.getElementById("customMessage");
const gradeSelector = document.getElementById("grade");
const submitBtn = document.getElementById("submitRequest");
const verifyBtn = document.getElementById("verificationCodeButton");
const connectionFlow = document.getElementById("connectionFlow");
const btnText = document.getElementById("btnText");

const schoolRadios = document.querySelectorAll(".school-radio");
const changeSchoolBtn = document.getElementById("changeSchool");
const continueBtn = document.getElementById("continueSchool");
const stageSchool = document.getElementById("stageSchool");
const stageDetails = document.getElementById("stageDetails");
const stageCode = document.getElementById("stageCode");
const schoolBody = document.getElementById("schoolBody");
const detailsBody = document.getElementById("detailsBody");
const verifyArea = document.getElementById("verifyArea");
const idGroup = document.getElementById("idGroup");
const emailGroup = document.getElementById("emailGroup");
const codeStatus = document.getElementById("codeStatus");

var waiting = false;
var attempts = 0;
var codeSent = false;
var selectedKey = null;
var blocked = false;

function currentSchool() { return selectedKey ? SCHOOLS[selectedKey] : null; }
function esc(s) { return String(s).replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])); }

function setStage(stage, state, summary) {
    stage.classList.remove("is-active", "is-done", "is-locked");
    stage.classList.add("is-" + state);
    if (state === "active") stage.setAttribute("aria-current", "step"); else stage.removeAttribute("aria-current");
    const sum = stage.querySelector(".stage-summary");
    if (sum && summary !== undefined) sum.textContent = summary;
}

function showError(input, errEl, message) {
    input.setAttribute("aria-invalid", "true");
    errEl.textContent = message;
}
function clearError(input, errEl) {
    input.removeAttribute("aria-invalid");
    errEl.textContent = "";
}

function maskEmail(value) {
    const parts = value.split("@");
    const local = parts[0] || "";
    return (local[0] || "") + "•".repeat(Math.min(6, Math.max(3, local.length - 1))) + "@" + (parts[1] || "");
}
function destinationLabel() {
    const s = currentSchool();
    if (s.fields.includes("email")) return maskEmail(schoolEmail.value.trim());
    return "the school email associated with ID ending " + id.value.slice(-4);
}

// ---- SCHOOL SELECTION ------------------------------------------------------
function syncMiddleSchool() {
    document.getElementById("schoolSelectgroup").classList.toggle("hidden", gradeSelector.value !== "9");
}

function applySchool(key) {
    if (blocked) return;
    const changed = key !== selectedKey;   // keep what was typed if the same school is re-confirmed
    selectedKey = key;
    const s = SCHOOLS[key];

    document.getElementById("schoolNote").textContent = s.destination;

    // identifier fields for this school
    const hasId = s.fields.includes("id");
    const hasEmail = s.fields.includes("email");
    idGroup.classList.toggle("hidden", !hasId);
    emailGroup.classList.toggle("hidden", !hasEmail);

    if (changed) {
        id.value = ""; schoolEmail.value = "";
        clearError(id, document.getElementById("idError"));
        clearError(schoolEmail, document.getElementById("emailError"));
    }

    if (hasId) {
        document.getElementById("idLabel").textContent = s.id.label;
        id.placeholder = s.id.placeholder;
        id.maxLength = s.id.maxlength;
        document.getElementById("idHelp").textContent = s.id.hint;
    }
    if (hasEmail) {
        document.getElementById("emailLabel").textContent = s.email.label;
        schoolEmail.placeholder = s.email.placeholder;
        document.getElementById("emailHelp").textContent = s.email.hint;
    }

    // middle school: curated list when we have one, free text otherwise
    const dropdown = document.getElementById("school");
    const other = document.getElementById("otherSchoolInput");
    if (changed) other.value = "";
    if (!changed) { /* same school: leave the middle-school controls as they are */ }
    else if (Array.isArray(s.middleSchools) && s.middleSchools.length) {
        dropdown.innerHTML = "<option>Select...</option>" +
            s.middleSchools.map(n => "<option>" + esc(n) + "</option>").join("") +
            '<option value="Other">Other</option>';
        dropdown.classList.remove("hidden");
        other.classList.add("hidden");
    } else {
        dropdown.innerHTML = "";
        dropdown.classList.add("hidden");
        other.classList.remove("hidden");
    }
    syncMiddleSchool();

    document.getElementById("error_place").textContent = "";

    // advance: school done, details active
    setStage(stageSchool, "done", s.name);
    schoolBody.classList.add("hidden");
    changeSchoolBtn.classList.remove("hidden");
    setStage(stageDetails, "active");
    detailsBody.classList.remove("hidden");
    submitBtn.disabled = false;
    (hasId ? id : schoolEmail).focus();
}

schoolRadios.forEach(r => r.addEventListener("change", () => { continueBtn.disabled = blocked; }));
continueBtn.addEventListener("click", () => {
    const checked = document.querySelector(".school-radio:checked");
    if (checked) applySchool(checked.value);
});

changeSchoolBtn.addEventListener("click", () => {
    if (waiting || codeSent) return;
    setStage(stageSchool, "active", "");
    schoolBody.classList.remove("hidden");
    changeSchoolBtn.classList.add("hidden");
    setStage(stageDetails, "locked");
    detailsBody.classList.add("hidden");
    submitBtn.disabled = true;
    const checked = document.querySelector(".school-radio:checked");
    if (checked) checked.focus();
});

// ---- INPUT HANDLERS ------------------------------------------------------------
id.addEventListener("input", () => {
    const s = currentSchool();
    if (s && s.id && s.id.numeric) id.value = id.value.replace(/\D/g, "").slice(0, s.id.maxlength);
    clearError(id, document.getElementById("idError"));
});
schoolEmail.addEventListener("input", () => clearError(schoolEmail, document.getElementById("emailError")));

gradeSelector.addEventListener("change", syncMiddleSchool);

// ---- LOCK / UNLOCK (unchanged behaviour) ----------------------------------------
function lockInput() {
    waiting = true;
    submitBtn.disabled = true;
    btnText.innerHTML = SPINNER + " Processing...";
    connectionFlow.classList.add("is-waiting");
    document.querySelectorAll('.lockable input').forEach( item => { item.readOnly = true; });
    document.querySelectorAll('.lockable select').forEach( item => { item.disabled = true; });
    schoolRadios.forEach(r => { r.disabled = true; });
    changeSchoolBtn.classList.add("hidden");
}

function unlockInputDueToError(error = "We couldn't send the code. Please try again, or contact support if this continues.") {
    waiting = false;
    document.getElementById("error_place").innerText = error;
    verifyArea.classList.add("hidden");
    setStage(stageCode, "locked");
    setStage(stageDetails, "active", "");
    detailsBody.classList.remove("hidden");
    document.querySelectorAll('.lockable input').forEach( item => { item.readOnly = false; });
    document.querySelectorAll('.lockable select').forEach( item => { item.disabled = false; });
    schoolRadios.forEach(r => { r.disabled = false; });
    changeSchoolBtn.classList.remove("hidden");
    submitBtn.disabled = false;
    btnText.textContent = "Try again";
    connectionFlow.classList.remove("is-waiting");
}

function lockFully() {
    waiting = false;
    document.querySelectorAll('.group input').forEach( item => { item.readOnly = true; });
    document.querySelectorAll('.group select').forEach( item => { item.disabled = true; });
    verifyBtn.disabled = true;
    submitBtn.disabled = true;
    connectionFlow.classList.remove("is-waiting");
}

function unlockFinal(error = "Network error. Please try again.") {
    waiting = false;
    document.getElementById("error_place_verify").innerText = error;
    document.getElementById("verificationCodeInput").readOnly = false;
    document.getElementById("verificationCodeInput").disabled = false;
    verifyBtn.disabled = false;
    document.getElementById("verifyBtnText").textContent = "Verify and continue";
    document.querySelectorAll('.group select').forEach( item => { item.disabled = false; });
}

function blockEmail() {
    waiting = false;
    blocked = true;
    schoolRadios.forEach(r => { r.disabled = true; });
    changeSchoolBtn.classList.add("hidden");
    continueBtn.disabled = true;
    document.getElementById("error_place").innerText = "We couldn't verify this account. Please contact support using the Help link below.";
    submitBtn.disabled = true;
    lockFully();
}

function finish() {
    window.location.href = "/project/welcome";
}

// ---- STEP 2: SEND CODE ------------------------------------------------------------
function validateDetails() {
    const s = currentSchool();
    let firstBad = null;

    if (s.fields.includes("id") && !new RegExp(s.id.pattern).test(id.value)) {
        showError(id, document.getElementById("idError"), s.id.error);
        if (!firstBad) firstBad = id;
    }
    if (s.fields.includes("email") && !new RegExp(s.email.pattern, "i").test(schoolEmail.value.trim())) {
        showError(schoolEmail, document.getElementById("emailError"), s.email.error);
        if (!firstBad) firstBad = schoolEmail;
    }
    if (firstBad) { firstBad.focus(); return false; }
    return true;
}

detailsBody.addEventListener("submit", (e) => {
    e.preventDefault();
    if (waiting || codeSent || !currentSchool()) return;
    document.getElementById("error_place").textContent = "";
    if (!validateDetails()) return;

    lockInput();
    verifyArea.classList.remove("hidden");
    setStage(stageCode, "active");
    codeStatus.textContent = "Sending code...";
    verifyArea.scrollIntoView({ behavior: "smooth", block: "nearest" });
    sendVerification();
});

async function sendVerification() {
    const s = currentSchool();
    const payload = { school: selectedKey, msg: customMsg.value };
    if (s.fields.includes("id")) payload.id = id.value;
    if (s.fields.includes("email")) payload.email = schoolEmail.value.trim().toLowerCase();

    let response;
    try {
        response = await fetch("/project/api/sendVerification",{
            method:"POST", headers: { "Content-Type":"application/json" },
            body: JSON.stringify(payload)
        });
    } catch(e) { unlockInputDueToError(); return; }

    if (!response.ok) { unlockInputDueToError(); blockEmail(); return; }
    const data = await response.json();
    connectionFlow.classList.remove("is-waiting");

    if (data.success !== true) {
        if (data.success === "false-but-may-redo") {
            unlockInputDueToError("Something went wrong. Please try again.");
        } else { unlockInputDueToError(); blockEmail(); }
        return;
    }

    codeSent = true;
    waiting = false;
    btnText.textContent = "Code sent";
    setStage(stageDetails, "done", destinationLabel());
    detailsBody.classList.add("hidden");
    codeStatus.textContent = "We sent a one-time code to " + destinationLabel() + ". Enter it below.";
    document.getElementById("verificationCodeInput").focus();
}

// ---- STEP 3: VERIFY CODE ----------------------------------------------------------
verifyArea.addEventListener("submit", (e) => {
    e.preventDefault();
    if (waiting) return;
    const vCode = document.getElementById("verificationCodeInput").value.trim();
    document.getElementById("error_place_verify").innerText = "";
    if (vCode === "") { document.getElementById("error_place_verify").innerText = "Enter the code we sent you."; return; }

    document.getElementById("verifyBtnText").innerHTML = SPINNER + " Verifying...";
    lockFully(); sendFinalVerification(vCode);
});

async function sendFinalVerification(vCode) {
    let response;
    try {
         response = await fetch("/project/api/finishSignUpVerification",{
            method:"POST", headers: { "Content-Type":"application/json" },
            body: JSON.stringify({ code: vCode })
        });
    } catch (e) { unlockFinal(); return; }

    const data = await response.json();
    if (data.success === true) { finish(); return; }

    if (response.status === 401 && (data.attempts !== undefined && data.attempts !== null)) {
        attempts = data.attempts;
        let errorMessage = data.error || '';
        if (errorMessage === "wrong_code") {
            unlockFinal("Incorrect code. Attempts: " + attempts);
        } else if (errorMessage === "out_of_attempts"){
            unlockFinal("Incorrect code. No attempts remaining (" + attempts + ").");
            lockFully();
            setTimeout(() => { window.location.href = "/verification"; }, 700);
        }
    } else if (response.status === 403){
        unlockFinal("This session is no longer valid.");
        lockFully();
        setTimeout(() => { window.location.href = "/verification"; }, 700);
    } else {
        unlockFinal("Something went wrong. Attempts: " + attempts + ". Please use the Help link below.");
    }
}

// ---- MIDDLE SCHOOL "OTHER" (unchanged) --------------------------------------------
const schoolDropdown = document.getElementById("school");
const otherSchoolInput = document.getElementById("otherSchoolInput");
schoolDropdown.addEventListener("change", () => {
    if (schoolDropdown.value === "Other") {
        otherSchoolInput.classList.remove("hidden"); otherSchoolInput.focus();
    } else {
        otherSchoolInput.classList.add("hidden"); otherSchoolInput.value = "";
    }
});
</script>

<?php elseif ($viewState === 'cool_blocked'): ?>

<div class="notice-wrap" id="blockedUI">
    <div class="notice" role="alert">
        <div class="wordmark">The Carve</div>
        <h1>This session isn't valid</h1>
        <p>Verification can't continue from your current session. It may have expired, or this page was opened directly. Sign in again to start over.</p>
        <a class="button" href="/logout">Sign in again</a>
        <a class="help-link" href="/help">Need help?</a>
    </div>
</div>

<?php else:  ?>

<h1>403 Forbidden</h1>
<p>Directory access is forbidden. Client does not have appropriate permissions to view this resource.</p>

<?php endif; ?>

</body>
</html>