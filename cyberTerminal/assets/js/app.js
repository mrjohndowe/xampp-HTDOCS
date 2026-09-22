const terminal = {
    // 1. Elements start as null, assigned inside init()
    output: null,
    input: null,
    container: null,
    cursor: null,
    fx: null,

    // Core settings
    typingDelay: 20,
    history: [],
    historyIndex: -1,
    commandCount: 0,
    isMusicPlaying: false,

    COMMANDS: {
        help: 'List all commands',
        about: 'Shows system creator info (Mr John Dowe)',
        'calc [expr]': 'Instantly evaluates math (e.g. calc 23*7)',
        joke: 'Shows random tech or cyber jokes',
        quote: 'Gives motivational cyber quotes',
        'hack [target]': 'Fake hacking animation with progress bars & trace logs',
        matrix: 'Starts/stops binary-rain screen animation',
        'theme [dark/pink/neon/blue/red/purple]': 'Change theme colors dynamically',
        clear: 'Clears the terminal screen',
        projects: 'Shows your CALCuS + NEO links',
        'echo [text]': 'Repeats back your text like real terminals',
        time: 'Shows current date & time in neon style',
        scan: 'Scans for "AI subsystems" (fake loading animation)',
        music: 'Plays/stops cyber beeps or background sound',
        'ascii [text]': 'Prints big ASCII art style text',
        credits: 'Shows developer + version info',
        shutdown: 'Ends session with animated exit screen',
    },

    // --- UTILITY FUNCTIONS ---
    scrollToBottom: function() {
        this.output.scrollTop = this.output.scrollHeight;
    },

    addLine: function(text, isSystem = false, allowHtml = false) {
        const line = document.createElement('div');
        line.classList.add('line');

        if (isSystem) {
            line.classList.add('system-message');
        }

        if (allowHtml) {
            line.innerHTML = text;
        } else {
            line.textContent = text;
        }

        this.output.appendChild(line);
        this.scrollToBottom();

        return line;
    },

    typeMessage: async function(message, isSystem = false, delayOverride = null) {
        const line = this.addLine('', isSystem);
        const textElement = document.createElement('span');
        line.appendChild(textElement);

        const delay = delayOverride !== null ? delayOverride : this.typingDelay;

        for (let i = 0; i < message.length; i++) {
            textElement.textContent += message.charAt(i);
            this.scrollToBottom();
            await new Promise(resolve => setTimeout(resolve, delay));
        }
        this.scrollToBottom();
    },

    printCommand: function(command) {
        this.addLine(
            `<span class="prompt-text">OPERATOR@CYBER-V2.5 &gt;</span> ${command}`,
            false,
            true
        );
        this.scrollToBottom();
    },

    // --- CORE COMMAND HANDLER ---
    handleCommand: async function(command) {
        this.commandCount++;
        const parts = command.trim().toLowerCase().split(/\s+/);
        const cmd = parts[0];
        const args = parts.slice(1).join(' ');

        // Auto-Promotion
        if (this.commandCount % 5 === 0) {
            await this.typeMessage('Try CALCuS v3.0 — my sibling project 🧩', true, 10);
        }

        switch (cmd) {
            // All calls use 'this.' to access other methods
            case 'help': this.showHelp(); break;
            case 'about': await this.typeMessage("Cyber Terminal V2.5 — Created by Mr John Dowe. An attempt to build a full cyber-AI environment.", true); break;
            case 'calc': this.handleCalc(args); break;
            case 'joke': await this.showJoke(); break;
            case 'quote': await this.showQuote(); break;
            case 'hack': this.handleHack(args); break;
            case 'matrix': this.toggleMatrix(); break;
            case 'theme': this.setTheme(args); break;
            case 'clear': this.output.innerHTML = ''; break;
            case 'projects': this.showProjects(); break;
            case 'echo': await this.typeMessage(args || 'Please provide text to echo.', false); break;
            case 'time': await this.showTime(); break;
            case 'scan': this.handleScan(); break;
            case 'music': this.toggleMusic(); break;
            case 'ascii': this.showAscii(args); break;
            case 'credits': await this.showCredits(); break;
            case 'shutdown': this.handleShutdown(); break;
            case '': break;
            default:
                await this.typeMessage(`Error: Command not found: ${cmd}. Type 'help' for a list of commands.`, false);
        }

        this.scrollToBottom();
        this.input.value = '';
    },



    showHelp: function () {
        const commandsList = this.COMMANDS;

        this.addLine("--- Available Commands ---", true);

        for (const [cmd, desc] of Object.entries(commandsList)) {
            this.addLine(`${cmd.padEnd(25, " ")} ${desc}`);
        }

        this.scrollToBottom();
    },


    handleCalc: function(expression) {
        try {
            if (/[a-zA-Z]/.test(expression)) throw new Error("Invalid characters detected.");
            const result = new Function('return ' + expression)();
            this.typeMessage(`Result: ${result}`, true);
        } catch (e) {
            this.typeMessage(`Error: Invalid expression. ${e.message}`, false);
        }
    },

    JOKES: ["Why was the JavaScript developer sad? Because he didn't Node how to Express himself.", "There are 10 types of people in the world: those who understand binary, and those who don't."],
    showJoke: async function() {
        const joke = this.JOKES[Math.floor(Math.random() * this.JOKES.length)];
        await this.typeMessage(`Joke: ${joke}`, true);
    },

    QUOTES: ["The best way to predict the future is to create it. - Peter Drucker", "The computer programmer is a creator of universes for which he alone is the lawgiver. - Joseph Weizenbaum"],
    showQuote: async function() {
        const quote = this.QUOTES[Math.floor(Math.random() * this.QUOTES.length)];
        await this.typeMessage(`Quote: "${quote}"`, true);
    },

    handleHack: async function(target) {
        if (!target) { await this.typeMessage('Usage: hack [target_ip/name]', false); return; }
        this.addLine(`\n[INIT] Commencing trace sequence on target: ${target}...`, true);
        await new Promise(r => setTimeout(r, 500));

        const bar1 = this.addLine(
            '<div class="progress-bar"><div id="hack-progress-1" class="progress-fill"></div></div>',
            false,
            true
        ).querySelector('#hack-progress-1');
        await this.animateProgressBar(bar1, 2000, "Phasing Trace Complete: 100%");

        this.addLine('[LOG] Initiating handshake protocol V7.1... Attempting zero-day exploit...', true);
        await new Promise(r => setTimeout(r, 800));

        const bar2 = this.addLine(
            '<div class="progress-bar"><div id="hack-progress-2" class="progress-fill"></div></div>',
            false,
            true
        ).querySelector('#hack-progress-2');
        await this.animateProgressBar(bar2, 3000, "Exploit Injection: 100% - ROOT ACCESS GRANTED", 'SUCCESS', 200);

        this.addLine(`\n[SUCCESS] Target ${target} compromised. Connection secured.`, true);
    },

    animateProgressBar: function(barElement, duration, finalMessage, finalStatus = 'COMPLETE', delay = 0) {
        return new Promise(resolve => {
            let width = 0;
            const step = duration / 100;
            const interval = setInterval(() => {
                width += 1;
                barElement.style.width = width + '%';
                if (width >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        this.addLine(`[${finalStatus}] ${finalMessage}`, true);
                        resolve();
                    }, delay);
                }
            }, step);
        });
    },

    setTheme: function(newTheme) {
        const validThemes = ['dark', 'blue', 'pink', 'neon', 'red', 'purple'];
        if (validThemes.includes(newTheme)) {
            document.body.setAttribute('data-theme', newTheme);
            this.typeMessage(`Theme successfully set to: ${newTheme.toUpperCase()}`, true);
        } else {
            this.typeMessage(`Error: Invalid theme. Options: ${validThemes.join(', ')}`, false);
        }
    },

    showProjects: function() {
        this.addLine('--- Cyber Terminal Ecosystem Projects ---', true);
        this.addLine('🧩 **CALCuS v3.0** - High-speed, secure, quantum calculation engine. [Link: Not-a-real-link.com/CALCuS]');
        this.addLine('🚀 **NEO-Matrix** - Next-gen data visualization and real-time environment monitor. [Link: Not-a-real-link.com/NEO]');
        this.addLine('Use these resources to enhance your operational efficiency.', true);
    },

    showTime: async function() {
        const date = new Date();
        const timeStr = date.toLocaleTimeString('en-US', { hour12: false });
        const dateStr = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        await this.typeMessage(`[CYBER-TIME] ${dateStr} - ${timeStr}`, true);
    },

    handleScan: async function() {
        this.addLine('[SCAN] Initializing AI Subsystem Scan... Please wait...', true);
        await new Promise(r => setTimeout(r, 1000));

        const bar = this.addLine(
            '<div class="progress-bar"><div id="scan-progress" class="progress-fill"></div></div>',
            false,
            true
        ).querySelector('#scan-progress');
        await this.animateProgressBar(bar, 4000, "Scan Complete: 100%", 'READY');

        this.addLine('[RESULT] 3 AI subsystems detected: Nexus, Sentinel, and Oracle. All operational.', true);
    },

    showAscii: function(text) {
        if (!text) {
            this.typeMessage('Usage: ascii [text]', false);
            return;
        }

        const asciiText = text.toUpperCase().split('').map(char => {
            if (char === 'A') return " /\\ \n/__\\";
            if (char === 'C') return " /-- \n|   \n \\--";
            return char;
        }).join('  ');

        this.addLine('');
        this.addLine('--------------------');
        this.addLine(asciiText, true);
        this.addLine('--------------------');
        this.addLine('');
    },

    showCredits: async function() {
        this.addLine('--- SYSTEM CREDITS ---', true);
        await this.typeMessage("Version: V2.5 'Full Cyber-AI Environment'", 10);
        await this.typeMessage("Core Development: Gemini AI (Design based on Mr John Dowe's V2.0 Specs)", 10);
        await this.typeMessage("\n(C) 2025 Cyber-Terminal Project. All rights reserved.", 10);
    },

    handleShutdown: async function() {
        this.addLine('\n[WARNING] Initiating system shutdown sequence...', true);
        await new Promise(r => setTimeout(r, 1000));
        await this.typeMessage('Goodbye, Operator. Session terminated.', true);

        this.input.disabled = true;
        this.cursor.style.display = 'none';
        this.fx.classList.add('matrix-hidden');
        document.body.style.filter = 'grayscale(100%) brightness(50%)';
        await new Promise(r => setTimeout(r, 2000));
        this.output.innerHTML = '<div class="line system-message" style="text-align: center; font-size: 2em; margin-top: 30vh;">CYBER TERMINAL OFFLINE</div>';
        this.scrollToBottom();
    },

    // --- VISUAL EFFECTS (Matrix Rain) ---
    matrixCanvas: null,
    matrixCtx: null,
    columns: 0,
    drops: [],
    matrixInterval: null,

    toggleMatrix: function() {
        if (this.matrixInterval) {
            clearInterval(this.matrixInterval);
            this.matrixInterval = null;
            this.fx.innerHTML = '';
            this.fx.classList.add('matrix-hidden');
            this.typeMessage('[FX] Binary Rain OFF.', true);
        } else {
            this.fx.classList.remove('matrix-hidden');
            this.initMatrix();
            this.typeMessage('[FX] Binary Rain ON.', true);
        }
    },

    initMatrix: function() {
        // Initialization logic here... (Same as before, using 'this')
        if (!this.matrixCanvas) {
            this.matrixCanvas = document.createElement('canvas');
            this.fx.appendChild(this.matrixCanvas);
            this.matrixCtx = this.matrixCanvas.getContext('2d');
        }

        const setupCanvas = () => {
            this.matrixCanvas.width = window.innerWidth;
            this.matrixCanvas.height = window.innerHeight;
            const fontSize = 16;
            this.columns = this.matrixCanvas.width / fontSize;
            this.drops = Array(Math.floor(this.columns)).fill(1);
        };
        setupCanvas();

        const drawMatrix = () => {
            this.matrixCtx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            this.matrixCtx.fillRect(0, 0, this.matrixCanvas.width, this.matrixCanvas.height);

            const mainColor = getComputedStyle(document.body).getPropertyValue('--color-main');
            this.matrixCtx.fillStyle = mainColor;
            this.matrixCtx.font = '16px monospace';

            const chars = "01";

            for(let i=0; i<this.drops.length; i++) {
                const text = chars[Math.floor(Math.random()*chars.length)];
                this.matrixCtx.fillText(text, i*16, this.drops[i]*16);

                if(this.drops[i]*16 > this.matrixCanvas.height && Math.random() > 0.975) {
                    this.drops[i] = 0;
                }
                this.drops[i]++;
            }
        };

        if (this.matrixInterval) clearInterval(this.matrixInterval);
        this.matrixInterval = setInterval(drawMatrix, 50);

        window.onresize = setupCanvas;
    },

    toggleMusic: function() {
        this.isMusicPlaying = !this.isMusicPlaying;
        this.typeMessage(this.isMusicPlaying ?
            '[MUSIC] Playing cyber beeps/BGM (Placeholder).' :
            '[MUSIC] Paused cyber beeps/BGM (Placeholder).', true);
    },

    // --- INITIALIZATION AND LISTENERS ---
    init: async function() {
        // 🌟 CRITICAL FIX: Assign elements here
        this.output = document.getElementById('output-window');
        this.input = document.getElementById('command-input');
        this.container = document.getElementById('terminal-container');
        this.cursor = document.getElementById('cursor');
        this.fx = document.getElementById('background-fx');

        // Mouse position for Glitch Spark FX
        this.container.addEventListener('mousemove', (e) => {
            const rect = this.container.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width * 100;
            const y = (e.clientY - rect.top) / rect.height * 100;
            this.container.style.setProperty('--mouse-x', `${x}%`);
            this.container.style.setProperty('--mouse-y', `${y}%`);
        });

        // Command Input Listener
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const command = this.input.value.trim();
                if (command) {
                    this.printCommand(command);
                    this.history.push(command);
                    this.historyIndex = this.history.length;
                    this.handleCommand(command);
                } else {
                    this.addLine(
                        `<span class="prompt-text">OPERATOR@CYBER-V2.5 &gt;</span> ${command}`,
                        false,
                        true
                    );
                    this.scrollToBottom();
                }
                this.input.value = '';
                e.preventDefault();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.navigateHistory(-1);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.navigateHistory(1);
            }
        });

        // Focus the input when clicking anywhere on the terminal
        this.container.addEventListener('click', () => {
            this.input.focus();
        });

        // Initial Boot Sequence
        await this.bootSequence();
    },

    navigateHistory: function(direction) {
        if (this.history.length === 0) return;

        this.historyIndex += direction;

        if (this.historyIndex < 0) {
            this.historyIndex = 0;
        } else if (this.historyIndex >= this.history.length) {
            this.historyIndex = this.history.length;
            this.input.value = '';
            return;
        }

        this.input.value = this.history[this.historyIndex];
        setTimeout(() => this.input.selectionStart = this.input.selectionEnd = this.input.value.length, 0);
    },

    BOOT_MESSAGES: [
        "Neural uplink established...", "System reboot complete.", "Synapses linked successfully.",
        "AI core online. Initializing modules...", "Welcome back, Operator.", "Executing startup script V2.5...",
    ],

    bootSequence: async function() {
        this.output.innerHTML = '';
        this.input.disabled = true;

        await this.typeMessage('CYBER TERMINAL V2.5 — Created by Mr John Dowe', true, 50);
        await this.typeMessage('----------------------------------------------------', false, 50);
        await new Promise(r => setTimeout(r, 500));

        const randMessage = this.BOOT_MESSAGES[Math.floor(Math.random() * this.BOOT_MESSAGES.length)];
        await this.typeMessage(`[STATUS] ${randMessage}`, true, 20);

        await new Promise(r => setTimeout(r, 1000));

        await this.typeMessage("Type 'help' to begin your session.", false, 20);
        this.addLine('');

        this.input.disabled = false;
        this.input.focus();
    }
};

// Start the terminal after the window loads (or DOMContentLoaded for faster startup)
window.addEventListener('load', () => {
    terminal.init();
});
// alert("Please Upvote & Comment if you like it! I tried my best😔 --MR JOHN DOWE!!")
// alert(" important--> help command doesn't working-------⟩ read console for help ")
console.log("OPERATOR@CYBER-V2.5 > help");
console.log("--- Available Commands ---");
console.log("help --- dosen't working -----");
console.log("about ");
console.log("calc [expr] ");
console.log("joke ");
console.log("quote ");
console.log("hack [target] ");
console.log("matrix ");
console.log("theme [dark/pink/neon/blue/red/purple] ");
console.log("clear");
console.log("projects ");
console.log("echo [text] ");
console.log("time");
console.log("scan ");
console.log("music ");
console.log("ascii [text]");
console.log("credits ");
console.log("shutdown ");

