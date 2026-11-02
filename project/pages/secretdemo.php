<?php
 
$convoId = App::$publicConversation;

$conversationData = ConversationRepository::getConvoInfo($convoId, $request->dbconn);
$readOnly = ($conversationData['access_mode'] == 1) ? true : false;

// ---------------------------------------------------------------------------
// Header view-model (keeps logic out of the markup below)
// ---------------------------------------------------------------------------
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
 
$convoName = (string)($conversationData['name'] ?? 'Conversation');
$userName  = trim((string)($request->session['name'] ?? 'User Name'));
$request->session['user_id'] = -10;
$request->session["request_limiter_bypass"] = true;
 
$initialsOf = static function (string $name): string {
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return '?';
    }
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
};
 
// Header theme. 'light' = paper header + colour logo (default, matches the
// birch canvas below it). 'pine' = dark pine header + white logo, which echoes
// the home-page sidebar. Flip this one value to compare the two.
$headerTheme = 'light';                      // 'light' | 'pine'
$logoSrc = ($headerTheme === 'pine')
    ? '/project/public/assets/logo/white-trans-logo.png'
    : '/project/public/assets/logo/main-logo-full.png';
$headerClass = ($headerTheme === 'pine') ? 'carve-header carve-header--pine' : 'carve-header';
 
?>
<!DOCTYPE html>
<html lang="en">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h($convoName) ?> &middot; The Carve</title>
    <link rel="icon" type="image/png" href="/favicon-32x32.png">
 
    <!-- Brand fonts (same link as home.php; this page previously had none) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600&display=swap" rel="stylesheet">
 
    <link rel="stylesheet" href="public/css/convo.css">

    <!-- Katex Imports for React Modules to use -->
    <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/katex@0.19.1/dist/katex.min.css"
    />

    <script
    defer
    src="https://cdn.jsdelivr.net/npm/katex@0.19.1/dist/katex.min.js"
    ></script>

    <script
    defer
    src="https://cdn.jsdelivr.net/npm/katex@0.19.1/dist/contrib/auto-render.min.js"
    ></script>
 
    <style>
        /* =====================================================================
           APP SHELL
           The page is exactly one viewport tall. The only things that scroll
           are the scroll areas React defines (lobby, plaza feed, thread page).
           Previously an <h1> + link sat above a height:100vh React root, so the
           document was ~130px taller than the screen and the bottom of every
           view (e.g. a tall sidebar) fell below the fold.
           ===================================================================== */
        html, body { height: 100%; }
 
        body.convo-page {
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            background: var(--birch-canvas, #F8F2EC);
            color: var(--ink-dark, #36221A);
            font-family: 'Nunito', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
 
        #react-root {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
 
        /* Fill the space under the header instead of forcing 100vh.
           (ID selector on purpose: beats any #react_screen / .carve-editorial-screen height.) */
        #react-root > .carve-editorial-screen {
            flex: 1;
            height: auto;
            min-height: 0;
        }
 
        /* =====================================================================
           HEADER
           Colours + slider tokens live here so React's tab slider (portalled
           into #convo-tabs-slot) inherits whichever theme is active.
           ===================================================================== */
        .carve-header {
            --hdr-bg: var(--paper-surface, #FFFDFA);
            --hdr-fg: var(--ink-dark, #36221A);
            --hdr-muted: var(--ink-meta, #826658);
            --hdr-line: var(--carve-line-dark, #D4BCA9);
            --hdr-lip: rgba(255, 255, 255, .9);
            --hdr-accent: var(--cedar-red, #A34830);
            --hdr-pill-bg: rgba(163, 72, 48, .10);
            --hdr-avatar-bg: var(--pine-green, #2B4E3E);
            --hdr-avatar-fg: #FFFDFA;
            --hdr-btn-border: var(--ink-meta, #826658);
            --hdr-focus: var(--pine-green, #2B4E3E);
 
            --tabs-w: 360px;
            --tabs-h: 42px;
            --tabs-track: var(--creator-bg, #F4EBE3);
            --tabs-groove: inset 0 2px 4px rgba(54, 34, 26, .10), inset 0 -1px 0 rgba(255, 255, 255, .85);
            --tabs-key: var(--pine-green, #2B4E3E);
            --tabs-key-edge: var(--pine-green-hover, #1F3B2E);
            --tabs-key-shadow: rgba(31, 59, 46, .35);
            --tabs-text: var(--ink-meta, #826658);
            --tabs-text-hover: var(--ink-dark, #36221A);
            --tabs-text-active: #FFFDFA;
            --tabs-focus: var(--pine-green, #2B4E3E);
 
            position: relative;
            z-index: 1;
            flex: none;
            box-sizing: border-box;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            grid-template-areas: "left tabs right";
            align-items: center;
            column-gap: 1.5rem;
            min-height: 68px;
            padding: .65rem 1.5rem;
            background: var(--hdr-bg);
            color: var(--hdr-fg);
            border-bottom: 1px solid var(--hdr-line);
            /* highlight lip under the rule = "carved" edge, then a soft lift */
            box-shadow: 0 1px 0 var(--hdr-lip), 0 8px 20px -12px rgba(54, 34, 26, .22);
            font-family: 'Nunito', system-ui, sans-serif;
        }
        .carve-header *, .carve-header *::before, .carve-header *::after { box-sizing: border-box; }
 
        /* Pine theme: same structure, home-page palette. Colour logo -> white logo. */
        .carve-header--pine {
            --hdr-bg: #2C3E35;
            --hdr-fg: #F5E6D3;
            --hdr-muted: rgba(245, 230, 211, .62);
            --hdr-line: rgba(0, 0, 0, .30);
            --hdr-lip: rgba(255, 255, 255, .07);
            --hdr-accent: #F0B393;
            --hdr-pill-bg: rgba(196, 85, 37, .24);
            --hdr-avatar-bg: #F5E6D3;
            --hdr-avatar-fg: #2C3E35;
            --hdr-btn-border: rgba(245, 230, 211, .40);
            --hdr-focus: #F5E6D3;
 
            --tabs-track: rgba(0, 0, 0, .24);
            --tabs-groove: inset 0 1px 3px rgba(0, 0, 0, .35), inset 0 -1px 1px rgba(255, 255, 255, .08);
            --tabs-key: #C45525;
            --tabs-key-edge: #9D3C16;
            --tabs-key-shadow: rgba(0, 0, 0, .40);
            --tabs-text: rgba(245, 230, 211, .62);
            --tabs-text-hover: #F5E6D3;
            --tabs-text-active: #FFFFFF;
            --tabs-focus: #F5E6D3;
        }
 
        /* --- left: brand + where am I ------------------------------------- */
        .carve-header__left  { grid-area: left;  display: flex; align-items: center; gap: 1rem; min-width: 0; }
        .carve-header__right { grid-area: right; display: flex; align-items: center; justify-content: flex-end; gap: .9rem; min-width: 0; }
 
        .carve-header__brand { flex: none; display: inline-flex; border-radius: 8px; }
        .carve-header__logo  { display: block; height: 34px; width: auto; }
        .carve-header__divider { flex: none; width: 1px; height: 28px; background: var(--hdr-line); }
 
        .carve-header__title { display: flex; align-items: center; gap: .65rem; min-width: 0; }
        .carve-header__name {
            margin: 0;
            min-width: 0;
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -.01em;
            color: var(--hdr-fg);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
 
        .carve-pill {
            flex: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .7rem;
            border-radius: 20px;
            background: var(--hdr-pill-bg);
            color: var(--hdr-accent);
            font-size: .68rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .carve-pill--muted { background: transparent; color: var(--hdr-muted); box-shadow: inset 0 0 0 1px var(--hdr-line); }
 
        /* --- centre: tab slot (React portals the slider in) ---------------- */
        .carve-tabs-slot { grid-area: tabs; width: var(--tabs-w); max-width: 100%; }
 
        /* Same footprint as the real slider so nothing shifts when React mounts */
        .carve-tabs-skel {
            width: 100%;
            height: var(--tabs-h);
            border-radius: 12px;
            background: var(--tabs-track);
            box-shadow: var(--tabs-groove);
        }
        .carve-tabs-slot.is-mounted > .carve-tabs-skel { display: none; }
 
        /* --- right: who am I + way out -------------------------------------- */
        .carve-user { display: flex; align-items: center; gap: .55rem; min-width: 0; }
        .carve-user__avatar {
            flex: none;
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--hdr-avatar-bg);
            color: var(--hdr-avatar-fg);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .02em;
            user-select: none;
            /* inlaid ring: gap of header colour, then a hairline */
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .22), 0 0 0 2px var(--hdr-bg), 0 0 0 3px var(--hdr-line);
        }
        .carve-user__name {
            max-width: 11rem;
            font-size: .9rem;
            font-weight: 700;
            color: var(--hdr-fg);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
 
        .carve-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .85rem;
            font: 700 .88rem/1.2 'Nunito', system-ui, sans-serif;
            color: var(--hdr-fg);
            text-decoration: none;
            background: transparent;
            border: 1.5px solid var(--hdr-btn-border);
            border-radius: 8px;
            transition: background .2s ease, color .2s ease, border-color .2s ease;
        }
        .carve-btn-ghost:hover { background: var(--hdr-fg); color: var(--hdr-bg); border-color: var(--hdr-fg); }
        .carve-btn-ghost:focus-visible,
        .carve-header__brand:focus-visible { outline: 2px solid var(--hdr-focus); outline-offset: 2px; }
 
        /* --- responsive ------------------------------------------------------ */
        @media (max-width: 1280px) {
            .carve-pill { display: none; }
        }
        @media (max-width: 1100px) {
            /* keep the name for screen readers, drop it visually */
            .carve-user__name {
                position: absolute; width: 1px; height: 1px; margin: -1px;
                overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;
            }
        }
        @media (max-width: 1000px) {
            .carve-header {
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas: "left right" "tabs tabs";
                row-gap: .65rem;
                padding: .6rem 1rem .75rem;
            }
            .carve-tabs-slot { width: 100%; }
        }
        @media (max-width: 520px) {
            .carve-header__divider { display: none; }
            .carve-header__logo { height: 32px; }
            .carve-header__name { font-size: 1.1rem; }
        }

  
.carve-header {
    --halo-drop: rgba(163, 72, 48, .20);   /* shadow under the blob   */
    --halo-lift: rgba(54, 34, 26, .22);    /* tiny lift on the logo   */
    --halo-blob: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 188 75' preserveAspectRatio='none'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%23FCF6EE'/%3E%3Cstop offset='1' stop-color='%23EBDACA'/%3E%3C/linearGradient%3E%3C/defs%3E%3Cpath d='M14 42C12 20 36 6 70 8C96 9 108 2 138 8C172 14 192 30 186 50C180 70 150 76 120 72C96 70 84 78 54 72C26 68 15 58 14 42Z' fill='url(%23g)' stroke='%23D8BEA4' stroke-opacity='.4' vector-effect='non-scaling-stroke'/%3E%3C/svg%3E");
}
.carve-header--pine {
    --halo-drop: rgba(0, 0, 0, .34);
    --halo-lift: rgba(0, 0, 0, .34);
    --halo-blob: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 188 75' preserveAspectRatio='none'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%233B5245'/%3E%3Cstop offset='1' stop-color='%232A3D33'/%3E%3C/linearGradient%3E%3C/defs%3E%3Cpath d='M14 42C12 20 36 6 70 8C96 9 108 2 138 8C172 14 192 30 186 50C180 70 150 76 120 72C96 70 84 78 54 72C26 68 15 58 14 42Z' fill='url(%23g)' stroke='%23F5E6D3' stroke-opacity='.14' vector-effect='non-scaling-stroke'/%3E%3C/svg%3E");
}
 
.carve-header__brand { position: relative; isolation: isolate; }
 
.carve-header__brand::before {
    content: '';
    position: absolute;
    z-index: -1;
    pointer-events: none;
    inset: -11px -9px -9px -16px;
    background: var(--halo-blob) center / 100% 100% no-repeat;
    filter: drop-shadow(0 1px 1.5px var(--halo-drop));
    transform: rotate(-1.5deg);
    transition: transform .5s cubic-bezier(.3, 1.4, .5, 1);
}
 
.carve-header__logo {
    position: relative;
    filter: drop-shadow(0 1px .5px var(--halo-lift));
    transition: transform .5s cubic-bezier(.3, 1.4, .5, 1);
}
 
/* a small squish on hover: the blob settles, the logo lifts a hair */
.carve-header__brand:hover::before { transform: rotate(1deg) scale(1.045); }
.carve-header__brand:hover .carve-header__logo { transform: translateY(-1px); }
 
@media (prefers-reduced-motion: reduce) {
    .carve-header__brand::before,
    .carve-header__logo { transition: none; }
}
    </style>
</head>
 
<body class="convo-page">
 
    <header class="<?= $headerClass ?>">
        <div class="carve-header__left">
            <a href="/home" class="carve-header__brand" aria-label="The Carve &mdash; back to home">
                <img src="<?= $h($logoSrc) ?>" alt="The Carve" class="carve-header__logo">
            </a>
            <span class="carve-header__divider" aria-hidden="true"></span>
            <div class="carve-header__title">
                <h1 class="carve-header__name" title="<?= $h($convoName) ?>"><?= $h($convoName) ?></h1>
                <span class="carve-pill">Class Hub</span>
                <?php if ($readOnly): ?>
                    <span class="carve-pill carve-pill--muted">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Read-only
                    </span>
                <?php endif; ?>
            </div>
        </div>
 
        <!-- React mounts the Lobby / Plaza / Arena slider here -->
        <div class="carve-tabs-slot" id="convo-tabs-slot">
            <div class="carve-tabs-skel" aria-hidden="true"></div>
        </div>
 
        <div class="carve-header__right">
            <?php if ($userName !== ''): ?>
                <div class="carve-user" title="Signed in as <?= $h($userName) ?>">
                    <span class="carve-user__avatar" aria-hidden="true"><?= $h($initialsOf($userName)) ?></span>
                    <span class="carve-user__name"><?= $h($userName) ?></span>
                </div>
            <?php endif; ?>
            <a href="/home" class="carve-btn-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Home
            </a>
        </div>
    </header>
 
    <div id="react-root">
        <!-- React will inject messages here -->
    </div>
 
    <script>
    window.APP_DATA = <?= json_encode(
        [
            'convoId' => $convoId,
            'readOnly' => $readOnly,
            'userName' => $request->session['name'] ?? "User Name"
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
    </script>
 
    <!-- must manually configure the js import from public/js/-->
    <script type="module" src="/project/public/js/convo.js?v=<?= filemtime(__DIR__ . '/../public/js/convo.js') ?>"></script>
 
</body>
</html>