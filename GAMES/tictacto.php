<!-- =============================================================================
   NOM DU FICHIER : games/tictactoe.php
   DESCRIPTION : Module Tic-Tac-Toe compact avec contrainte stricte des symboles dans les cases
   ============================================================================= -->
<div id="binokub-tictactoe-widget">
    <style>
        #binokub-tictactoe-widget {
            --bg-color: #050505;
            --cyan: #00f3ff;
            --magenta: #ff00ff;
            background-color: var(--bg-color);
            color: white;
            font-family: 'Orbitron', sans-serif, Arial, sans-serif;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 12px;
            width: 100%;
            box-sizing: border-box;
            position: relative;
        }

        #binokub-tictactoe-widget * { box-sizing: border-box; user-select: none; -webkit-tap-highlight-color: transparent; }

        #binokub-tictactoe-widget .game-wrapper-widget {
            width: 100%;
            max-width: 100%;
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 12px;
            position: relative;
        }

        #binokub-tictactoe-widget .screen { 
            width: 100%; 
            display: none; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
        }
        #binokub-tictactoe-widget .screen.active { display: flex; }

        #binokub-tictactoe-widget .grid-ttc {
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            grid-template-rows: repeat(3, 1fr);
            gap: 8px; 
            width: 100%; 
            max-width: 260px; 
            aspect-ratio: 1 / 1; 
            position: relative;
        }

        #binokub-tictactoe-widget .cell {
            background: rgba(17, 17, 17, 0.9); 
            border: 2px solid #222; 
            border-radius: 8px;
            display: flex; 
            justify-content: center; 
            align-items: center;
            cursor: pointer; 
            transition: all 0.2s; 
            position: relative;
            overflow: hidden; /* Empêche tout élément interne de déborder de la case */
        }
        #binokub-tictactoe-widget .cell:active { transform: scale(0.95); }
        #binokub-tictactoe-widget .cell.winning { animation: pulse-win-ttc 0.5s infinite alternate; z-index: 5; }

        @keyframes pulse-win-ttc {
            from { box-shadow: 0 0 8px white; filter: brightness(1.2); }
            to { box-shadow: 0 0 20px white; filter: brightness(1.6); }
        }

        #binokub-tictactoe-widget #particle-canvas-ttc {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 20;
        }

        #binokub-tictactoe-widget #win-line-ttc {
            position: absolute; background: white; border-radius: 10px;
            box-shadow: 0 0 15px white, 0 0 8px var(--cyan); z-index: 15; display: none;
            pointer-events: none;
        }

        #binokub-tictactoe-widget .menu-box { 
            background: rgba(15, 15, 15, 0.95); 
            padding: 15px; 
            border: 1px solid #333; 
            border-radius: 12px; 
            width: 100%; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.5); 
        }
        #binokub-tictactoe-widget .choice-label { 
            font-size: 9px; 
            color: #888; 
            margin-top: 8px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }
        
        #binokub-tictactoe-widget .toggle-group { display: flex; background: #222; border-radius: 6px; margin: 5px 0; padding: 3px; border: 1px solid #333; }
        #binokub-tictactoe-widget .toggle-btn { flex: 1; padding: 7px; font-size: 9px; border-radius: 5px; cursor: pointer; transition: 0.3s; text-align: center; color: #666; }
        #binokub-tictactoe-widget .toggle-btn.active { background: var(--cyan); color: black; font-weight: bold; box-shadow: 0 0 10px var(--cyan); }

        #binokub-tictactoe-widget button.main-btn {
            background: transparent; border: 2px solid var(--cyan); color: var(--cyan);
            padding: 10px; font-family: 'Orbitron', sans-serif; font-size: 11px; font-weight: bold;
            cursor: pointer; width: 100%; border-radius: 6px; margin-top: 10px;
            transition: 0.3s; text-shadow: 0 0 4px var(--cyan);
        }
        #binokub-tictactoe-widget button.main-btn:hover { background: var(--cyan); color: black; box-shadow: 0 0 20px var(--cyan); }

        #binokub-tictactoe-widget .shape-btn { width: 40px; height: 40px; border: 2px solid #333; display: flex; justify-content: center; align-items: center; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        #binokub-tictactoe-widget .shape-btn.selected { border-color: var(--cyan); background: rgba(0, 243, 255, 0.1); box-shadow: 0 0 10px rgba(0, 243, 255, 0.3); }

        #binokub-tictactoe-widget #status-text { margin-bottom: 8px; font-size: 11px; font-weight: bold; text-align: center; }

        #binokub-tictactoe-widget .score-board { display: flex; justify-content: space-around; width: 100%; margin: 8px 0; font-size: 9px; text-align: center; color: #888; }
        #binokub-tictactoe-widget .score-val { font-size: 13px; font-weight: bold; color: white; }

        #binokub-tictactoe-widget .shake { animation: shake-ttc 0.4s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake-ttc {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>

    <canvas id="particle-canvas-ttc"></canvas>

    <div class="game-wrapper-widget">
        <div id="menu-screen-ttc" class="screen active">
            <div class="menu-box">
                <div class="choice-label" style="margin-top:0;">Mode</div>
                <div class="toggle-group">
                    <div class="toggle-btn active" id="mode-classic-ttc" onclick="setModeTtc('classic')">CLASSIQUE</div>
                    <div class="toggle-btn" id="mode-custom-ttc" onclick="setModeTtc('custom')">SPECIAL</div>
                </div>

                <div id="shape-selection-ttc" style="display:none;">
                    <div class="choice-label">Symbole</div>
                    <div style="display:flex; gap:6px; justify-content:center; margin:5px 0;">
                        <div class="shape-btn selected" onclick="selectShapeTtc('triangle', this)"><svg width="20" height="20" viewBox="0 0 100 100"><path d="M50 15 L85 85 L15 85 Z" fill="none" stroke="#00f3ff" stroke-width="10"/></svg></div>
                        <div class="shape-btn" onclick="selectShapeTtc('square', this)"><svg width="20" height="20" viewBox="0 0 100 100"><rect x="15" y="15" width="70" height="70" fill="none" stroke="#00f3ff" stroke-width="10"/></svg></div>
                        <div class="shape-btn" onclick="selectShapeTtc('octagon', this)"><svg width="20" height="20" viewBox="0 0 100 100"><path d="M30 15 L70 15 L85 30 L85 70 L70 85 L30 85 L15 70 L15 30 Z" fill="none" stroke="#00f3ff" stroke-width="10"/></svg></div>
                    </div>
                </div>

                <div class="choice-label">Premier Joueur</div>
                <div class="toggle-group">
                    <div class="toggle-btn active" id="start-player-ttc" onclick="setStarterTtc('PLAYER')">MOI</div>
                    <div class="toggle-btn" id="start-tok-ttc" onclick="setStarterTtc('TOK')">TOK</div>
                </div>

                <button class="main-btn" onclick="initiateGameTtc()">DÉMARRER</button>
            </div>
        </div>

        <div id="game-screen-ttc" class="screen">
            <div id="status-text">---</div>
            <div class="grid-ttc" id="grid-ttc">
                <div id="win-line-ttc"></div>
                <div class="cell" data-index="0"></div><div class="cell" data-index="1"></div><div class="cell" data-index="2"></div>
                <div class="cell" data-index="3"></div><div class="cell" data-index="4"></div><div class="cell" data-index="5"></div>
                <div class="cell" data-index="6"></div><div class="cell" data-index="7"></div><div class="cell" data-index="8"></div>
            </div>
            <div class="stats" style="width:100%; text-align:center; margin-top:8px;">
                <div class="score-board">
                    <div>JOUEUR<br><span id="score-player-ttc" class="score-val">0</span></div>
                    <div>TOK<br><span id="score-ia-ttc" class="score-val">0</span></div>
                </div>
                <button class="main-btn" style="margin-top:5px; padding:8px;" onclick="resetBoardTtc()">REJOUER</button>
                <div onclick="quitToMenuTtc()" style="margin-top:6px; font-size:9px; color:#666; cursor:pointer; letter-spacing:1px;">MENU PRINCIPAL</div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const widget = document.getElementById('binokub-tictactoe-widget');
            let audioCtxTtc = null;
            function getAudioContext() {
                if (!audioCtxTtc) {
                    audioCtxTtc = new (window.AudioContext || window.webkitAudioContext)();
                }
                return audioCtxTtc;
            }

            function playSfxTtc(f1, f2, type='sine', dur=0.15) {
                try {
                    const ctx = getAudioContext();
                    if (ctx.state === 'suspended') ctx.resume();
                    const o = ctx.createOscillator(), g = ctx.createGain();
                    o.type = type; o.frequency.setValueAtTime(f1, ctx.currentTime);
                    o.frequency.exponentialRampToValueAtTime(f2, ctx.currentTime + dur);
                    g.gain.setValueAtTime(0.15, ctx.currentTime);
                    g.gain.linearRampToValueAtTime(0, ctx.currentTime + dur);
                    o.connect(g); g.connect(ctx.destination);
                    o.start(); o.stop(ctx.currentTime + dur);
                } catch(e) {}
            }

            const pCanvas = widget.querySelector('#particle-canvas-ttc');
            const pCtx = pCanvas.getContext('2d');
            let particlesTtc = [];

            function resizeCanvasTtc() {
                pCanvas.width = widget.clientWidth;
                pCanvas.height = widget.clientHeight;
            }
            window.addEventListener('resize', resizeCanvasTtc);
            setTimeout(resizeCanvasTtc, 100);

            class ParticleTtc {
                constructor(x, y, color) {
                    this.x = x; this.y = y; this.color = color;
                    this.size = Math.random() * 2 + 1;
                    this.speedX = (Math.random() - 0.5) * 6;
                    this.speedY = (Math.random() - 0.5) * 6;
                    this.life = 1.0;
                }
                update() {
                    this.x += this.speedX; this.y += this.speedY;
                    this.life -= 0.03;
                }
                draw() {
                    pCtx.fillStyle = this.color;
                    pCtx.globalAlpha = this.life;
                    pCtx.beginPath();
                    pCtx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    pCtx.fill();
                }
            }

            function spawnParticlesTtc(x, y, color) {
                const rect = widget.getBoundingClientRect();
                const relX = x - rect.left;
                const relY = y - rect.top;
                for(let i=0; i<10; i++) particlesTtc.push(new ParticleTtc(relX, relY, color));
            }

            function animateParticlesTtc() {
                pCtx.clearRect(0, 0, pCanvas.width, pCanvas.height);
                particlesTtc = particlesTtc.filter(p => p.life > 0);
                particlesTtc.forEach(p => { p.update(); p.draw(); });
                requestAnimationFrame(animateParticlesTtc);
            }
            animateParticlesTtc();

            let boardTtc = Array(9).fill(""), gameActiveTtc = false, currentPlayerTtc = "PLAYER", starterTtc = "PLAYER", gameModeTtc = "classic", myShapeTtc = "triangle", scoresTtc = { player: 0, ia: 0 };

            window.setModeTtc = function(m) {
                gameModeTtc = m;
                widget.querySelector('#mode-classic-ttc').classList.toggle('active', m === 'classic');
                widget.querySelector('#mode-custom-ttc').classList.toggle('active', m === 'custom');
                widget.querySelector('#shape-selection-ttc').style.display = (m === 'custom') ? 'block' : 'none';
                playSfxTtc(400, 600);
            };

            window.setStarterTtc = function(s) {
                starterTtc = s;
                widget.querySelector('#start-player-ttc').classList.toggle('active', s === 'PLAYER');
                widget.querySelector('#start-tok-ttc').classList.toggle('active', s === 'TOK');
                playSfxTtc(400, 600);
            };

            window.selectShapeTtc = function(s, el) {
                myShapeTtc = s;
                widget.querySelectorAll('.shape-btn').forEach(b => b.classList.remove('selected'));
                el.classList.add('selected');
                playSfxTtc(500, 700);
            };

            window.quitToMenuTtc = function() {
                widget.querySelector('#game-screen-ttc').classList.remove('active');
                widget.querySelector('#menu-screen-ttc').classList.add('active');
            };

            window.initiateGameTtc = function() {
                widget.querySelector('#menu-screen-ttc').classList.remove('active');
                widget.querySelector('#game-screen-ttc').classList.add('active');
                resizeCanvasTtc();
                resetBoardTtc();
            };

            window.resetBoardTtc = function() {
                boardTtc = Array(9).fill(""); gameActiveTtc = true; currentPlayerTtc = starterTtc;
                widget.querySelector('#win-line-ttc').style.display = "none";
                widget.querySelectorAll('.cell').forEach(c => { c.innerHTML = ""; c.classList.remove('winning'); });
                updateStatusTtc();
                if (currentPlayerTtc === "TOK") setTimeout(iaMoveTtc, 500);
            };

            function updateStatusTtc() {
                const txt = widget.querySelector('#status-text');
                if (!gameActiveTtc) return;
                txt.innerText = (currentPlayerTtc === "PLAYER") ? "VOTRE TOUR" : "TOK RÉFLÉCHIT...";
                txt.style.color = (currentPlayerTtc === "PLAYER") ? "var(--cyan)" : "var(--magenta)";
            }

            widget.querySelectorAll('.cell').forEach(cell => {
                cell.addEventListener('click', (e) => {
                    const idx = cell.dataset.index;
                    if (boardTtc[idx] !== "" || !gameActiveTtc || currentPlayerTtc !== "PLAYER") return;
                    
                    const rect = cell.getBoundingClientRect();
                    spawnParticlesTtc(rect.left + rect.width/2, rect.top + rect.height/2, "#00f3ff");
                    executeMoveTtc(idx, "PLAYER");
                });
            });

            function executeMoveTtc(idx, p) {
                boardTtc[idx] = p;
                renderCellTtc(idx, p);
                playSfxTtc(p === "PLAYER" ? 400 : 200, p === "PLAYER" ? 800 : 100, p === "PLAYER" ? 'sine' : 'sawtooth');
                
                const win = checkWinTtc(boardTtc, p);
                if (win) { handleEndTtc(p, win); return; }
                if (boardTtc.every(b => b !== "")) { handleEndTtc("DRAW"); return; }

                currentPlayerTtc = (p === "PLAYER") ? "TOK" : "PLAYER";
                updateStatusTtc();
                if (currentPlayerTtc === "TOK") setTimeout(iaMoveTtc, 500);
            }

            function renderCellTtc(idx, p) {
                const cell = widget.querySelector(`[data-index="${idx}"]`);
                const cvs = document.createElement('canvas');
                // Définition d'une résolution nette et forcée aux dimensions exactes de la case
                cvs.width = 60; 
                cvs.height = 60;
                cvs.style.width = '100%';
                cvs.style.height = '100%';
                cvs.style.display = 'block';

                const ctx = cvs.getContext('2d');
                const color = (p === "PLAYER") ? "#00f3ff" : "#ff00ff";
                ctx.strokeStyle = color; 
                ctx.lineWidth = 5; 
                ctx.lineCap = "round"; 
                ctx.shadowBlur = 4; 
                ctx.shadowColor = color;

                if (gameModeTtc === 'classic') {
                    if (p === "PLAYER") { 
                        // Cercle ajusté pour tenir parfaitement au centre (rayon 16)
                        ctx.beginPath(); ctx.arc(30, 30, 16, 0, Math.PI * 2); ctx.stroke(); 
                    } else { 
                        // Croix (X) redimensionnée pour ne pas dépasser des bords
                        ctx.beginPath(); 
                        ctx.moveTo(15, 15); ctx.lineTo(45, 45); 
                        ctx.moveTo(45, 15); ctx.lineTo(15, 45); 
                        ctx.stroke(); 
                    }
                } else {
                    if (p === "PLAYER") {
                        ctx.beginPath();
                        if (myShapeTtc === 'triangle') { ctx.moveTo(30, 10); ctx.lineTo(50, 50); ctx.lineTo(10, 50); ctx.closePath(); }
                        else if (myShapeTtc === 'square') { ctx.rect(15, 15, 30, 30); }
                        else { ctx.moveTo(22,10); ctx.lineTo(38,10); ctx.lineTo(50,22); ctx.lineTo(50,38); ctx.lineTo(38,50); ctx.lineTo(22,50); ctx.lineTo(10,38); ctx.lineTo(10,22); ctx.closePath(); }
                        ctx.stroke();
                    } else { 
                        ctx.beginPath(); 
                        ctx.moveTo(15, 15); ctx.lineTo(45, 45); 
                        ctx.moveTo(45, 15); ctx.lineTo(15, 45); 
                        ctx.stroke(); 
                    }
                }
                cell.appendChild(cvs);
            }

            function checkWinTtc(b, p) {
                const wins = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
                for (let c of wins) { if (c.every(i => b[i] === p)) return c; }
                return null;
            }

            function iaMoveTtc() {
                let best = -Infinity, move;
                for (let i=0; i<9; i++) {
                    if (boardTtc[i] === "") {
                        boardTtc[i] = "TOK"; let score = minimaxTtc(boardTtc, 0, false); boardTtc[i] = "";
                        if (score > best) { best = score; move = i; }
                    }
                }
                const cell = widget.querySelector(`[data-index="${move}"]`);
                const rect = cell.getBoundingClientRect();
                spawnParticlesTtc(rect.left + rect.width/2, rect.top + rect.height/2, "#ff00ff");
                executeMoveTtc(move, "TOK");
            }

            function minimaxTtc(b, d, isMax) {
                if (checkWinTtc(b, "TOK")) return 10; if (checkWinTtc(b, "PLAYER")) return -10; if (b.every(s => s !== "")) return 0;
                if (isMax) {
                    let best = -Infinity;
                    for (let i=0; i<9; i++) { if (b[i]==="") { b[i]="TOK"; best=Math.max(best, minimaxTtc(b, d+1, false)); b[i]=""; } }
                    return best;
                } else {
                    let best = Infinity;
                    for (let i=0; i<9; i++) { if (b[i]==="") { b[i]="PLAYER"; best=Math.min(best, minimaxTtc(b, d+1, true)); b[i]=""; } }
                    return best;
                }
            }

            function handleEndTtc(res, combo) {
                gameActiveTtc = false;
                const txt = widget.querySelector('#status-text');
                const wrap = widget.querySelector('.game-wrapper-widget');
                wrap.classList.add('shake'); setTimeout(() => wrap.classList.remove('shake'), 400);

                if (res === "DRAW") { txt.innerText = "MATCH NUL"; txt.style.color = "#888"; playSfxTtc(200, 100, 'sawtooth', 0.3); }
                else {
                    txt.innerText = (res === "PLAYER") ? "VICTOIRE !" : "TOK GAGNE";
                    if (res === "PLAYER") scoresTtc.player++; else scoresTtc.ia++;
                    widget.querySelector('#score-player-ttc').innerText = scoresTtc.player;
                    widget.querySelector('#score-ia-ttc').innerText = scoresTtc.ia;
                    highlightWinTtc(combo);
                    playSfxTtc(400, 1200, 'triangle', 0.5);
                }
            }

            function highlightWinTtc(combo) {
                const line = widget.querySelector('#win-line-ttc');
                const grid = widget.querySelector('#grid-ttc');
                const first = widget.querySelector(`[data-index="${combo[0]}"]`);
                const last = widget.querySelector(`[data-index="${combo[2]}"]`);
                combo.forEach(i => widget.querySelector(`[data-index="${i}"]`).classList.add('winning'));
                
                const r1 = first.getBoundingClientRect(), r2 = last.getBoundingClientRect(), gr = grid.getBoundingClientRect();
                const x1 = r1.left + r1.width/2 - gr.left, y1 = r1.top + r1.height/2 - gr.top;
                const x2 = r2.left + r2.width/2 - gr.left, y2 = r2.top + r2.height/2 - gr.top;
                
                line.style.width = Math.hypot(x2-x1, y2-y1) + "px";
                line.style.left = x1 + "px"; line.style.top = (y1-2) + "px";
                line.style.transform = `rotate(${Math.atan2(y2-y1, x2-x1)}rad)`;
                line.style.transformOrigin = "0 50%"; line.style.display = "block"; line.style.height = "4px";
            }
        })();
    </script>
</div>
