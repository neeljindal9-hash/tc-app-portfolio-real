<?php

    if (!isset($request->session['user_id'])) {
        throw new ExitException('Invalid Session');
    }

    // Handle the fresh user state cleanly
    $isFreshUser = false;
    if (($request->session['fresh_user'] ?? null) === true) {
        unset($request->session['fresh_user']);
        $isFreshUser = true;
    }

    $schoolName = "Mountain View High School";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/favicon-32x32.png">
    <title>Welcome to The Carve</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600&display=swap" rel="stylesheet">
    <style>
        /* =========================================
           1. PROFESSIONAL WOOD TONES & PHYSICS
           ========================================= */
        :root {
            var(--wood-bg): #EBD5B9;      
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
           3. ONBOARDING CARD
           ========================================= */
        .welcome-wrapper {
            width: 100%;
            max-width: 720px;
            padding: 2rem;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .welcome-card {
            background-color: var(--wood-surface);
            border-radius: var(--radius-round);
            padding: 4rem 3rem;
            width: 100%;
            text-align: center;
            border: var(--border-subtle);
            box-shadow: var(--shadow-lifted);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        /* The School Campus Tag */
        .campus-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--wood-recessed);
            color: var(--text-dark);
            padding: 0.5rem 1.25rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: var(--shadow-engraved);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .campus-tag svg { color: var(--sidebar-pine); }

        /* Main Logo */
        .main-logo {
            height: 110px;
            width: auto;
            object-fit: contain;
            margin-bottom: -0.5rem;
        }

        /* Typography */
        .welcome-header h1 {
            font-size: 3rem;
            line-height: 1.1;
            letter-spacing: -0.5px;
            margin-bottom: 1rem;
        }

        .welcome-header p {
            font-size: 1.15rem;
            line-height: 1.6;
            color: var(--text-muted);
            font-weight: 500;
            margin: 0 auto;
            max-width: 500px;
        }

        /* =========================================
           4. MICRO-FEATURES VISUAL
           ========================================= */
        .steps-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            padding: 2rem 0;
            border-top: 2px dashed rgba(131, 92, 72, 0.2);
            border-bottom: 2px dashed rgba(131, 92, 72, 0.2);
            margin: 1rem 0;
            position: relative;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
            flex: 1;
            position: relative;
            z-index: 2;
        }

        .step-icon {
            width: 56px;
            height: 56px;
            background-color: var(--wood-bg);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-cinnamon);
            border: var(--border-subtle);
            box-shadow: var(--shadow-engraved);
        }

        .step-item span {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* =========================================
           5. HERO BUTTON
           ========================================= */
        .hero-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background-color: var(--accent-cinnamon);
            color: #FFF;
            text-decoration: none;
            padding: 1.25rem 3rem;
            font-size: 1.15rem;
            font-weight: 800;
            border-radius: 12px;
            border: 2px solid var(--accent-cinnamon-dark);
            box-shadow: 0 6px 0 var(--accent-cinnamon-dark), 0 12px 24px rgba(62, 39, 35, 0.2); 
            transition: transform 0.1s, box-shadow 0.1s;
            width: fit-content;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hero-btn:hover { 
            transform: translateY(2px); 
            box-shadow: 0 4px 0 var(--accent-cinnamon-dark), 0 8px 16px rgba(62, 39, 35, 0.2); 
        }
        
        .hero-btn:active { 
            transform: translateY(6px); 
            box-shadow: 0 0 0 var(--accent-cinnamon-dark), 0 4px 8px rgba(62, 39, 35, 0.2); 
        }

        /* Subtle Help Text */
        .help-text {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 1rem;
        }
        
        .help-text a {
            color: var(--accent-cinnamon);
            text-decoration: none;
            font-weight: 800;
        }
        
        .help-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <main class="welcome-wrapper">
        <div class="welcome-card">
            
            <div class="campus-tag">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                <?= htmlspecialchars($schoolName) ?>
            </div>

            <img src="/public/assets/logo/main-logo-full.png" alt="The Carve Logo" class="main-logo">

            <div class="welcome-header">
                <?php if ($isFreshUser): ?>
                    <h1>You made it inside!</h1>
                    <p>Woohoo! Your school verification is complete. You are officially plugged into the campus network.</p>
                <?php else: ?>
                    <h1>Welcome back.</h1>
                    <p>It looks like you haven't finished setting up your profile yet. Let's get your schedule synced.</p>
                <?php endif; ?>
            </div>

            <div class="steps-container">
                <div class="step-item">
                    <div class="step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    </div>
                    <span>Add Classes</span>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <span>Find Peers</span>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    </div>
                    <span>Join Hubs</span>
                </div>
            </div>

            <a href="/home" class="hero-btn">
                Enter The Carve
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </a>

        </div>

        <div class="help-text">
            Help others find their way inside by sharing the <a href="/info">invite link</a>.
        </div>
    </main>

</body>
</html>