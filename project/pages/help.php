<?php

$name =$request->session['name'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center | The Carve</title>
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
            
            --radius-soft: 16px;
            --radius-round: 24px;
            --border-subtle: 1px solid rgba(131, 92, 72, 0.15);
            
            --shadow-lifted: 0 4px 12px rgba(62, 39, 35, 0.05), 0 1px 3px rgba(62, 39, 35, 0.08);
            --shadow-lifted-hover: 0 8px 24px rgba(62, 39, 35, 0.08), 0 2px 6px rgba(62, 39, 35, 0.06);
            --shadow-engraved: inset 0 2px 6px rgba(62, 39, 35, 0.08), inset 0 -1px 2px rgba(255, 255, 255, 0.6);
            
            --transition-smooth: 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            font-family: 'Nunito', system-ui, sans-serif;
        }

        body {
            margin: 0; padding: 0; background-color: var(--wood-bg);
            background-image: radial-gradient(circle at top right, rgba(255,255,255,0.3) 0%, transparent 60%);
            color: var(--text-dark); -webkit-font-smoothing: antialiased; min-height: 100vh; display: flex;
        }

        h1, h2, h3 { font-family: 'Playfair Display', serif; margin: 0; color: var(--text-dark); }

        /* =========================================
           2. SIDEBAR (PUBLIC VIEW)
           ========================================= */
        .app-sidebar { width: 280px; background-color: var(--sidebar-pine); color: var(--wood-surface); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 500; }
        .sidebar-header { padding: 2.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: center; }
        .brand-logo-img { height: 48px; }
        .public-nav-wrapper { padding: 2.5rem 1.5rem; flex: 1; display: flex; flex-direction: column; gap: 0.5rem; }
        .public-nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 700; font-size: 1.05rem; border-radius: 12px; transition: var(--transition-smooth); }
        .public-nav-link.active { background: rgba(0,0,0,0.2); color: var(--wood-surface); box-shadow: inset 0 1px 3px rgba(0,0,0,0.3); }
        .public-nav-link i { width: 20px; text-align: center; }
        .security-badge { margin: 2rem 1.5rem; padding: 1.25rem; background: rgba(0,0,0,0.15); border-radius: 12px; color: rgba(255,255,255,0.6); font-size: 0.85rem; font-weight: 600; line-height: 1.5; }
        .security-badge strong { color: var(--wood-surface); font-size: 0.95rem; display: block; margin-bottom: 0.25rem; }

        /* =========================================
           3. MAIN WORKSPACE & HEADER
           ========================================= */
        .main-workspace { margin-left: 280px; width: calc(100% - 280px); display: flex; flex-direction: column; }
        .top-nav { padding: 1.25rem 4rem; display: flex; justify-content: flex-end; align-items: center; gap: 1.5rem; background-color: rgba(235, 213, 185, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: var(--border-subtle); position: sticky; top: 0; z-index: 100; }
        .help-btn { font-family: 'Nunito', sans-serif; background: transparent; border: none; color: var(--sidebar-pine); font-weight: 800; font-size: 0.95rem; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .login-btn { font-family: 'Nunito', sans-serif; background: var(--wood-surface); border: 2px solid var(--sidebar-pine); color: var(--sidebar-pine); padding: 0.5rem 1.5rem; border-radius: 8px; font-weight: 800; font-size: 0.95rem; text-decoration: none; box-shadow: var(--shadow-lifted); transition: var(--transition-smooth); }
        .login-btn:hover { background: var(--sidebar-pine); color: var(--wood-surface); }

        .container { max-width: 1000px; margin: 0 auto; padding: 2rem 4rem 6rem; display: flex; flex-direction: column; gap: 3rem; width: 100%; box-sizing: border-box; }

        /* =========================================
           4. HERO HEADER
           ========================================= */
        header.hero { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
        .hero-icon { width: 72px; height: 72px; border-radius: 18px; background: var(--sidebar-pine); color: var(--wood-surface); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; box-shadow: var(--shadow-lifted); margin-bottom: 0.5rem; }
        header.hero h1 { font-size: 3.2rem; line-height: 1.1; letter-spacing: -0.5px; }
        .hero-subtitle { font-size: 1.15rem; color: var(--text-muted); max-width: 680px; margin: 0 auto; line-height: 1.6; font-weight: 600; }

        /* =========================================
           5. INTERACTIVE SUPPORT DESK 
           ========================================= */
        .support-desk-container {
            background-color: var(--wood-surface); border-radius: var(--radius-round);
            border: var(--border-subtle); box-shadow: var(--shadow-lifted);
            position: relative; overflow: hidden; min-height: 440px;
            display: flex; flex-direction: column;
        }

        .support-desk-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px; background: var(--sidebar-pine); z-index: 10; }

        /* Step 1: Pre-fill Form */
        #prefill-step { padding: 3.5rem; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1.5rem; }
        #prefill-step h2 { font-size: 2.2rem; color: var(--sidebar-pine); letter-spacing: -0.5px; }
        #prefill-step p { font-size: 1.1rem; color: var(--text-muted); font-weight: 600; max-width: 500px; line-height: 1.6; margin: 0; }

        .input-group { width: 100%; max-width: 420px; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; text-align: left; }
        
        .input-field {
            width: 100%; padding: 1rem 1.25rem; font-size: 1.05rem; font-weight: 600; font-family: 'Nunito', sans-serif;
            background-color: var(--wood-bg); color: var(--text-dark); border: 2px solid rgba(131, 92, 72, 0.2); 
            border-radius: 12px; outline: none; box-shadow: inset 0 2px 4px rgba(62, 39, 35, 0.05); transition: var(--transition-smooth); box-sizing: border-box;
        }
        
        select.input-field {
            cursor: pointer; appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%232C3E35%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat; background-position: right 1.2rem top 50%; background-size: 0.8rem auto;
        }
        
        .input-field:focus { border-color: var(--sidebar-pine); }

        .form-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; 
            background: var(--sidebar-pine); color: var(--wood-surface); border: none; cursor: pointer;
            padding: 1.25rem; font-size: 1.15rem; font-weight: 800; border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(44, 62, 53, 0.15); transition: var(--transition-smooth);
            width: 100%; font-family: 'Nunito', sans-serif; margin-top: 0.5rem;
        }
        .form-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(44, 62, 53, 0.25); background: #1e2b25; }

        /* Step 2: Iframe Container */
        #iframe-step { display: none; width: 100%; height: 950px; border: none; background: transparent; }

        /* =========================================
           6. HELP TOPICS GRID 
           ========================================= */
        .section-title { text-align: center; font-size: 1.8rem; color: var(--text-dark); margin-bottom: -1rem; margin-top: 1rem; }
        .help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .help-card { background: var(--wood-recessed); border-radius: var(--radius-soft); padding: 2rem; border: 1px solid rgba(255,255,255,0.3); box-shadow: var(--shadow-engraved); display: flex; flex-direction: column; gap: 1rem; }
        .help-card-header { display: flex; align-items: center; gap: 1rem; }
        .help-card-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--wood-surface); display: flex; align-items: center; justify-content: center; color: var(--accent-cinnamon); font-size: 1.2rem; box-shadow: var(--shadow-lifted); border: var(--border-subtle); flex-shrink: 0; }
        .help-card h3 { font-size: 1.2rem; font-weight: 800; line-height: 1.3;}
        .help-card p { font-size: 0.95rem; color: var(--text-muted); font-weight: 600; line-height: 1.6; margin: 0; }

        footer { text-align: center; padding: 2rem 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 600; border-top: 1px solid rgba(131, 92, 72, 0.2); margin-top: 2rem; }
    </style>
</head>
<body>

    <aside class="app-sidebar">
        <div class="sidebar-header">
            <a href="/"><img src="/public/assets/logo/white-trans-logo.png" alt="The Carve Logo" class="brand-logo-img"></a>
        </div>
        <nav class="public-nav-wrapper">
            <a href="/about" class="public-nav-link"><i class="fa-solid fa-compass"></i> What is The Carve?</a>
            <a href="/info" class="public-nav-link"><i class="fa-solid fa-shield-halved"></i> Why The Carve?</a>
            <a href="/privacy-policy" class="public-nav-link"><i class="fa-solid fa-lock"></i> Privacy Policy</a>
            <a href="/help" class="public-nav-link active"><i class="fa-solid fa-circle-question"></i> Help Center</a>
        </nav>
        <?php
            if ($name) {
                echo '<div class="security-badge"><strong>You are logged on.</strong> Unlock the Carve to get inside.</div>';
            } else {
                echo '<div class="security-badge"><strong>The doors are locked.</strong> A secure login is required to enter the campus rooms.</div>';
            }
        ?>
    </aside>

    <div class="main-workspace">
        <header class="top-nav">
            <a href="/help" class="help-btn"><i class="fa-solid fa-circle-question"></i> Support</a>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <?php
                    if ($name) {
                        echo "<span style='color: var(--text-muted); font-weight: 600; font-size: 0.95rem;'> Welcome back, $name!</span> <a href='/home' class='login-btn'>Get Inside</a>";
                    } else {
                        echo '<span style="color: var(--text-muted); font-weight: 700; font-size: 0.95rem;">Already a student?</span> <a href="/login" class="login-btn">Log In</a>';
                    }
                ?>
            </div>
        </header>

        <main class="container">
            <header class="hero">
                <div class="hero-icon"><i class="fa-solid fa-life-ring"></i></div>
                <h1>How can we help?</h1>
                <p class="hero-subtitle">Whether you need to request a new campus, report a bug, or give feedback, you are in the right place. The Carve team is here to assist.</p>
            </header>

            <!-- INTERACTIVE SUPPORT DESK -->
            <section class="support-desk-container">
                
                <!-- STEP 1: Capture Info -->
                <div id="prefill-step">
                    <h2>Official Support Desk</h2>
                    <p>Let's get started. Enter your details and select the nature of your request so we can route your ticket to the right team.</p>
                    
                    <div class="input-group">
                        <select id="ticketCategory" class="input-field">
                            <option value="" disabled selected>What best describes your need?</option>
                            <option value="Report a Bug">Report a Bug</option>
                            <option value="Login/Registration/Account Issue">Login/Registration/Account Issue</option>
                            <option value="Request New Campus">Request New Campus</option>
                            <option value="Report User / Content Violation">Report User / Content Violation</option>
                            <option value="Volunteer Service Opportunities">Volunteer Service Opportunities</option>
                            <option value="General Feedback">General Feedback</option>
                            <option value="Another Problem">Another Problem</option>
                        </select>
                        <input type="text" id="ticketName" class="input-field" placeholder="Full Name" value="<?php echo htmlspecialchars($name ?? '', ENT_QUOTES); ?>">
                        <input type="email" id="ticketEmail" class="input-field" placeholder="Account Email Address">
                        
                        <button onclick="loadGoogleForm()" class="form-btn">
                            Continue to Form <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: The Google Form Iframe -->
                <iframe id="iframe-step" src="" frameborder="10" marginheight="0" marginwidth="0">Loading…</iframe>

            </section>

            <h2 class="section-title">Common Ticket Categories</h2>
            
            <section class="help-grid">
                <div class="help-card">
                    <div class="help-card-header"><div class="help-card-icon"><i class="fa-solid fa-bug"></i></div><h3>Report a Bug</h3></div>
                    <p>Encountered a glitch? Schedule not syncing correctly? Let our dev team know so we can squash the bug and keep the platform running smoothly.</p>
                </div>
                <div class="help-card">
                    <div class="help-card-header"><div class="help-card-icon"><i class="fa-solid fa-user-lock"></i></div><h3>Login & Account Issues</h3></div>
                    <p>Locked out of your account, having trouble with verification, or need to update your student email? We can help you regain access.</p>
                </div>
                <div class="help-card">
                    <div class="help-card-header"><div class="help-card-icon"><i class="fa-solid fa-school-flag"></i></div><h3>Request New Campus</h3></div>
                    <p>Don't see your high school in our active directory? Submit your school's details so we can verify and build out your local campus network.</p>
                </div>
                <div class="help-card">
                    <div class="help-card-header"><div class="help-card-icon"><i class="fa-solid fa-shield-halved"></i></div><h3>Report User / Violation</h3></div>
                    <p>We have a strict zero-tolerance policy for bullying, harassment, and harmful content. Report malicious behavior so our team can investigate immediately.</p>
                </div>
                <div class="help-card">
                    <div class="help-card-header"><div class="help-card-icon"><i class="fa-solid fa-handshake-angle"></i></div><h3>Volunteer Opportunities</h3></div>
                    <p>Looking to get involved with the community? Apply to become a campus ambassador, help beta-test new features, or even help our code & design team.</p>
                </div>
                <div class="help-card">
                    <div class="help-card-header"><div class="help-card-icon"><i class="fa-solid fa-lightbulb"></i></div><h3>General Feedback</h3></div>
                    <p>Have an idea for a cool new feature? Built by students, for students—we want to hear how we can make your campus experience even better.</p>
                </div>
            </section>

            <footer><p>&copy; 2026 The Carve Project. Designed for secure student collaboration.</p></footer>
        </main>
    </div>

    <script>
        function loadGoogleForm() {
            const category = document.getElementById('ticketCategory').value;
            const name = document.getElementById('ticketName').value;
            const email = document.getElementById('ticketEmail').value;
            
            if (!category) {
                alert("Please select what best describes your need.");
                return;
            }
            if (!name || !email) {
                alert("Please enter both your name and email to continue.");
                return;
            }

            // Google Form IDs based on user's exact inputs
            const formBaseURL = "https://docs.google.com/forms/d/e/1FAIpQLSfwBGYx6SuznN4vOOJ2G4h7apdCJow5EGVoBoVtMosWP_WTnA/viewform?embedded=true";
            const emailEntryID = "entry.756556790";
            const nameEntryID = "entry.717426322";
            const categoryEntryID = "entry.1445084890";

            // Construct the final URL with the pre-filled data
            const finalURL = `${formBaseURL}&${emailEntryID}=${encodeURIComponent(email)}&${nameEntryID}=${encodeURIComponent(name)}&${categoryEntryID}=${encodeURIComponent(category)}`;

            // Hide the Step 1 UI
            document.getElementById('prefill-step').style.display = 'none';
            
            // Show the Iframe and set its source to the prefilled URL
            const iframe = document.getElementById('iframe-step');
            iframe.src = finalURL;
            iframe.style.display = 'block';
            
            // Smooth scroll up to ensure the top of the form is visible
            document.querySelector('.support-desk-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    </script>
</body>
</html>