<?php

    if (!isset($request->session['user_id'])) {
        throw new ExitException('Invalid Session');
    }
    // Fetch conversations (Class Hubs) for this user
    $conversations = ConversationRepository::fetchConversations($request->session['user_id'], $request->dbconn);

    $pendingJoinGroupName = null;
    $pendingJoinCode = null;
    $handleJoinRequest = "no_request";

    // If they landed here via a shareable GET link
    if (isset($request->get['joinCode']) && !empty($request->get['joinCode'])) {
        
        $pendingJoinCode = trim($request->get['joinCode']);
        
        $info = ConversationRepository::getAbstractConvoInfoGivenCode($pendingJoinCode, $request->dbconn);

        if (!$info) {
            $pendingJoinGroupName = false;
            $handleJoinRequest = "invalid_request";
        } elseif (ConversationRepository::verifyAccess($request->session['user_id'], $info['ID'], $request->dbconn)) {
            $pendingJoinGroupName = $info['name'];
            $handleJoinRequest = "already_joined";
        } else {
            $pendingJoinGroupName = $info['name'];
            $handleJoinRequest = "verified";
        }
    }

    $getLockInfo = static function (int $access_mode): string {
        return match ($access_mode) {
            0 => "",
            1 => "(Read-only)",
            2 => "Locked until minimum # of participants.",
            3, 4 => "Locked",
            default => "505 Internal Server Error",
        };
    };
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Carve</title>
    <link rel="icon" type="image/png" href="/favicon-32x32.png">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/schedule.css">
    <style>
        /* =========================================
           1. PROFESSIONAL WOOD TONES & PHYSICS
           ========================================= */
        :root {
            --wood-bg: #EBD5B9;
            --wood-surface: #F5E6D3;
            --wood-recessed: #E0C7A8;
            --sidebar-pine: #2C3E35;
            --text-dark: #3E2723;
            --text-muted: #835C48;
            --accent-cinnamon: #C45525;
            --accent-cinnamon-dark: #9D3C16;
            
            --radius-soft: 16px;
            --radius-round: 24px;
            --border-subtle: 1px solid rgba(131, 92, 72, 0.15);
            
            --shadow-lifted: 0 4px 12px rgba(62, 39, 35, 0.05), 0 1px 3px rgba(62, 39, 35, 0.08);
            --shadow-lifted-hover: 0 8px 24px rgba(62, 39, 35, 0.08), 0 2px 6px rgba(62, 39, 35, 0.06);
            --shadow-engraved: inset 0 2px 6px rgba(62, 39, 35, 0.08), inset 0 -1px 2px rgba(255, 255, 255, 0.6);
            --shadow-groove: inset 0 1px 3px rgba(0,0,0,0.3), inset 0 -1px 1px rgba(255,255,255,0.1);
            
            --transition-smooth: 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            font-family: 'Nunito', system-ui, sans-serif;
        }

        /* =========================================
           2. GLOBAL STYLES
           ========================================= */
        body {
            margin: 0; padding: 0;
            background-color: var(--wood-bg);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }

        h1, h2, h3, h4 { font-family: 'Playfair Display', serif; margin: 0; color: var(--text-dark); }

        /* =========================================
           3. SIDEBAR (ELEGANT PINE)
           ========================================= */
        .app-sidebar {
            width: 280px;
            background-color: var(--sidebar-pine);
            color: var(--wood-surface);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 500;
            border-right: 1px solid rgba(0,0,0,0.2);
            box-shadow: 2px 0 12px rgba(44, 62, 53, 0.2);
            transition: width var(--transition-smooth);
            overflow: hidden;
        }

        body.sidebar-collapsed .app-sidebar { width: 88px; }

        .sidebar-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.75rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            box-shadow: 0 1px 0 rgba(0,0,0,0.2);
            transition: var(--transition-smooth);
        }

        body.sidebar-collapsed .sidebar-header { flex-direction: column; padding: 1.75rem 0; gap: 1.5rem; }

        .brand-logo-img {
            height: 42px; width: auto; flex-shrink: 0; object-fit: contain; 
            transition: height var(--transition-smooth);
        }

        body.sidebar-collapsed .brand-logo-img { height: 25px; }

        .sidebar-brand { display: flex; align-items: center; gap: 0.85rem; text-decoration: none; color: var(--wood-surface); }
        .brand-icon { flex-shrink: 0; color: var(--wood-recessed); }
        .brand-text { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 800; white-space: nowrap; letter-spacing: -0.5px;}
        body.sidebar-collapsed .brand-text { display: none; }

        .sidebar-toggle {
            background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); color: rgba(255,255,255,0.7);
            cursor: pointer; padding: 0.4rem; border-radius: 8px; display: flex; justify-content: center; align-items: center;
            transition: var(--transition-smooth);
        }
        .sidebar-toggle:hover { background: rgba(0,0,0,0.3); color: var(--wood-surface); }

        /* --- SKELETON LOADING STATE (Scoped so it doesn't clash with React) --- */
        .timeline-wrapper { padding: 2.5rem 2rem; flex: 1; overflow-y: auto; overflow-x: hidden; display: flex; flex-direction: column; }
        body.sidebar-collapsed .timeline-wrapper { padding: 2.5rem 0; align-items: center; }

        .timeline-wrapper h3 { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.5); margin-bottom: 2rem; font-family: 'Nunito', sans-serif; font-weight: 800; white-space: nowrap; }
        body.sidebar-collapsed .timeline-wrapper h3 { display: none; }

        .skeleton-timeline { position: relative; display: flex; flex-direction: column; gap: 1.75rem; width: 100%; }
        

        .skeleton-slot { position: relative; padding-left: 2.5rem; display: flex; flex-direction: column; gap: 0.5rem; }
        body.sidebar-collapsed .skeleton-slot { padding-left: 0; align-items: center; justify-content: center; height: 24px; }

        .skeleton-slot::before { content: ''; position: absolute; left: 2px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--sidebar-pine); box-shadow: var(--shadow-groove); z-index: 2; }
        body.sidebar-collapsed .skeleton-slot::before { left: 50%; top: 50%; transform: translate(-50%, -50%); margin-left: 0; }

        .skeleton-time { height: 10px; width: 45px; background: rgba(0,0,0,0.2); border-radius: 4px; box-shadow: var(--shadow-groove); }
        .skeleton-period { height: 14px; width: 90px; background: rgba(0,0,0,0.15); border-radius: 4px; box-shadow: var(--shadow-groove); }
        body.sidebar-collapsed .skeleton-time, body.sidebar-collapsed .skeleton-period { display: none; }

        .teaser-msg {
            margin-top: 1rem; padding: 1.25rem; background: rgba(0,0,0,0.15); border-radius: 12px;
            box-shadow: var(--shadow-groove); display: flex; align-items: flex-start; gap: 0.85rem;

            color: rgba(255,255,255,0.7); font-size: 0.87rem; font-weight: 700; line-height: 1.5;
        }
        body.sidebar-collapsed .teaser-msg { display: none; }

        /* Live Clock Fixed */
        .live-clock { padding: 1.5rem; background: rgba(0,0,0,0.2); font-family: monospace; font-size: 1.25rem; font-weight: 800; color: rgba(255,255,255,0.7); text-align: center; letter-spacing: 2px; white-space: nowrap; box-shadow: inset 0 1px 3px rgba(0,0,0,0.3); transition: var(--transition-smooth); }
        body.sidebar-collapsed .live-clock { font-size: 0.85rem; padding: 1.5rem 0; letter-spacing: 0; }

        /* =========================================
           4. MAIN WORKSPACE & HEADER
           ========================================= */
        .main-workspace { margin-left: 280px; width: calc(100% - 280px); display: flex; flex-direction: column; transition: margin-left var(--transition-smooth), width var(--transition-smooth); }
        body.sidebar-collapsed .main-workspace { margin-left: 88px; width: calc(100% - 88px); }

        .top-nav {
            padding: 1.25rem 4rem; display: flex; justify-content: flex-end; align-items: center;
            background-color: rgba(235, 213, 185, 0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            position: sticky; top: 0; z-index: 100; border-bottom: var(--border-subtle);
        }

        .logout-btn { 
            font-family: 'Nunito', sans-serif; background: transparent; border: 1.5px solid var(--text-muted); color: var(--text-dark); 
            padding: 0.5rem 1rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem; 
            cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: var(--transition-smooth);
        }
        .logout-btn:hover { background: var(--text-dark); color: var(--wood-surface); border-color: var(--text-dark); }

        .container { max-width: 1150px; margin: 0 auto; padding: 4rem 4rem 6rem; display: flex; flex-direction: column; gap: 5rem; width: 100%; box-sizing: border-box; }

        .section-header { 
            margin-bottom: 2rem; padding-bottom: 1rem; display: flex; align-items: flex-end; justify-content: space-between; 
            border-bottom: 2px solid rgba(131, 92, 72, 0.15); box-shadow: 0 1px 0 rgba(255,255,255,0.4);
        }
        .section-title-group { display: flex; align-items: center; gap: 1.25rem; }
        .section-header h2 { font-size: 2.1rem; font-weight: 800; letter-spacing: -0.5px; display: flex; align-items: center; gap: 0.75rem; }
        .section-header span { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800; color: var(--accent-cinnamon); background: rgba(196, 85, 37, 0.1); padding: 0.35rem 0.85rem; border-radius: 20px; }

        /* =========================================
           5. HERO PANEL (CLEAN SURFACES)
           ========================================= */
        .carved-panel {
            background-color: var(--wood-surface); border-radius: var(--radius-round); padding: 4rem;
            border: var(--border-subtle); box-shadow: var(--shadow-lifted);
            display: grid; grid-template-columns: 1fr 1.1fr; gap: 4.5rem; align-items: center;
        }

        @media (max-width: 1000px) { .carved-panel { grid-template-columns: 1fr; gap: 3rem; padding: 2.5rem; } }

        .hero-text { display: flex; flex-direction: column; gap: 1.25rem; }
        .hero-text h3 { font-size: 2.8rem; line-height: 1.15; letter-spacing: -0.5px; color: var(--text-dark); }
        .hero-text p { font-size: 1.1rem; line-height: 1.6; color: var(--text-muted); font-weight: 500; margin: 0; }

        .chiseled-btn {
            display: inline-flex; align-items: center; gap: 0.75rem; background-color: var(--accent-cinnamon); color: #FFF; text-decoration: none;
            padding: 1rem 2rem; font-size: 1.05rem; font-weight: 800; border-radius: 12px; border: 2px solid var(--accent-cinnamon-dark);
            box-shadow: 0 4px 0 var(--accent-cinnamon-dark), 0 8px 16px rgba(62, 39, 35, 0.15); 
            transition: transform 0.1s, box-shadow 0.1s; width: fit-content; cursor: pointer; margin-top: 0.5rem;
        }
        .chiseled-btn:hover:not(.disabled) { transform: translateY(2px); box-shadow: 0 2px 0 var(--accent-cinnamon-dark), 0 4px 8px rgba(62, 39, 35, 0.15); }
        .chiseled-btn:active:not(.disabled) { transform: translateY(4px); box-shadow: 0 0 0 var(--accent-cinnamon-dark), 0 2px 4px rgba(62, 39, 35, 0.15); }
        .chiseled-btn.disabled { background: var(--wood-bg); color: var(--text-muted); border: 1.5px dashed var(--text-muted); box-shadow: none; cursor: not-allowed; margin: 0; transform: none; }

        /* --- THE FEATURE AD BOX --- */
        .dad-box {
            background-color: var(--wood-recessed); border-radius: var(--radius-soft); 
            padding: 2.5rem; display: flex; flex-direction: column; gap: 1.75rem; justify-content: center;
            box-shadow: var(--shadow-engraved); border: 1px solid rgba(255,255,255,0.3);
        }
        .dad-box h4 { font-family: 'Nunito', sans-serif; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--sidebar-pine); border-bottom: 1px solid rgba(131, 92, 72, 0.2); padding-bottom: 0.75rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 1px 0 rgba(255,255,255,0.3); }
        .dad-item { display: flex; align-items: flex-start; gap: 1.25rem; }
        .dad-icon { width: 44px; height: 44px; flex-shrink: 0; border-radius: 10px; background-color: var(--wood-surface); color: var(--accent-cinnamon); display: flex; align-items: center; justify-content: center; border: var(--border-subtle); box-shadow: var(--shadow-lifted); }
        .dad-text { display: flex; flex-direction: column; gap: 0.25rem; margin-top: 0.15rem;}
        .dad-text strong { font-size: 1.05rem; color: var(--text-dark); font-weight: 800; }
        .dad-text span { font-size: 0.95rem; color: var(--text-muted); font-weight: 500; line-height: 1.5; }

        /* =========================================
           6. GRID CARDS
           ========================================= */
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; position: relative; }

        .feature-card { background-color: var(--wood-surface); border-radius: var(--radius-soft); padding: 2.25rem; display: flex; flex-direction: column; gap: 1rem; border: var(--border-subtle); box-shadow: var(--shadow-lifted); max-width: 450px; transition: transform 0.2s ease, box-shadow 0.2s ease; position: relative; }
        .feature-card:hover:not([data-isLocked="1"]) { transform: translateY(-4px); box-shadow: var(--shadow-lifted-hover); cursor: pointer; }
        .feature-card[data-isLocked="1"] { cursor: not-allowed; background-color: var(--wood-recessed); border-color: transparent; box-shadow: var(--shadow-engraved); opacity: 0.9; }

        .feature-icon-wrapper { width: 50px; height: 50px; border-radius: 12px; background-color: var(--wood-bg); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; color: var(--sidebar-pine); border: var(--border-subtle); box-shadow: var(--shadow-engraved); }
        .feature-card h3 { font-size: 1.35rem; color: var(--text-dark); font-weight: 800; }
        .feature-card p { font-size: 0.95rem; color: var(--text-muted); margin: 0; line-height: 1.6; font-weight: 500; flex: 1; }

        .explicit-lock-msg { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed rgba(131, 92, 72, 0.3); font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

        .locked-overlay { position: absolute; top: -15px; left: -15px; right: -15px; bottom: -15px; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10; pointer-events: none; }
        .lock-badge { background-color: var(--wood-surface); color: var(--text-dark); padding: 1.25rem 2.5rem; border-radius: 12px; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; border: var(--border-subtle); box-shadow: 0 12px 24px rgba(62, 39, 35, 0.15); }
        .lock-badge svg { color: var(--accent-cinnamon); margin-bottom: 0.25rem; }
        .lock-badge h3 { font-size: 1.25rem; font-weight: 800; }
        .lock-badge p { margin: 0; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); }

        /* =========================================
           7. MODAL STYLING
           ========================================= */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(62, 39, 35, 0.5); display: flex; align-items: center; justify-content: center; z-index: 2000; }
        .modal-content { background-color: var(--wood-surface); padding: 3rem; border-radius: var(--radius-round); width: 100%; max-width: 420px; text-align: center; border: var(--border-subtle); box-shadow: 0 24px 48px rgba(0,0,0,0.15); }
        .modal-content h2 { color: var(--text-dark); font-weight: 800; }
        .modal-actions { display: flex; gap: 1rem; margin-top: 2.5rem; justify-content: center; }
    </style>
</head>
<body>

    <?php if ($handleJoinRequest === "verified"): ?>
    <div class="modal-overlay" id="joinModal">
        <div class="modal-content">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="var(--sidebar-pine)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>
            </svg>
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Join Class Hub</h2>
            <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.5; font-weight: 500;">You've been invited to join <strong style="color: var(--text-dark);"><?= htmlspecialchars($pendingJoinGroupName) ?></strong>.</p>
            
            <form id="confirmJoinForm" method="POST">
                <input type="hidden" name="joinCode" value="<?= htmlspecialchars($pendingJoinCode) ?>">
                <div class="modal-actions">
                    <button type="button" id="closeModalBtn" class="chiseled-btn" style="background: transparent; color: var(--text-dark); box-shadow: none; border: 1.5px solid var(--text-muted); transform: none;">Cancel</button>
                    <button type="submit" class="chiseled-btn" style="background-color: var(--sidebar-pine); border-color: #1E2B25; box-shadow: 0 4px 0 #1E2B25, 0 8px 16px rgba(62, 39, 35, 0.15);">Accept Invite</button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($handleJoinRequest === "invalid_request"): ?>
    <div class="modal-overlay" id="joinModal">
        <div class="modal-content">
            <h2 style="color: var(--accent-cinnamon); margin-bottom: 0.5rem;">Invalid Link</h2>
            <p style="color: var(--text-muted); font-weight: 500;">This invite link is expired or does not exist.</p>
            <button id="closeModalBtn" class="chiseled-btn" style="margin-top: 1.5rem;">Dismiss</button>
        </div>
    </div>
    <?php endif; ?>

    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <img src="/project/public/assets/logo/white-trans-logo.png" alt="The Carve Logo" class="brand-logo-img">
            </a>
            
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div class="timeline-wrapper">
            <h3>Your Live Route</h3>
            
            <div class="skeleton-timeline" id="schedule-timeline">
                <div class="skeleton-slot">
                    <div class="skeleton-time"></div>
                    <div class="skeleton-period"></div>
                </div>
                <div class="skeleton-slot">
                    <div class="skeleton-time" style="width: 55px;"></div>
                    <div class="skeleton-period" style="width: 70px;"></div>
                </div>
                <div class="skeleton-slot">
                    <div class="skeleton-time" style="width: 40px;"></div>
                    <div class="skeleton-period"></div>
                </div>
                <div class="skeleton-slot">
                    <div class="skeleton-time" style="width: 60px;"></div>
                    <div class="skeleton-period" style="width: 85px;"></div>
                </div>
            </div>

            <div class="teaser-msg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: rgba(255,255,255,0.55);">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                </svg>
                <span>Be sure to add your classes if you haven't already.</span>
            </div>
        </div>

        <div class="live-clock" id="live-clock">--:--</div>
    </aside>

    <div class="main-workspace">
        
        <header class="top-nav">
            <button class="logout-btn" onclick="if(confirm('Are you sure you want to log out?')) { window.location.href = '/logout'; }">
                Log Out
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>
                </svg>
            </button>
        </header>

        <main class="container">
            <section id="before-school">
                <div class="section-header">
                    <div class="section-title-group">
                        <h2>
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cinnamon)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 15v4c0 1.1.9 2 2 2h14v-2"/><path d="M3 7v14"/><path d="M14 15h4v-2h-4v2z"/>
                            </svg>
                            Outer Deck
                        </h2>
                        <span> Classes View</span>
                    </div>
                </div>

                <div class="carved-panel">
                    <div class="hero-text">
                        <h3>Sync your schedule.<br>See who's around.</h3>
                        <p>Discover shared teachers and shared subjects. Enter your schedule to find your people, join class group rooms (very soon!), and map your live route room-to-room.</p>
                        
                        <a href="/classes" class="chiseled-btn">
                            Enter The Outer Deck
                            
                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 6px; font-size: 0.9rem; box-shadow: inset 0 1px 2px rgba(0,0,0,0.25);">
                                45
                                <span style="color: #FFD54F;">+5</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#FFD54F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                    <polyline points="17 6 23 6 23 12"></polyline>
                                </svg>
                            </span>

                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="dad-box">
                        <h4>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h4l3-9 5 18 3-9h5"/></svg>
                            Deck Toolkit
                        </h4>
                        
                        <div class="dad-item">
                            <div class="dad-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            </div>
                            <div class="dad-text">
                                <strong>Nuanced Filters</strong>
                                <span>See who shares your exact periods, similar schedules, or just your teachers.</span>
                            </div>
                        </div>

                        <div class="dad-item">
                            <div class="dad-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            </div>
                            <div class="dad-text">
                                <strong>Share Your Schedule</strong>
                                <span>Generate a custom link to swap schedules and compare your friends' classes.</span>
                            </div>
                        </div>

                        <div class="dad-item">
                            <div class="dad-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                            </div>
                            <div class="dad-text">
                                <strong>Top Matches Leaderboard</strong>
                                <span>Discover your hidden campus buddies. See who shares the most classes with you.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="during-school">
                <div class="section-header">
                    <div class="section-title-group">
                        <h2>
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--sidebar-pine)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                            The Inner Carve
                        </h2>
                        <span>Campus Life</span>
                    </div>
                    
                    <button class="chiseled-btn disabled" disabled title="Locked until classes begin">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Enter Invite Code
                    </button>
                </div>

                <div class="features-grid" id="conversation_container">
                    

                    <?php if (!empty($conversations)): ?>
                        <?php foreach ($conversations as $convo):
                          $isLocked = ($convo['access_mode'] > 1) ? 1 : 0 ?>
                            <div class="feature-card convo_tile clickable-tile" data-id="<?= htmlspecialchars($convo['ID']) ?>" data-isLocked="<?= $isLocked ?>">
                                <div class="feature-icon-wrapper">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                                <h3><?= htmlspecialchars($convo['name']) ?></h3>
                                <p>Play educational games, study together, debate topics, and inform each other of school updates with your period.</p>
                                
                                <?php if($isLocked === 1): ?>
                                    <div class="explicit-lock-msg">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        <?php echo $getLockInfo($convo['access_mode']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        
                        <div class="feature-card" data-isLocked="1">
                            <div class="feature-icon-wrapper">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            </div>
                            <h3>Study Lobbies</h3>
                            <p>Join live, period-specific study rooms. Ask questions about tonight's homework and collaborate with classmates in real-time.</p>
                            <div class="explicit-lock-msg"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Locked</div>
                        </div>

                        <div class="feature-card" data-isLocked="1">
                            <div class="feature-icon-wrapper">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </div>
                            <h3>The Arena</h3>
                            <p>Test your knowledge in educational mini-games against other periods. Climb the leaderboard and show off your mastery.</p>
                            <div class="explicit-lock-msg"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Locked</div>
                        </div>
                    <?php endif; ?>

                </div>
            </section>

        </main>
    </div>

    <script>
        window.APP_DATA = 
            {
                'userName': <?php echo json_encode($request->session['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                'bellSchedules': <?= file_get_contents(__DIR__ . '/../public/assets/schedule/bell_schedules.json') ?>,
                'daySchedule': <?= file_get_contents(__DIR__ . '/../public/assets/schedule/year_schedule.json')?>
            };
        
    </script>

    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        sidebarToggle.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });

        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; 
            minutes = minutes < 10 ? '0' + minutes : minutes;
            document.getElementById('live-clock').innerText = hours + ':' + minutes + ' ' + ampm;
        }
        setInterval(updateClock, 1000);
        updateClock();

        const tiles = document.querySelectorAll('.clickable-tile');
        const url = new URL(window.location.href);  

        if (url.searchParams.has('joinCode')) {
            url.searchParams.delete('joinCode');
            history.replaceState({}, '', url); 
        }

        tiles.forEach(tile => {
            if (tile.getAttribute('data-isLocked') ==="1") return;
            tile.addEventListener('click', () => {
                const convoId = tile.getAttribute('data-id');
                window.location.href = `/convo?id=${convoId}`;
            });
        });

        const joinModal = document.querySelector('#joinModal');

        if (joinModal) {
            const form = joinModal.querySelector('#confirmJoinForm');

            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault(); 
                    const formData = new FormData(form);

                    try {
                        const response = await fetch('/project/api/process_join', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (!response.ok) throw new Error(data.error || 'Unknown API error');

                        if (data.success) {
                            joinModal.remove();
                            window.location.reload(); 
                        } else {
                            console.error("API Error: Failed to process invite.");
                        }
                    } catch (err) {
                        console.error(err);
                    }
                });
            }
            
            const closeBtn = joinModal.querySelector('#closeModalBtn');
            if (closeBtn) closeBtn.addEventListener('click', () => joinModal.remove());
        }
    </script>

    <script type="module" src="/public/js/schedule.js?v=<?= filemtime(__DIR__ . '/../public/js/schedule.js') ?>"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const timelineWrapper = document.querySelector('.timeline-wrapper');
            
            // Remove the hardcoded skeleton HTML so React can take over cleanly
            const existingTimeline = document.getElementById('schedule-timeline');
            if (existingTimeline) existingTimeline.innerHTML = "";
            

            // Give the browser a tiny tick to ensure the module is loaded, then inject
            setTimeout(() => {
                if (typeof window.injectScheduleComponent === 'function') {
                    window.injectScheduleComponent(existingTimeline);
                } else {
                    console.error("React injector not found.");
                }
            }, 50);
        });
    </script>
</body>
</html>