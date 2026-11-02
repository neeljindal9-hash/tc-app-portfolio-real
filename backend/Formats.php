<?php

class Formats {
    public static function admindataRead($rawData) {
        $encodedData = htmlspecialchars($rawData, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<style>
    /* * SLATE DASHBOARD THEME 
     * Modern grays, crisp borders, status-driven accents
     */
    :root {
        --bg-main: #0f172a;       /* Deep slate base */
        --bg-panel: #1e293b;      /* Elevated slate panel */
        --border: #334155;        /* Subtle divider */
        --text-main: #94a3b8;     /* Muted reading text */
        --text-bright: #f8fafc;   /* Bright white for values */
        
        /* Status Tokens */
        --status-ok: #10b981;     /* Emerald Green */
        --status-err: #ef4444;    /* Rose Red */
        --status-warn: #f59e0b;   /* Amber */
        
        /* Syntax */
        --accent: #38bdf8;        /* Cyan for structural highlights */
        --key: #c084fc;           /* Purple for keys */
    }

    body { background-color: #020617; padding: 20px; font-family: sans-serif; }

    .admin-term {
        background-color: var(--bg-main);
        color: var(--text-main);
        font-family: 'Fira Code', 'Consolas', monospace;
        border-radius: 8px;
        border: 1px solid var(--border);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        max-width: 1300px;
        margin: 0 auto;
        font-size: 13px;
        line-height: 1.6;
        display: flex;
        flex-direction: column;
    }

    /* HEADER & CONTROLS */
    .admin-term-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #0b1120;
        padding: 16px 24px;
        border-bottom: 1px solid var(--border);
        border-radius: 8px 8px 0 0;
    }

    .server-status {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-bright);
        font-weight: bold;
        font-size: 1.1rem;
    }

    .status-dot {
        height: 10px;
        width: 10px;
        background-color: var(--status-ok);
        border-radius: 50%;
        box-shadow: 0 0 10px var(--status-ok);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    .controls { display: flex; gap: 12px; align-items: center; }

    button {
        background: var(--bg-panel);
        border: 1px solid var(--border);
        color: var(--text-main);
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-family: inherit;
        font-weight: bold;
        transition: all 0.2s;
    }
    button:hover { background: var(--border); color: var(--text-bright); }
    
    .timer-display { font-variant-numeric: tabular-nums; font-weight: bold; color: var(--accent); }

    /* DASHBOARD GRID */
    .admin-content { padding: 24px; }

    .admin-term-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-box {
        background: var(--bg-panel);
        border: 1px solid var(--border);
        padding: 16px;
        border-radius: 6px;
    }

    .stat-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
    .stat-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
    .stat-value { font-size: 1.2rem; color: var(--text-bright); }
    
    .status-token {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: bold;
        color: #fff;
    }
    .token-ok { background: var(--status-ok); }
    .token-err { background: var(--status-err); }
    .token-warn { background: var(--status-warn); }

    .admin-term-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width: 900px) { .admin-term-grid { grid-template-columns: 1fr; } }
    
    .panel { background: var(--bg-panel); border: 1px solid var(--border); border-radius: 6px; }
    .panel-header {
        background: rgba(0,0,0,0.2);
        padding: 12px 16px;
        border-bottom: 1px solid var(--border);
        color: var(--accent);
        font-weight: bold;
    }
    .panel-content {
        padding: 16px;
        overflow-y: auto;
        max-height: 400px;
        white-space: pre;
    }

    /* Scrollbars & Syntax */
    .panel-content::-webkit-scrollbar { width: 8px; }
    .panel-content::-webkit-scrollbar-track { background: var(--bg-panel); }
    .panel-content::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
    
    .hl-key { color: var(--key); }
    .hl-hash { color: #818cf8; cursor: help; border-bottom: 1px dotted #4f46e5; }
    .hl-hash:hover { color: var(--text-bright); }
</style>

<div class="admin-term">
    <div class="admin-term-header">
        <div class="server-status">
            <div class="status-dot"></div>
            <span id="srv-name">SYS_MONITOR</span>
        </div>
        <div class="controls">
            <span class="timer-display" id="adm-timer">--</span>
            <button id="btn-toggle-reload">[ AUTO: OFF ]</button>
            <button id="btn-reload">RELOAD NOW</button>
        </div>
    </div>
    
    <div class="admin-content">
        <div class="admin-term-stats" id="adm-stats"></div>
        <div class="admin-term-grid" id="adm-grid"></div>
    </div>
</div>

<script type="text/template" id="adm-raw-data">
{$encodedData}
</script>

<script>
    (function(){
        const raw = document.getElementById('adm-raw-data').textContent;
        const getMatch = (regex, fallback = 'N/A') => (raw.match(regex) ? raw.match(regex)[1].trim() : fallback);

        // --- 1. DATA EXTRACTION ---
        const serverName = getMatch(/Server name:\s*(.+)/);
        document.getElementById('srv-name').innerText = serverName !== 'N/A' ? serverName : 'NODE_UNKNOWN';

        let sessionData = "ERR_NO_DATA", sessionIds = "ERR_NO_DATA", pdoStatus = "ERR_NO_DATA";
        try {
            sessionData = raw.split('SESSION IDs:')[0].replace('SESSION DATA:', '').trim();
            sessionIds = raw.split('SESSION IDs:')[1].split('PDO STATUS:')[0].trim();
            pdoStatus = raw.split('DB Conn data ->')[1].split('<- DB Conn data')[0].trim();
        } catch (e) { console.error("Data fault", e); }

        // --- 2. CLIENT-SIDE VALIDATION ---
        
        // Validate Sessions (Check for duplicate IDs)
        const sessionHashes = [...sessionData.matchAll(/\[([a-f0-9]{64})\] =>/g)].map(m => m[1]);
        const uniqueSessions = new Set(sessionHashes);
        const hasDuplicates = uniqueSessions.size !== sessionHashes.length;
        const sessionStatusHtml = hasDuplicates 
            ? '<span class="status-token token-err">DUPES FOUND</span>' 
            : '<span class="status-token token-ok">VALID</span>';

        // Validate DLL (Check for Head/Tail missing, or null gaps)
        let dllStatusHtml = '<span class="status-token token-ok">INTEGRITY OK</span>';
        const headExists = /\[head\] => [a-f0-9]{64}/.test(sessionIds);
        const tailExists = /\[tail\] => [a-f0-9]{64}/.test(sessionIds);
        
        // In print_r, an empty pointer usually renders as `[front] => ` followed by a newline.
        // Add an extra backslash before the 'n'
const emptyLinks = [...sessionIds.matchAll(/\[(?:front|behind)\] =>\s*\\n/g)];
        
        if (sessionHashes.length > 0) {
            if (!headExists || !tailExists) {
                dllStatusHtml = '<span class="status-token token-err">MISSING HEAD/TAIL</span>';
            } else if (emptyLinks.length > 2) {
                // A valid DLL should only have 2 nulls (head.behind and tail.front)
                dllStatusHtml = '<span class="status-token token-err">NULL GAPS DETECTED</span>';
            }
        } else {
            dllStatusHtml = '<span class="status-token token-warn">EMPTY</span>';
        }

        // Validate DB Connections (Highlight if > 0)
        const connInUseStr = getMatch(/CONNECTIONS IN USE:\s*(\d+)/, '0');
        const activeConns = parseInt(connInUseStr, 10);
        const dbStatusHtml = activeConns > 0 
            ? '<span class="status-token token-warn">ACTIVE LOCK</span>' 
            : '<span class="status-token token-ok">ALL IDLE</span>';

        // --- 3. RENDER STATS DASHBOARD ---
        const stats = {
            'Total Sessions': { val: sessionHashes.length, tag: sessionStatusHtml },
            'DLL Health': { val: 'Pointers', tag: dllStatusHtml },
            'Active DB Conns': { val: activeConns, tag: dbStatusHtml },
            'Pool Index': { val: getMatch(/CurrentNextIndex:\s*(\d+)/), tag: '' }
        };

        const statsContainer = document.getElementById('adm-stats');
        for (const [label, data] of Object.entries(stats)) {
            statsContainer.innerHTML += `
                <div class="stat-box">
                    <div class="stat-header">
                        <span class="stat-label">\${label}</span>
                        \${data.tag}
                    </div>
                    <div class="stat-value">\${data.val}</div>
                </div>
            `;
        }

        // --- 4. RENDER PANELS & HIGHLIGHTING ---
        const formatBlock = (text) => {
            let html = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            html = html.replace(/([a-f0-9]{40,64})/gi, (m) => `<span class="hl-hash" title="\${m}">\${m.substring(0,6)}..\${m.substring(m.length-6)}</span>`);
            html = html.replace(/\[(.*?)\](\s*=&gt;)/g, '<span class="hl-key">[$1]</span>$2');
            return html;
        };

        const grid = document.getElementById('adm-grid');
        const createPanel = (title, content, full = false) => `
            <div class="panel" \${full ? 'style="grid-column: 1 / -1;"' : ''}>
                <div class="panel-header">\${title}</div>
                <div class="panel-content">\${formatBlock(content)}</div>
            </div>`;

        grid.innerHTML += createPanel('// SESSION_DATA_DUMP', sessionData);
        grid.innerHTML += createPanel('// SESSION_IDS_DLL', sessionIds);
        grid.innerHTML += createPanel('// PDO_CONNECTION_POOL', pdoStatus, true);

        // --- 5. TIMER & RELOAD LOGIC ---
        let autoReload = false;
        let countdown = 5; // Start manual timer at a blank/idle state
        const timerDisplay = document.getElementById('adm-timer');
        const btnToggle = document.getElementById('btn-toggle-reload');
        const btnReload = document.getElementById('btn-reload');

        btnReload.addEventListener('click', () => window.location.reload());

        btnToggle.addEventListener('click', () => {
            autoReload = !autoReload;
            if(autoReload) {
                countdown = 2; // 2 seconds when toggled on
                btnToggle.innerText = '[ AUTO: ON ]';
                btnToggle.style.color = 'var(--status-ok)';
                timerDisplay.innerText = `0\${countdown}s`;
            } else {
                btnToggle.innerText = '[ AUTO: OFF ]';
                btnToggle.style.color = 'var(--text-main)';
                timerDisplay.innerText = '--';
            }
        });

        setInterval(() => {
            if(!autoReload) return;
            countdown--;
            timerDisplay.innerText = `0\${countdown}s`;
            if(countdown <= 0) window.location.reload();
        }, 1000);

    })();
</script>
HTML;
    }
}