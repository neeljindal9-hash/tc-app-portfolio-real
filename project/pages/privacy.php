<?php

$name = $request->session['name'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy & Terms | The Carve</title>
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

        .container { max-width: 900px; margin: 0 auto; padding: 2rem 4rem 6rem; display: flex; flex-direction: column; width: 100%; box-sizing: border-box; }

        /* =========================================
           4. HERO HEADER
           ========================================= */
        header.hero { margin: 20px auto 40px; padding: 0; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
        
        .hero-icon {
            width: 64px; height: 64px; border-radius: 16px; background: var(--wood-surface);
            color: var(--sidebar-pine); display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; border: var(--border-subtle); box-shadow: var(--shadow-lifted); margin-bottom: 0.5rem;
        }

        header.hero h1 { font-size: 3.2rem; line-height: 1.1; letter-spacing: -0.5px; color: var(--text-dark); }
        .hero-subtitle { font-size: 1.15rem; color: var(--text-muted); max-width: 680px; margin: 0 auto; line-height: 1.6; font-weight: 600; }

        /* =========================================
           5. LEGAL DOCUMENT PANEL
           ========================================= */
        .document-panel {
            background-color: var(--wood-surface); border-radius: var(--radius-soft); padding: 4rem;
            border: var(--border-subtle); box-shadow: var(--shadow-lifted); text-align: left;
        }

        .document-panel h2 {
            font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; color: var(--sidebar-pine);
            margin-top: 3rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(131, 92, 72, 0.15);
        }
        
        .document-panel h2:first-of-type { margin-top: 0; }

        .document-panel h3 {
            font-family: 'Nunito', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-dark);
            margin-top: 2.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }

        .document-panel p {
            font-size: 1.05rem; color: var(--text-muted); line-height: 1.7; font-weight: 500; margin-bottom: 1.25rem;
        }

        .document-panel strong { color: var(--text-dark); font-weight: 700; }

        .document-panel ul {
            list-style: none; padding: 0; margin: 0 0 1.5rem 0;
        }

        .document-panel li {
            position: relative; padding-left: 1.75rem; font-size: 1.05rem; color: var(--text-muted); line-height: 1.7; font-weight: 500; margin-bottom: 0.75rem;
        }

        .document-panel li::before {
            content: '\f111'; /* Solid circle in FontAwesome */
            font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; left: 0; top: 0.2rem;
            font-size: 0.5rem; color: var(--accent-cinnamon);
        }

        .effective-date {
            display: inline-block; background: var(--wood-recessed); padding: 0.5rem 1rem; border-radius: 8px;
            font-size: 0.9rem; font-weight: 700; color: var(--sidebar-pine); margin-bottom: 2rem; border: var(--border-subtle); box-shadow: var(--shadow-engraved);
        }

        footer { text-align: center; padding: 2rem 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 600; border-top: 1px solid rgba(131, 92, 72, 0.2); margin-top: 4rem; }
        
        @media (max-width: 800px) { .document-panel { padding: 2.5rem; } }
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
            <a href="/about" class="public-nav-link">
                <i class="fa-solid fa-compass"></i> What is The Carve?
            </a>
            <a href="/info" class="public-nav-link">
                <i class="fa-solid fa-shield-halved"></i> Why The Carve?
            </a>
            <a href="/privacy-policy" class="public-nav-link active">
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
            
            <header class="hero">
                <div class="hero-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                <h1>Privacy Policy & Terms</h1>
                <p class="hero-subtitle">We believe technology should protect you, not exploit your data. Here is exactly how we handle your information and the rules of the campus.</p>
            </header>

            <section class="document-panel">
                <div id="privacy">
                    <h2>Privacy Policy</h2>
                    <span class="effective-date">Effective Date: September 2026</span>

                    <p>Welcome to The Carve. We built this platform because we believe technology should protect students, not exploit their data. This Privacy Policy explains how we collect, use, and safeguard your information.</p>

                    <h3>1. The Short Version (TL;DR)</h3>
                    <ul>
                        <li><strong>No Data Selling:</strong> We do not and will never sell your personal data to third parties, advertisers, or data brokers.</li>
                        <li><strong>Student-Only:</strong> Our platform is designed for verified high school students.</li>
                        <li><strong>Your Data is Yours:</strong> You control your schedule and your account. You can delete your data completely at any time.</li>
                    </ul>

                    <h3>2. Information We Collect</h3>
                    <p>To make The Carve work for your campus, we collect the following:</p>
                    <ul>
                        <li><strong>Account Information:</strong> Your name, email address, school ID number (used exclusively for secure verification), and high school.</li>
                        <li><strong>Academic Information:</strong> Your class schedule, teachers, and periods, which you manually input or sync.</li>
                        <li><strong>Usage Data:</strong> Basic diagnostic data to keep the app running smoothly and securely (e.g., device type, IP address for security logging).</li>
                        <li><strong>Location Data:</strong> We <strong>never</strong> track your live GPS location. Any "live routing" features shown in the app are based purely on the static schedule data and room numbers you provide.</li>
                    </ul>

                    <h3>3. How We Use Your Information</h3>
                    <ul>
                        <li>To match you with classmates who share your classes or periods.</li>
                        <li>To facilitate in-app communication, study groups, and substitute teacher reporting.</li>
                        <li>To verify your identity and ensure you belong to the requested campus network.</li>
                        <li>To maintain the security and integrity of our platform.</li>
                    </ul>

                    <h3>4. Information Sharing</h3>
                    <p>Your schedule and profile are visible to <strong>verified students within your specific high school campus</strong>. We do not share your information across different schools, with school administrators, or with outside marketing agencies. We will only disclose data if legally required to do so by a valid subpoena or court order.</p>

                    <h3>5. Age Restriction (14+)</h3>
                    <p>The Carve is strictly for users who are <strong>14 years of age or older</strong>. We do not knowingly collect personal information from children under 13. If we discover a user is under 13, their account and all associated data will be immediately and permanently deleted in compliance with COPPA.</p>

                    <h3>6. Your Rights & Deletion</h3>
                    <p>You can delete your account at any time through the app settings. When you request account deletion, we wipe your profile, schedule, and personal data from our active servers, rather than simply hiding it.</p>
                </div>
                <br><br>
                <div id="terms">
                    <h2>Terms and Conditions</h2>
                    <span class="effective-date">Effective Date: September 2026</span>

                    <h3>1. Acceptance of Terms</h3>
                    <p>By creating an account and accessing The Carve, you agree to abide by these Terms and Conditions. If you do not agree to these rules, do not use the app.</p>

                    <h3>2. Eligibility</h3>
                    <p>You must be <strong>at least 14 years old</strong> and currently enrolled in one of our supported high school campuses to use The Carve. You agree to provide accurate, truthful information during the verification process. Attempting to impersonate another student, using a false identity, or attempting to access a campus you do not attend is grounds for immediate termination.</p>

                    <h3>3. User Conduct & Zero Tolerance Policy</h3>
                    <p>The Carve is a collaborative academic tool. You agree <strong>not</strong> to:</p>
                    <ul>
                        <li>Engage in bullying, harassment, hate speech, or intimidation of any kind.</li>
                        <li>Use the platform for stalking or unauthorized tracking of other students.</li>
                        <li>Scrape, mine, or extract user data or schedules from the platform using automated tools.</li>
                        <li>Post illegal, sexually explicit, profane, or otherwise harmful content.</li>
                        <li>Spread misinformation, join the wrong classes, or otherwise harm the experience of other users.</li>
                    </ul>
                    <p>Violation of these rules will result in an immediate and permanent ban from the network.</p>

                    <h3>4. Platform Independence</h3>
                    <p>The Carve is an independent, student-led platform. We are not officially affiliated with, endorsed by, or monitored by any high school administration or school district unless explicitly stated on your campus page.</p>

                    <h3>5. Account Security</h3>
                    <p>You are responsible for maintaining the confidentiality of your login credentials. Do not share your account access or invite codes with unverified individuals. You must notify us immediately if you suspect unauthorized access to your account.</p>

                    <h3>6. Limitation of Liability</h3>
                    <p>The Carve is provided "as is" and "as available." To the fullest extent permitted by law, The Carve and its creators shall not be liable for any indirect, incidental, or consequential damages arising from your use of the app, interactions with other users, or unauthorized access to your data.</p>

                    <h3>7. Modifications</h3>
                    <p>We reserve the right to modify these terms at any time to reflect updates to the law or our platform. We will notify users of significant changes. Continued use of the app after changes indicates your acceptance of the new terms.</p>
                </div>
            </section>

            <footer>
                <p>&copy; 2026 The Carve Project. Designed for secure student collaboration.</p>
            </footer>
        </main>
    </div>

    <script>
        const url = window.location.href;
        if (url.includes("terms")) {
            window.scrollTo(0, 2160);
        }
    </script>

</body>
</html>