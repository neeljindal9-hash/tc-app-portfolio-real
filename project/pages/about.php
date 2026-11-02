<?php

$name = $request->session['name'] ?? null; // check if logged in

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="oUvrSjfiRvZCJag4OAO5CpCoDQvhr1L4QLyORZDEmy4" />
    <title>About | The Carve</title>
    <link rel="icon" type="image/png" href="/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            
            --shadow-lifted: 0 4px 12px rgba(62, 39, 35, 0.05), 0 1px 3px rgba(62, 39, 35, 0.08);
            --shadow-lifted-hover: 0 8px 24px rgba(62, 39, 35, 0.08), 0 2px 6px rgba(62, 39, 35, 0.06);
            --shadow-engraved: inset 0 2px 6px rgba(62, 39, 35, 0.08), inset 0 -1px 2px rgba(255, 255, 255, 0.6);
            --shadow-groove: inset 0 1px 3px rgba(0,0,0,0.3), inset 0 -1px 1px rgba(255,255,255,0.1);
            
            --transition-smooth: 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            font-family: 'Nunito', system-ui, sans-serif;
        }

        body {
            margin: 0; padding: 0;
            background-color: var(--wood-bg);
            background-image: radial-gradient(circle at top right, rgba(255,255,255,0.3) 0%, transparent 60%);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }

        h1, h2, h3, h4 { font-family: 'Playfair Display', serif; margin: 0; color: var(--text-dark); }

        /* =========================================
           2. SIDEBAR (PUBLIC VIEW)
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
            overflow: hidden;
        }

        .sidebar-header {
            display: flex; align-items: center; justify-content: center;
            padding: 2.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            box-shadow: 0 1px 0 rgba(0,0,0,0.2);
        }

        .brand-logo-img { height: 48px; width: auto; object-fit: contain; }

        .public-nav-wrapper { padding: 2.5rem 1.5rem; flex: 1; display: flex; flex-direction: column; gap: 0.5rem; }

        .public-nav-link {
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem 1.25rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-weight: 700; font-size: 1.05rem;
            border-radius: 12px;
            transition: var(--transition-smooth);
        }

        .public-nav-link.active {
            background: rgba(0,0,0,0.2);
            color: var(--wood-surface);
            box-shadow: var(--shadow-groove);
        }

        .public-nav-link:hover:not(.active) { background: rgba(255,255,255,0.05); color: #FFF; }
        .public-nav-link i { flex-shrink: 0; font-size: 1.1rem; width: 20px; text-align: center; }

        .security-badge {
            margin: 2rem 1.5rem; padding: 1.25rem;
            background: rgba(0,0,0,0.15); border-radius: 12px;
            box-shadow: var(--shadow-groove);
            display: flex; flex-direction: column; gap: 0.5rem;
            color: rgba(255,255,255,0.6); font-size: 0.85rem; font-weight: 600; line-height: 1.5;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .security-badge strong { color: var(--wood-surface); font-size: 0.95rem; }

        /* =========================================
           3. MAIN WORKSPACE & HEADER (FIXED SCROLL BLEED)
           ========================================= */
        .main-workspace { margin-left: 280px; width: calc(100% - 280px); display: flex; flex-direction: column; }

        .top-nav {
            padding: 1.25rem 4rem; display: flex; justify-content: flex-end; align-items: center; gap: 1.5rem;
            background-color: rgba(235, 213, 185, 0.85); /* Frosted glass effect */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: var(--border-subtle);
            position: sticky; top: 0; z-index: 100;
        }
        
        .help-btn {
            font-family: 'Nunito', sans-serif; background: transparent; border: none; color: var(--text-muted); 
            font-weight: 800; font-size: 0.95rem; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;
            cursor: pointer; transition: var(--transition-smooth);
        }
        .help-btn:hover { color: var(--sidebar-pine); }

        .login-btn { 
            font-family: 'Nunito', sans-serif; background: var(--wood-surface); border: 2px solid var(--sidebar-pine); color: var(--sidebar-pine); 
            padding: 0.5rem 1.5rem; border-radius: 8px; font-weight: 800; font-size: 0.95rem; text-decoration: none;
            cursor: pointer; transition: var(--transition-smooth); box-shadow: var(--shadow-lifted);
        }
        .login-btn:hover { background: var(--sidebar-pine); color: var(--wood-surface); box-shadow: var(--shadow-lifted-hover); }

        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 4rem 6rem; display: flex; flex-direction: column; gap: 4rem; width: 100%; box-sizing: border-box; }

        /* =========================================
           4. FRONT DOOR PANEL & MARQUEE
           ========================================= */
        .carved-panel {
            background-color: var(--wood-surface); border-radius: var(--radius-round); padding: 4rem 2rem;
            border: var(--border-subtle); box-shadow: var(--shadow-lifted);
            display: flex; flex-direction: column; gap: 2rem; text-align: center; align-items: center; overflow: hidden;
        }
        
        .card-logo-icon {
            width: 84px; height: 84px; object-fit: contain; border-radius: 16px;
            box-shadow: 0 8px 20px rgba(62, 39, 35, 0.12), 0 2px 4px rgba(62, 39, 35, 0.08);
            margin-bottom: 0.5rem; background: var(--wood-surface);
        }

        .hero-text { display: flex; flex-direction: column; align-items: center; gap: 1.25rem; max-width: 750px; }
        .hero-text h1 { font-size: 3.5rem; line-height: 1.1; letter-spacing: -0.5px; color: var(--text-dark); }
        .hero-text p { font-size: 1.15rem; line-height: 1.6; color: var(--text-muted); font-weight: 600; margin: 0; }

        /* DUAL MARQUEE STYLES */
        .marquee-container {
            width: 100%; max-width: 900px; display: flex; flex-direction: column; gap: 1rem;
            margin: 1.5rem 0 1rem; position: relative;
            mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
        }

        .marquee-row { display: flex; width: max-content; gap: 1rem; }
        .marquee-row.left { animation: scrollLeft 35s linear infinite; }
        .marquee-row.right { animation: scrollRight 35s linear infinite; }

        .marquee-item {
            background: var(--wood-recessed); color: var(--sidebar-pine);
            padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 800; font-size: 0.9rem;
            text-transform: uppercase; letter-spacing: 1px;
            box-shadow: var(--shadow-engraved); border: 1px solid rgba(255,255,255,0.3);
            display: flex; align-items: center; gap: 0.5rem; white-space: nowrap;
        }

        @keyframes scrollLeft { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        @keyframes scrollRight { 0% { transform: translateX(-50%); } 100% { transform: translateX(0); } }

        /* Subtle Hero Button */
        .hero-subtle-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; 
            background: transparent; color: var(--sidebar-pine); text-decoration: none;
            padding: 1.1rem 2.5rem; font-size: 1.1rem; font-weight: 800; border-radius: 12px; 
            border: 2px solid var(--sidebar-pine); box-shadow: 0 4px 12px rgba(44, 62, 53, 0.08); 
            transition: var(--transition-smooth); margin-top: 0.5rem;
        }
        .hero-subtle-btn:hover { 
            background: var(--sidebar-pine); color: var(--wood-surface); 
            transform: translateY(-2px); box-shadow: 0 8px 16px rgba(44, 62, 53, 0.15); 
        }

        /* =========================================
           5. SCHOOL FINDER & BUTTONS
           ========================================= */
        .school-finder-section {
            background-color: var(--wood-recessed); border-radius: var(--radius-soft); 
            padding: 3rem; width: 100%; box-sizing: border-box;
            box-shadow: var(--shadow-engraved); border: 1px solid rgba(255,255,255,0.3); text-align: left;
        }

        .finder-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; }
        .finder-header h2 { font-size: 1.8rem; letter-spacing: -0.5px; margin-bottom: 0.5rem; }
        .finder-header p { font-size: 1rem; color: var(--sidebar-pine); font-weight: 600; margin: 0; }

        .search-box { position: relative; margin-bottom: 1.5rem; }
        .search-box input {
            width: 100%; padding: 1.25rem 1.25rem 1.25rem 3.5rem;
            font-size: 1.05rem; font-weight: 600; font-family: var(--font-sans);
            background-color: var(--wood-bg); color: var(--text-dark);
            border: 2px solid rgba(131, 92, 72, 0.2); border-radius: 12px; outline: none;
            box-shadow: inset 0 2px 4px rgba(62, 39, 35, 0.05); transition: var(--transition-smooth); box-sizing: border-box;
        }
        .search-box input::placeholder { color: var(--text-muted); }
        .search-box input:focus { border-color: var(--sidebar-pine); box-shadow: 0 0 0 3px rgba(44, 62, 53, 0.1); }
        .search-box i { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--sidebar-pine); font-size: 1.1rem; }

        .school-ticker-wrapper {
            height: 180px; overflow: hidden; position: relative; border: 1px solid rgba(131, 92, 72, 0.15);
            border-radius: 12px; background: var(--wood-bg); box-shadow: var(--shadow-groove);
        }
        .school-ticker-wrapper::before, .school-ticker-wrapper::after { content: ''; position: absolute; left: 0; right: 0; height: 40px; z-index: 2; pointer-events: none; }
        .school-ticker-wrapper::before { top: 0; background: linear-gradient(180deg, var(--wood-bg) 0%, rgba(235, 213, 185, 0) 100%); }
        .school-ticker-wrapper::after { bottom: 0; background: linear-gradient(0deg, var(--wood-bg) 0%, rgba(235, 213, 185, 0) 100%); }
        .school-ticker-content { animation: scrollSchools 18s linear infinite; }
        .school-ticker-wrapper:hover .school-ticker-content { animation-play-state: paused; }
        @keyframes scrollSchools { 0% { transform: translateY(0); } 100% { transform: translateY(-50%); } }

        .school-item {
            padding: 14px 24px; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid rgba(131, 92, 72, 0.1); font-size: 0.95rem; font-weight: 700; color: var(--text-dark);
        }
        
        .school-status {
            display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; color: var(--wood-surface);
            background: var(--sidebar-pine); padding: 4px 12px; border-radius: 20px; font-weight: 800; box-shadow: 0 2px 4px rgba(44, 62, 53, 0.2);
        }
        
        /* New Pending Status Style */
        .school-status.pending {
            background: rgba(196, 85, 37, 0.15);
            color: var(--accent-cinnamon-dark);
            border: 1px solid rgba(196, 85, 37, 0.2);
            box-shadow: none;
        }

        .expansion-note {
            margin-top: 1.5rem; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; font-weight: 600; color: var(--text-dark);
            background: rgba(255,255,255,0.4); padding: 12px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.6);
        }
        .expansion-note i { color: var(--accent-cinnamon); }

        /* =========================================
           6. PILLAR GRID 
           ========================================= */
        .pillars-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2.5rem; }
        .pillar-card { 
            background-color: var(--wood-surface); border-radius: var(--radius-round); padding: 3rem 2rem; 
            display: flex; flex-direction: column; gap: 1.25rem; border: var(--border-subtle); box-shadow: var(--shadow-lifted); 
            text-align: center; align-items: center; transition: transform 0.3s ease;
        }
        .pillar-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lifted-hover); }
        .pillar-icon { width: 70px; height: 70px; border-radius: 50%; background-color: var(--wood-bg); display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; color: var(--sidebar-pine); border: 2px solid rgba(131, 92, 72, 0.15); box-shadow: var(--shadow-engraved); font-size: 1.5rem; }
        .pillar-card h3 { font-size: 1.35rem; color: var(--text-dark); font-weight: 800; }
        .pillar-card p { font-size: 1.05rem; color: var(--text-muted); margin: 0; line-height: 1.6; font-weight: 500; }

        footer { text-align: center; padding: 2rem 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 600; border-top: 1px solid rgba(131, 92, 72, 0.2); margin-top: 2rem; }
        .cta-group { display: flex; gap: 1rem; margin-top: 2rem; }
        .chiseled-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; 
            background-color: var(--accent-cinnamon); color: #FFF; text-decoration: none;
            padding: 1.15rem 2.5rem; font-size: 1.1rem; font-weight: 800; border-radius: 12px; 
            border: 2px solid var(--accent-cinnamon-dark); box-shadow: 0 4px 0 var(--accent-cinnamon-dark), 0 8px 16px rgba(62, 39, 35, 0.15); 
            transition: transform 0.1s, box-shadow 0.1s; cursor: pointer; flex: 1;
        }
        .chiseled-btn:hover { transform: translateY(2px); box-shadow: 0 2px 0 var(--accent-cinnamon-dark), 0 4px 8px rgba(62, 39, 35, 0.15); }
        .chiseled-btn:active { transform: translateY(4px); box-shadow: 0 0 0 var(--accent-cinnamon-dark), 0 2px 4px rgba(62, 39, 35, 0.15); }

        .chiseled-btn.secondary { background-color: var(--wood-surface); color: var(--sidebar-pine); border-color: var(--sidebar-pine); box-shadow: 0 4px 0 var(--sidebar-pine), 0 8px 16px rgba(62, 39, 35, 0.1); }
        .chiseled-btn.secondary:hover { transform: translateY(2px); box-shadow: 0 2px 0 var(--sidebar-pine), 0 4px 8px rgba(62, 39, 35, 0.1); }
        .chiseled-btn.secondary:active { transform: translateY(4px); box-shadow: 0 0 0 var(--sidebar-pine), 0 2px 4px rgba(62, 39, 35, 0.1); }
        @media (max-width: 800px) { .cta-group { flex-direction: column; } .finder-header { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>

    <aside class="app-sidebar">
        <div class="sidebar-header">
            <a href="/">
                <img src="/public/assets/logo/white-trans-logo.png" alt="The Carve Logo" class="brand-logo-img">
            </a>
        </div>
        
        <nav class="public-nav-wrapper">
            <a href="/about" class="public-nav-link active">
                <i class="fa-solid fa-compass"></i> What is The Carve?
            </a>
            <a href="/info" class="public-nav-link">
                <i class="fa-solid fa-shield-halved"></i> Why The Carve?
            </a>
            <a href="/privacy-policy" class="public-nav-link">
                <i class="fa-solid fa-lock"></i> Privacy Policy
            </a>
            <a href="/help" class="public-nav-link">
                <i class="fa-solid fa-circle-question"></i> Help Center
            </a>
        </nav>

        <?php
            if ($name) {
                echo '<div class="security-badge">
                    <strong>You are logged on.</strong>
                    Unlock the Carve to get inside.
                </div>';
            } else {
                echo '<div class="security-badge">
                    <strong>The doors are locked.</strong>
                    A secure login is required to enter the campus rooms.
                </div>';
            }
            
        ?>
    </aside>

    <div class="main-workspace">
        <!-- FIXED TOP NAV -->
        <header class="top-nav">
            <a href="/help" class="help-btn">
                <i class="fa-solid fa-circle-question"></i> Support
            </a>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
            <?php
                    if ($name) {
                        echo "
                        <span style='color: var(--text-muted); font-weight: 600; font-size: 0.95rem;'> Welcome back, $name!</span>
                        <a href='/home' class='login-btn'>Get Inside</a>
                        ";
                    } else {
                        echo '
                        <span style="color: var(--text-muted); font-weight: 700; font-size: 0.95rem;">Already a student?</span>
                        <a href="/login" class="login-btn">Log In</a>
                        ';
                    }
                    
                ?>
            </div>
        </header>

        <main class="container">
            
            <section class="carved-panel">
                <div class="hero-text">
                    <img src="/public/assets/logo/icon-hq.png" alt="The Carve Icon" class="card-logo-icon">
                    <h1>The student-to-student campus network.</h1>
                    <p>Driven by students, built for students. A tool we wish we had throughout high school to map out schedules, discover classmates, and talk and argue about class securely with them. Think of it as your campus, unlocked, but to students only.</p>
                </div>

                <div class="marquee-container">
                    <div class="marquee-row left">
                        <div class="marquee-item"><i class="fa-solid fa-user-group"></i> Find Your Friends</div>
                        <div class="marquee-item"><i class="fa-solid fa-book-open"></i> Join Study Rooms</div>
                        <div class="marquee-item"><i class="fa-solid fa-map-location-dot"></i> Navigate The Halls</div>
                        <div class="marquee-item"><i class="fa-solid fa-eye-slash"></i> Completely Private</div>
                        <div class="marquee-item"><i class="fa-solid fa-person-chalkboard"></i> Report Substitutes</div>
                        
                        <div class="marquee-item"><i class="fa-solid fa-user-group"></i> Compete With Peers</div>
                        <div class="marquee-item"><i class="fa-solid fa-book-open"></i> Join Study Rooms</div>
                        <div class="marquee-item"><i class="fa-solid fa-map-location-dot"></i> Navigate The Halls</div>
                        <div class="marquee-item"><i class="fa-solid fa-eye-slash"></i> Completely Private</div>
                    </div>
                    
                    <div class="marquee-row right">
                        <div class="marquee-item"><i class="fa-solid fa-calendar-check"></i> Sync Your Schedule</div>
                        <div class="marquee-item"><i class="fa-solid fa-comments"></i> Debate The Homework</div>
                        <div class="marquee-item"><i class="fa-solid fa-stopwatch"></i> Live Countdowns</div>
                        <div class="marquee-item"><i class="fa-solid fa-shield-halved"></i> Strictly Peer-To-Peer</div>
                        
                        <div class="marquee-item"><i class="fa-solid fa-calendar-check"></i> Compare Schedules</div>
                        <div class="marquee-item"><i class="fa-solid fa-comments"></i> Ask Questions</div>
                        <div class="marquee-item"><i class="fa-solid fa-stopwatch"></i> Live Countdowns</div>
                        <div class="marquee-item"><i class="fa-solid fa-shield-halved"></i> Strictly Peer-To-Peer</div>
                    </div>
                </div>

                <a href="/login" class="hero-subtle-btn">
                    Unlock The Carve <i class="fa-solid fa-arrow-right"></i>
                </a>
            </section>

            <section class="school-finder-section">
                <div class="finder-header">
                    <div>
                        <h2>Is your campus on The Carve?</h2>
                        <p>Search our active directory of protected networks.</p>
                    </div>
                </div>

                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="campusSearch" placeholder="Search for your high school and press Enter...">
                </div>

                <div class="school-ticker-wrapper">
                    <div class="school-ticker-content" id="campusList">
                        <div class="school-item"><span>Loyola High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Mountain View High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Henry M. Gunn High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Los Altos High School</span> <span class="school-status pending"><i class="fa-solid fa-hammer"></i> In Progress</span></div>
                        <div class="school-item"><span>Stuyvesant High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Tunis Lake High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Oak Park High School</span> <span class="school-status pending"><i class="fa-solid fa-hammer"></i> In Progress</span></div>
                        
                        <!-- Duplicated for Marquee effect -->
                        <div class="school-item"><span>Loyola High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Mountain View High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Henry M. Gunn High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Los Altos High School</span> <span class="school-status pending"><i class="fa-solid fa-hammer"></i> In Progress</span></div>
                        <div class="school-item"><span>Stuyvesant High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Tunis Lake High School</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span></div>
                        <div class="school-item"><span>Oak Park High School</span> <span class="school-status pending"><i class="fa-solid fa-hammer"></i> In Progress</span></div>
                    </div>
                </div>

                <div class="expansion-note">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>We are actively verifying and adding new schools. Don't see yours yet? You can request campus onboarding in seconds.</span>
                </div>

                <div class="cta-group">
                    <a href="/login" class="chiseled-btn">
                        Get Started <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="/help" class="chiseled-btn secondary">
                        <i class="fa-solid fa-plus"></i> Request New Campus
                    </a>
                </div>
            </section>

            <section class="pillars-grid">
                <div class="pillar-card">
                    <div class="pillar-icon"><i class="fa-solid fa-list-check"></i></div>
                    <h3>Schedule Sync</h3>
                    <p>Enter your classes to instantly discover who shares your subjects, teachers, and exact periods.</p>
                </div>
                <div class="pillar-card">
                    <div class="pillar-icon"><i class="fa-regular fa-comments"></i></div>
                    <h3>Real Collaboration</h3>
                    <p>A conversational space designed for asking questions, sharing ideas, and organizing school events together.</p>
                </div>
                <div class="pillar-card">
                    <div class="pillar-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                    <h3>Live Navigation</h3>
                    <p>See what period it is, when lunch ends, or just check your next destination.</p>
                </div>
            </section>

            <footer>
                <p>&copy; 2026 The Carve Project. Designed for secure student collaboration.</p>
            </footer>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('campusSearch');
            const campusList = document.getElementById('campusList');
            const originalHTML = campusList.innerHTML;
            
            // Added object mapping to track statuses for search rendering
            const schoolsData = [
                { name: "Loyola High School", status: "Supported" },
                { name: "Mountain View High School", status: "Supported" },
                { name: "Henry M. Gunn High School", status: "Supported" },
                { name: "Los Altos High School", status: "Pending" },
                { name: "Stuyvesant High School", status: "Supported" },
                { name: "Tunis Lake High School", status: "Supported" },
                { name: "Oak Park High School", status: "Pending" },
                { name: "Eastlake High School", status: "Supported" },
                { name: "Cairo Verde High School", status: "Supported" }
            ];

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = searchInput.value.toLowerCase().trim();

                    if (query === '') {
                        campusList.innerHTML = originalHTML;
                        campusList.style.animation = ''; 
                        campusList.style.transform = '';
                        return;
                    }

                    campusList.style.animation = 'none';
                    campusList.style.transform = 'translateY(0)';
                    const filteredSchools = schoolsData.filter(school => school.name.toLowerCase().includes(query));
                    campusList.innerHTML = '';

                    if (filteredSchools.length > 0) {
                        filteredSchools.forEach(school => {
                            const div = document.createElement('div');
                            div.className = 'school-item';
                            if (school.status === "Supported") {
                                div.innerHTML = `<span>${school.name}</span> <span class="school-status"><i class="fa-solid fa-check"></i> Supported</span>`;
                            } else {
                                div.innerHTML = `<span>${school.name}</span> <span class="school-status pending"><i class="fa-solid fa-hammer"></i> In Progress</span>`;
                            }
                            campusList.appendChild(div);
                        });
                    } else {
                        const emptyState = document.createElement('div');
                        emptyState.className = 'school-item';
                        emptyState.style.justifyContent = 'center';
                        emptyState.style.color = 'var(--text-muted)';
                        emptyState.innerHTML = `<span>Campus not found. Request onboarding below!</span>`;
                        campusList.appendChild(emptyState);
                    }
                }
            });
        });
    </script>
</body>
</html>