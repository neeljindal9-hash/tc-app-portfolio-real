<?php

    
    if (isset($request->session['exc']) && $request->session['exc'] === true) {
        $name = $request->session['name'] ?? "Unknown";
    } else {
        throw new ExitException("404: Not Found");
    }

    // Extract just the first name for a more casual, student-to-student greeting
    $nameParts = explode(' ', trim($name));
    $firstName = $nameParts[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Rep Access | The Carve</title>
    <link rel="icon" type="image/png" href="/favicon-32x32.png">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600&display=swap" rel="stylesheet">
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
            
            --shadow-lifted: 0 12px 32px rgba(62, 39, 35, 0.1), 0 2px 6px rgba(62, 39, 35, 0.08);
            --shadow-engraved: inset 0 2px 6px rgba(62, 39, 35, 0.08), inset 0 -1px 2px rgba(255, 255, 255, 0.6);
            
            font-family: 'Nunito', system-ui, sans-serif;
        }

        /* =========================================
           2. GLOBAL LAYOUT
           ========================================= */
        body {
            margin: 0; padding: 0;
            background-color: var(--wood-bg);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        h1, h2, h3 { font-family: 'Playfair Display', serif; margin: 0; color: var(--text-dark); }

        /* =========================================
           3. INVITATION CARD
           ========================================= */
        .invite-wrapper {
            width: 100%;
            max-width: 680px;
            padding: 2rem;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .invite-card {
            background-color: var(--wood-surface);
            border-radius: var(--radius-round);
            padding: 4rem 3.5rem;
            width: 100%;
            text-align: center;
            border: var(--border-subtle);
            box-shadow: var(--shadow-lifted);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            position: relative;
        }

        /* Top Badge */
        .rep-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--sidebar-pine);
            color: var(--wood-surface);
            padding: 0.6rem 1.25rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: 0 4px 12px rgba(44, 62, 53, 0.2);
            margin-bottom: 0.5rem;
        }

        .rep-badge svg { color: var(--accent-cinnamon); }

        /* Typography */
        .invite-header h1 {
            font-size: 2.8rem;
            line-height: 1.1;
            letter-spacing: -0.5px;
            margin-bottom: 1rem;
        }

        .invite-header p {
            font-size: 1.15rem;
            line-height: 1.6;
            color: var(--text-muted);
            font-weight: 500;
            margin: 0 auto;
            max-width: 500px;
        }

        /* =========================================
           4. ENGRAVED MISSION BOX
           ========================================= */
        .mission-box {
            background-color: var(--wood-recessed);
            border-radius: var(--radius-soft);
            padding: 2.25rem;
            width: 100%;
            box-sizing: border-box;
            box-shadow: var(--shadow-engraved);
            border: 1px solid rgba(255,255,255,0.3);
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .mission-box h3 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 1.1px;
            color: var(--sidebar-pine);
            font-weight: 830;
            border-bottom: 1px solid rgba(131, 92, 72, 0.2);
            padding-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 0 rgba(255,255,255,0.3);
            margin-bottom: 0.25rem;
        }

        .mission-box p {
            font-size: 1rem;
            color: var(--text-dark);
            line-height: 1.6;
            font-weight: 600;
            margin: 0;
        }

        /* --- MISSION BOX TEXT FORMATTING --- */
.mission-lead {
    font-size: 1.05rem;
    color: var(--text-dark);
    line-height: 1.6;
    margin: 0;
}

.mission-checklist {
    background: rgba(255, 255, 255, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    box-shadow: inset 0 1px 3px rgba(131, 92, 72, 0.05);
}

.checklist-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 0 0.25rem 0;
}

.checklist-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    color: var(--text-dark);
    font-weight: 500;
}

.checklist-item svg {
    flex-shrink: 0;
}

.mission-ask {
    font-size: 1rem;
    color: var(--text-dark);
    line-height: 1.6;
    font-weight: 600;
    margin: 0;
}

.mission-disclaimer {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-style: italic;
    margin: -0.5rem 0 0 0;
}

        /* Security Device Notice */
        .device-notice {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: var(--wood-surface);
            padding: 0.85rem 1.25rem;
            border-radius: 8px;
            border: var(--border-subtle);
            margin-top: 0.5rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            box-shadow: var(--shadow-lifted);
        }

        .device-notice svg { color: var(--accent-cinnamon); flex-shrink: 0; }

        /* =========================================
           5. CALLS TO ACTION
           ========================================= */
        .action-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
            margin-top: 1rem;
        }

        /* Hero Google Button */
        .google-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background-color: var(--wood-surface);
            color: var(--text-dark);
            text-decoration: none;
            padding: 1.15rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 800;
            border-radius: 12px;
            border: 2px solid var(--text-dark);
            box-shadow: 0 4px 0 var(--text-dark), 0 8px 16px rgba(62, 39, 35, 0.15); 
            transition: transform 0.1s, box-shadow 0.1s;
            width: 100%;
            max-width: 360px;
            cursor: pointer;
        }

        .card-logo-icon {
    width: 84px;
    height: 84px;
    object-fit: contain;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(62, 39, 35, 0.12), 0 2px 4px rgba(62, 39, 35, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    margin-top: -0.5rem;
}

.card-logo-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(62, 39, 35, 0.16);
}

        .google-btn:hover { 
            transform: translateY(2px); 
            box-shadow: 0 2px 0 var(--text-dark), 0 4px 8px rgba(62, 39, 35, 0.15); 
            background-color: #FFF;
        }
        
        .google-btn:active { 
            transform: translateY(4px); 
            box-shadow: 0 0 0 var(--text-dark), 0 2px 4px rgba(62, 39, 35, 0.15); 
        }

        /* The Saturn Alternative Link */
        .info-link {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .info-link:hover { color: var(--sidebar-pine); text-decoration: underline; }
    </style>
</head>
<body>

    <main class="invite-wrapper">
        <div class="invite-card">
            
            <div class="rep-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                Student Rep Access
            </div>

            <img src="/project/public/assets/logo/icon-hq.png" alt="The Carve Icon" class="card-logo-icon">

            <div class="invite-header">
                <h1>Welcome to The Carve, <?= htmlspecialchars($firstName) ?>.</h1>
                <p>We're giving a handful of selected student leaders complete access to the new campus network. <strong>Congratulations!</strong> Your name is officially on the list for Mountain View High School, CA.</p>
            </div>

            <div class="mission-box">
                <h3>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Not the Average 'Schedule App'
                </h3>
                
                <p class="mission-lead">
                    The Carve is a <strong style="color: var(--sidebar-pine);">crowd-sourcing hub for campuses</strong> designed to make classes simpler and more enjoyable. The Carve's conversation tools enable students to <i>carve</i> their way to meaning, shaping <strong style="color: var(--sidebar-pine);">grades, academic experiences</strong>, and more.
                    <br><br>
                    Like <strong style="color: var(--sidebar-pine);"> no other app to date</strong>, our app now features a clean interface to report substitute teacher appearances, rank difficult tests, and comment on confusing homework.
                </p>

                <div class="mission-checklist">
                    <p class="checklist-title">Help us help you:</p>
                    <div class="checklist-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cinnamon)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span><strong>Sync</strong> your schedule.</span>
                    </div>
                    <div class="checklist-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cinnamon)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>Explore our premium <strong>'Carver' interface</strong>.</span>
                    </div>
                    <div class="checklist-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-cinnamon)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Experience how it could <strong>connect your peers</strong>.</span>
                    </div>
                </div>

                <p class="mission-ask">
                    If you genuinely like what we've built, we'd encourage you to <strong style="color: var(--accent-cinnamon);">share it with your friends</strong>. Your call.
                </p>
                
                <p class="mission-disclaimer">
                    *The Carve is not affiliated with school admin and serves as a secure, independent tool for students only.
                </p>
                
                <div class="device-notice">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    For security and verification, The Carve requires a desktop or laptop.
                </div>
            </div>

            <div class="action-group">
                <a href="/project/login" class="google-btn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Continue with Google
                </a>

                <a href="/project/info" class="info-link">
                    More: Why High Schoolers prefer The Carve over Saturn in 2026 
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>

        </div>
    </main>

</body>
</html>