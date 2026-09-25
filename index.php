<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Hero;
use App\Monster;
use App\BattleEngine;

// Instanțiem eroii
$kratos = new Hero();
$monster = new Monster();

// Helper cu Closure Bind pentru a citi proprietățile protejate fara a edita src/
$getStats = function($char) {
    return \Closure::bind(function() {
        return [
            'strength' => $this->strength,
            'defence' => $this->defence
        ];
    }, $char, $char)();
};

$kratosStats = $getStats($kratos);
$monsterStats = $getStats($monster);

// Salvăm valorile inițiale de HP MĂSURATE ÎNAINTE DE SIMULARE
$initialHeroHp = $kratos->getHealth();
$initialMonsterHp = $monster->getHealth();

// Rulăm simularea în buffer pentru a extrage istoricul complet al luptei
ob_start();
$game = new BattleEngine($kratos, $monster);
$game->startBattle();
$rawLog = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Life is Hard, Work Soft - 3D Battle Arena</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Three.js + GLTFLoader + OrbitControls -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

    <style>
        :root {
            --bg-color: #060913;
            --kratos-color: #00b4d8;
            --kratos-glow: rgba(0, 180, 216, 0.4);
            --monster-color: #ff3344;
            --monster-glow: rgba(255, 51, 68, 0.4);
            --gold-color: #ffb703;
            --gold-glow: rgba(255, 183, 3, 0.5);
            --glass-bg: rgba(12, 18, 34, 0.85);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        * {
            box-sizing: border-box;
            user-select: none;
        }

        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: var(--bg-color);
            font-family: 'Outfit', sans-serif;
            color: #ffffff;
        }

        #webgl-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1;
        }

        /* --- LOADING OVERLAY --- */
        #loading-screen {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at center, #10172e 0%, #04060c 100%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.6s ease;
        }

        .loader-icon {
            font-size: 3.5rem;
            color: var(--gold-color);
            animation: pulseIcon 1.5s infinite ease-in-out;
            margin-bottom: 20px;
        }

        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.15); opacity: 1; filter: drop-shadow(0 0 20px var(--gold-color)); }
        }

        .loader-bar-bg {
            width: 280px;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .loader-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #ffb703, #fb8500);
            transition: width 0.3s ease;
        }

        .loader-text {
            margin-top: 15px;
            font-size: 1.1rem;
            letter-spacing: 1.5px;
            font-weight: 600;
            color: #d1d5db;
        }

        /* --- TOP HUD --- */
        #top-hud {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 94%;
            max-width: 1200px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
            pointer-events: none;
        }

        .char-hud-card {
            pointer-events: auto;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 16px 22px;
            width: 420px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .char-hud-card.kratos {
            border-left: 4px solid var(--kratos-color);
        }

        .char-hud-card.monster {
            border-right: 4px solid var(--monster-color);
        }

        .char-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .char-name {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kratos .char-name { color: var(--kratos-color); }
        .monster .char-name { color: var(--monster-color); }

        .hp-numeric {
            font-size: 0.95rem;
            font-weight: 700;
            color: #e2e8f0;
        }

        .hp-bar-outer {
            width: 100%;
            height: 16px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
        }

        .hp-bar-inner {
            height: 100%;
            width: 100%;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 7px;
        }

        .kratos .hp-bar-inner {
            background: linear-gradient(90deg, #00b4d8, #0077b6);
            box-shadow: 0 0 12px var(--kratos-glow);
        }

        .monster .hp-bar-inner {
            background: linear-gradient(90deg, #ff3344, #b91c1c);
            box-shadow: 0 0 12px var(--monster-glow);
        }

        .stat-badges-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 4px;
        }

        .stat-badge {
            font-size: 0.78rem;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .center-match-info {
            pointer-events: auto;
            text-align: center;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--gold-color);
            border-radius: 20px;
            padding: 12px 28px;
            box-shadow: 0 0 25px var(--gold-glow);
        }

        .turn-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--gold-color);
            font-weight: 700;
        }

        .turn-number {
            font-size: 1.6rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.1;
        }

        /* --- 3D FLOATING WORLD BARS --- */
        .world-hp-bar {
            position: absolute;
            transform: translate(-50%, -100%);
            pointer-events: none;
            z-index: 15;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: opacity 0.3s ease;
        }

        .world-hp-name {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-shadow: 0 2px 4px #000;
            margin-bottom: 3px;
        }

        .world-hp-track {
            width: 90px;
            height: 8px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            overflow: hidden;
        }

        .world-hp-fill {
            height: 100%;
            width: 100%;
            transition: width 0.3s ease;
        }

        /* --- FLOATING POPUPS --- */
        .pop-indicator {
            position: absolute;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            pointer-events: none;
            z-index: 30;
            transform: translate(-50%, -50%);
            animation: popUpFloat 1.2s cubic-bezier(0.18, 0.89, 0.32, 1.28) forwards;
        }

        .pop-damage {
            font-size: 2.8rem;
            color: #ff3344;
            text-shadow: 0 0 15px rgba(255, 51, 68, 0.8), 2px 2px 0 #000, -2px -2px 0 #000;
        }

        .pop-skill {
            font-size: 1.8rem;
            color: #ffb703;
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 16px;
            border-radius: 20px;
            border: 2px solid #ffb703;
            box-shadow: 0 0 20px rgba(255, 183, 3, 0.6);
            text-shadow: 0 2px 4px #000;
        }

        .pop-dodge {
            font-size: 2rem;
            color: #a855f7;
            text-shadow: 0 0 15px rgba(168, 85, 247, 0.8), 2px 2px 0 #000;
        }

        @keyframes popUpFloat {
            0% { opacity: 0; transform: translate(-50%, -20%) scale(0.4); }
            20% { opacity: 1; transform: translate(-50%, -60%) scale(1.25); }
            70% { opacity: 1; transform: translate(-50%, -100%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -140%) scale(0.9); }
        }

        /* --- BOTTOM CONTROLS BAR --- */
        #controls-bar {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 10px 20px;
            border-radius: 50px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.7);
        }

        .btn-ctrl {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 30px;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            outline: none;
        }

        .btn-ctrl:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-ctrl:active {
            transform: translateY(0);
        }

        .btn-ctrl.btn-primary {
            background: linear-gradient(135deg, #ffb703, #fb8500);
            color: #060913;
            border: none;
            box-shadow: 0 4px 20px var(--gold-glow);
        }

        .btn-ctrl.btn-primary:hover {
            background: linear-gradient(135deg, #ffe169, #ffb703);
            box-shadow: 0 6px 25px var(--gold-glow);
        }

        .btn-ctrl.active {
            background: rgba(0, 180, 216, 0.25);
            border-color: var(--kratos-color);
            color: var(--kratos-color);
        }

        /* --- LOG DRAWER --- */
        #log-drawer {
            position: absolute;
            right: 25px;
            bottom: 100px;
            width: 360px;
            max-height: 420px;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            z-index: 10;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.8);
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        #log-drawer.hidden {
            transform: translateX(400px);
            opacity: 0;
            pointer-events: none;
        }

        .log-header {
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.04);
            border-bottom: 1px solid var(--glass-border);
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--gold-color);
            letter-spacing: 0.5px;
        }

        .log-body {
            padding: 14px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 0.85rem;
            max-height: 350px;
        }

        .log-body::-webkit-scrollbar {
            width: 5px;
        }
        .log-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .log-entry {
            background: rgba(255, 255, 255, 0.03);
            border-left: 3px solid #64748b;
            padding: 8px 12px;
            border-radius: 6px;
            line-height: 1.4;
        }

        .log-entry.kratos-atk { border-left-color: var(--kratos-color); }
        .log-entry.monster-atk { border-left-color: var(--monster-color); }
        .log-entry.skill { border-left-color: var(--gold-color); background: rgba(255, 183, 3, 0.08); }
        .log-entry.dodge { border-left-color: #a855f7; }

        /* --- VICTORY MODAL --- */
        #victory-modal {
            position: fixed;
            inset: 0;
            background: rgba(4, 6, 12, 0.85);
            backdrop-filter: blur(12px);
            z-index: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
        }

        #victory-modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .victory-card {
            background: linear-gradient(145deg, #10172e, #0a0e1c);
            border: 2px solid var(--gold-color);
            box-shadow: 0 0 50px var(--gold-glow);
            border-radius: 24px;
            padding: 40px 50px;
            text-align: center;
            max-width: 480px;
            width: 90%;
            transform: scale(0.85);
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        #victory-modal.active .victory-card {
            transform: scale(1);
        }

        .victory-crown {
            font-size: 4rem;
            color: var(--gold-color);
            margin-bottom: 10px;
            animation: bounceCrown 2s infinite;
        }

        @keyframes bounceCrown {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .victory-title {
            font-size: 2.2rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
            color: #ffffff;
        }

        .victory-winner-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gold-color);
            margin-bottom: 25px;
        }

        .victory-stats {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-around;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
        }

        .stat-val {
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
        }
    </style>
</head>
<body>

<!-- WebGL Canvas Container -->
<div id="webgl-container"></div>

<!-- Loading Screen -->
<div id="loading-screen">
    <div class="loader-icon"><i class="fa-solid fa-swords"></i></div>
    <div class="loader-bar-bg">
        <div id="loader-progress" class="loader-bar-fill"></div>
    </div>
    <div class="loader-text">ÎNCĂRCARE ARENĂ 3D...</div>
</div>

<!-- Top HUD Header -->
<div id="top-hud">
    <!-- Hero (Kratos) Card -->
    <div class="char-hud-card kratos">
        <div class="char-header">
            <div class="char-name">
                <i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars($kratos->getName()); ?>
            </div>
            <div class="hp-numeric" id="kratos-hp-num"><?= $initialHeroHp; ?> / <?= $initialHeroHp; ?> HP</div>
        </div>
        <div class="hp-bar-outer">
            <div id="kratos-hp-bar" class="hp-bar-inner"></div>
        </div>
        <div class="stat-badges-grid">
            <div class="stat-badge"><i class="fa-solid fa-hand-fist" style="color: #ff3344;"></i> Putere: <?= $kratosStats['strength']; ?></div>
            <div class="stat-badge"><i class="fa-solid fa-shield" style="color: #3b82f6;"></i> Apărare: <?= $kratosStats['defence']; ?></div>
            <div class="stat-badge"><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Viteză: <?= $kratos->getSpeed(); ?></div>
            <div class="stat-badge"><i class="fa-solid fa-clover" style="color: #10b981;"></i> Noroc: <?= ($kratos->getLuck() * 100); ?>%</div>
        </div>
    </div>

    <!-- Match Info -->
    <div class="center-match-info">
        <div class="turn-title">LUPTĂ ARENĂ</div>
        <div class="turn-number" id="turn-display-num">TURA 1</div>
    </div>

    <!-- Monster Card -->
    <div class="char-hud-card monster">
        <div class="char-header">
            <div class="char-name">
                <i class="fa-solid fa-dragon"></i> <?= htmlspecialchars($monster->getName()); ?>
            </div>
            <div class="hp-numeric" id="monster-hp-num"><?= $initialMonsterHp; ?> / <?= $initialMonsterHp; ?> HP</div>
        </div>
        <div class="hp-bar-outer">
            <div id="monster-hp-bar" class="hp-bar-inner"></div>
        </div>
        <div class="stat-badges-grid">
            <div class="stat-badge"><i class="fa-solid fa-hand-fist" style="color: #ff3344;"></i> Putere: <?= $monsterStats['strength']; ?></div>
            <div class="stat-badge"><i class="fa-solid fa-shield" style="color: #3b82f6;"></i> Apărare: <?= $monsterStats['defence']; ?></div>
            <div class="stat-badge"><i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Viteză: <?= $monster->getSpeed(); ?></div>
            <div class="stat-badge"><i class="fa-solid fa-clover" style="color: #10b981;"></i> Noroc: <?= ($monster->getLuck() * 100); ?>%</div>
        </div>
    </div>
</div>

<!-- Floating World HP Bars -->
<div id="kratos-world-hp" class="world-hp-bar">
    <div class="world-hp-name" style="color: var(--kratos-color);"><?= htmlspecialchars($kratos->getName()); ?></div>
    <div class="world-hp-track">
        <div id="kratos-world-fill" class="world-hp-fill" style="background: var(--kratos-color);"></div>
    </div>
</div>

<div id="monster-world-hp" class="world-hp-bar">
    <div class="world-hp-name" style="color: var(--monster-color);"><?= htmlspecialchars($monster->getName()); ?></div>
    <div class="world-hp-track">
        <div id="monster-world-fill" class="world-hp-fill" style="background: var(--monster-color);"></div>
    </div>
</div>

<!-- Controls Bar -->
<div id="controls-bar">
    <button class="btn-ctrl btn-primary" id="btn-next-turn" onclick="handleNextTurn()">
        <i class="fa-solid fa-hand-fist"></i> LOVESTE
    </button>
    <button class="btn-ctrl" id="btn-auto-play" onclick="toggleAutoPlay()">
        <i class="fa-solid fa-play" id="icon-auto"></i> AUTO
    </button>
    <button class="btn-ctrl" id="btn-speed" onclick="cycleSpeed()">
        <i class="fa-solid fa-gauge-high"></i> <span id="text-speed">1x</span>
    </button>
    <button class="btn-ctrl" id="btn-sound" onclick="toggleSound()">
        <i class="fa-solid fa-volume-high" id="icon-sound"></i>
    </button>
    <button class="btn-ctrl" id="btn-log-toggle" onclick="toggleLogDrawer()">
        <i class="fa-solid fa-scroll"></i>
    </button>
    <button class="btn-ctrl" onclick="location.reload()">
        <i class="fa-solid fa-rotate-right"></i> MECI NOU
    </button>
</div>

<!-- Battle Log Drawer -->
<div id="log-drawer">
    <div class="log-header">
        <span><i class="fa-solid fa-scroll me-2"></i> JURNAL LUPTĂ</span>
        <i class="fa-solid fa-xmark" style="cursor: pointer;" onclick="toggleLogDrawer()"></i>
    </div>
    <div class="log-body" id="log-body-content">
        <div class="log-entry">⚔️ Simularea a fost inițializată. Apasă LOVESTE sau AUTO pentru a începe!</div>
    </div>
</div>

<!-- Victory Modal -->
<div id="victory-modal">
    <div class="victory-card">
        <div class="victory-crown"><i class="fa-solid fa-trophy"></i></div>
        <div class="victory-title">VICTORIE!</div>
        <div class="victory-winner-name" id="winner-name-text">KRATOS</div>
        <div class="victory-stats">
            <div class="stat-item">
                <span class="stat-label">Ture Totale</span>
                <span class="stat-val" id="vic-stat-turns">5</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">HP Rămas</span>
                <span class="stat-val" id="vic-stat-hp">45</span>
            </div>
        </div>
        <button class="btn-ctrl btn-primary" style="width: 100%; justify-content: center; padding: 14px 0;" onclick="location.reload()">
            <i class="fa-solid fa-rotate-right me-2"></i> MECI NOU
        </button>
    </div>
</div>

<script>
    // --- 1. WEB AUDIO SYNTHESIZER ---
    class SoundFX {
        constructor() {
            this.ctx = null;
            this.enabled = true;
        }

        init() {
            if (!this.ctx) {
                this.ctx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (this.ctx.state === 'suspended') {
                this.ctx.resume();
            }
        }

        playSlash() {
            if (!this.enabled) return;
            this.init();
            try {
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(850, this.ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(110, this.ctx.currentTime + 0.18);
                gain.gain.setValueAtTime(0.35, this.ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.18);
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                osc.stop(this.ctx.currentTime + 0.18);
            } catch(e){}
        }

        playBeastClaw() {
            if (!this.enabled) return;
            this.init();
            try {
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(240, this.ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(40, this.ctx.currentTime + 0.3);
                gain.gain.setValueAtTime(0.5, this.ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                osc.stop(this.ctx.currentTime + 0.3);
            } catch(e){}
        }

        playHit() {
            if (!this.enabled) return;
            this.init();
            try {
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(160, this.ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(30, this.ctx.currentTime + 0.22);
                gain.gain.setValueAtTime(0.4, this.ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.22);
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                osc.stop(this.ctx.currentTime + 0.22);
            } catch(e){}
        }

        playShield() {
            if (!this.enabled) return;
            this.init();
            try {
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(350, this.ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(1100, this.ctx.currentTime + 0.35);
                gain.gain.setValueAtTime(0.3, this.ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.35);
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                osc.stop(this.ctx.currentTime + 0.35);
            } catch(e){}
        }

        playDodge() {
            if (!this.enabled) return;
            this.init();
            try {
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(250, this.ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(550, this.ctx.currentTime + 0.15);
                gain.gain.setValueAtTime(0.2, this.ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.15);
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                osc.stop(this.ctx.currentTime + 0.15);
            } catch(e){}
        }

        playVictory() {
            if (!this.enabled) return;
            this.init();
            try {
                const notes = [523.25, 659.25, 783.99, 1046.50];
                notes.forEach((freq, idx) => {
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.value = freq;
                    const startTime = this.ctx.currentTime + idx * 0.14;
                    gain.gain.setValueAtTime(0.3, startTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, startTime + 0.45);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start(startTime);
                    osc.stop(startTime + 0.45);
                });
            } catch(e){}
        }
    }
    const sfx = new SoundFX();

    // --- 2. PARSARE LOG DIN PHP ---
    const rawConsoleLog = <?= json_encode($rawLog); ?>;

    function parseBattleLog(logText) {
        const lines = logText.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        const turns = [];
        let currentTurn = null;
        let winner = null;
        let draw = false;
        let firstAttacker = null;

        for (let line of lines) {
            if (line.includes('Primul care atacă este:')) {
                firstAttacker = line.includes('Kratos') ? 'Kratos' : 'Wild Monster';
            }
            else if (line.startsWith('--- TURA ')) {
                const turnNum = parseInt(line.replace(/[^0-9]/g, ''));
                currentTurn = {
                    number: turnNum,
                    attacker: null,
                    defender: null,
                    events: [],
                    rawLines: []
                };
                turns.push(currentTurn);
            }
            else if (currentTurn && line.includes(' atacă pe ')) {
                const parts = line.split(' atacă pe ');
                currentTurn.attacker = parts[0].trim();
                currentTurn.defender = parts[1].replace('...', '').trim();
                currentTurn.rawLines.push(line);
            }
            else if (currentTurn) {
                currentTurn.rawLines.push(line);
                if (line.includes('[SKILL ACTIVAT] Rapid Fire')) {
                    currentTurn.events.push({ type: 'skill', skill: 'Rapid Fire', text: line });
                }
                else if (line.includes('[SKILL ACTIVAT] Magic Armour')) {
                    currentTurn.events.push({ type: 'skill', skill: 'Magic Armour', text: line });
                }
                else if (line.includes('a avut noroc și a evitat atacul')) {
                    const charName = line.split(' a avut noroc')[0].trim();
                    currentTurn.events.push({ type: 'dodge', target: charName, text: line });
                }
                else if (line.includes('primește') && line.includes('daune!')) {
                    const parts = line.split(' primește ');
                    const charName = parts[0].trim();
                    const dmg = parseInt(parts[1].replace('daune!', '').trim());
                    currentTurn.events.push({ type: 'damage', target: charName, damage: dmg, text: line });
                }
                else if (line.includes('HP Rămas ->')) {
                    const parts = line.split('HP Rămas ->')[1].split(':');
                    const charName = parts[0].trim();
                    const hp = parseInt(parts[1].trim());
                    currentTurn.events.push({ type: 'hp_update', target: charName, hp: hp, text: line });
                }
            }

            if (line.includes('A CÂȘTIGAT LUPTA!')) {
                winner = line.split(' A CÂȘTIGAT LUPTA!')[0].trim();
            }
            if (line.includes('egalitate')) {
                draw = true;
            }
        }

        return { firstAttacker, turns, winner, draw };
    }

    const battleData = parseBattleLog(rawConsoleLog);
    const maxHeroHp = <?= $initialHeroHp; ?>;
    const maxMonsterHp = <?= $initialMonsterHp; ?>;
    let currentHeroHp = maxHeroHp;
    let currentMonsterHp = maxMonsterHp;

    // --- 3. THREE.JS 3D SCENĂ & LUMINI ---
    const container = document.getElementById('webgl-container');
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x060913);
    scene.fog = new THREE.FogExp2(0x060913, 0.025);

    const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 1.2, 9.5);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.35;
    container.appendChild(renderer.domElement);

    const controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.maxPolarAngle = Math.PI / 2 - 0.02;
    controls.minDistance = 4;
    controls.maxDistance = 16;
    controls.target.set(-0.4, -0.7, 0.7);

    // Lumini ambientale & direcționale frontale
    const ambientLight = new THREE.AmbientLight(0xffffff, 2.5);
    scene.add(ambientLight);

    const frontFillLight = new THREE.DirectionalLight(0xfffaed, 2.2);
    frontFillLight.position.set(0, 8, 10);
    scene.add(frontFillLight);

    const sunLight = new THREE.DirectionalLight(0xffeacc, 2.8);
    sunLight.position.set(6, 14, 8);
    sunLight.castShadow = true;
    sunLight.shadow.mapSize.width = 2048;
    sunLight.shadow.mapSize.height = 2048;
    sunLight.shadow.bias = -0.0001;
    scene.add(sunLight);

    // Rim Lights (Hero Blue, Monster Red)
    const heroRimLight = new THREE.PointLight(0x00b4d8, 5, 10);
    heroRimLight.position.set(-4, 3, -1);
    scene.add(heroRimLight);

    const monsterRimLight = new THREE.PointLight(0xff3344, 5, 10);
    monsterRimLight.position.set(4, 3, -1);
    scene.add(monsterRimLight);

    // Dynamic Impact Point Light
    const impactLight = new THREE.PointLight(0xffb703, 0, 10);
    impactLight.position.set(0, 0, 0);
    scene.add(impactLight);

    // --- 4. PARTICLES SYSTEM ---
    // Ambient dust particles
    const particleCount = 80;
    const particleGeo = new THREE.BufferGeometry();
    const particlePositions = new Float32Array(particleCount * 3);
    for (let i = 0; i < particleCount * 3; i += 3) {
        particlePositions[i] = (Math.random() - 0.5) * 18;
        particlePositions[i + 1] = Math.random() * 6 - 1;
        particlePositions[i + 2] = (Math.random() - 0.5) * 18;
    }
    particleGeo.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
    const particleMat = new THREE.PointsMaterial({
        color: 0xffb703,
        size: 0.09,
        transparent: true,
        opacity: 0.65,
        blending: THREE.AdditiveBlending
    });
    const ambientParticles = new THREE.Points(particleGeo, particleMat);
    scene.add(ambientParticles);

    // Dynamic Slash / Impact Burst System
    let sparkParticles = [];
    function createImpactSparks(x, y, z, color = 0xffb703, count = 30) {
        const pGeo = new THREE.BufferGeometry();
        const pPositions = new Float32Array(count * 3);
        const velocities = [];

        for (let i = 0; i < count; i++) {
            pPositions[i * 3] = x;
            pPositions[i * 3 + 1] = y;
            pPositions[i * 3 + 2] = z;
            velocities.push(new THREE.Vector3(
                (Math.random() - 0.5) * 0.2,
                Math.random() * 0.2 + 0.05,
                (Math.random() - 0.5) * 0.2
            ));
        }

        pGeo.setAttribute('position', new THREE.BufferAttribute(pPositions, 3));
        const pMat = new THREE.PointsMaterial({
            color: color,
            size: 0.15,
            transparent: true,
            opacity: 1,
            blending: THREE.AdditiveBlending
        });

        const points = new THREE.Points(pGeo, pMat);
        scene.add(points);
        sparkParticles.push({ points, velocities, life: 1.0 });

        // Point light flash
        impactLight.position.set(x, y, z);
        impactLight.color.setHex(color);
        impactLight.intensity = 8;
        setTimeout(() => { impactLight.intensity = 0; }, 200);
    }

    // --- 4.B CONSTRUIREA SĂBIEI 3D LUI KRATOS ȘI GHEARELE MONSTRULUI ---
    function createHeroSword() {
        const swordGroup = new THREE.Group();
        swordGroup.name = "heroSword";

        // 1. Lama Săbiei (Steel Blade with Glowing Energy Core)
        const bladeGeo = new THREE.BoxGeometry(0.08, 2.2, 0.02);
        const bladeMat = new THREE.MeshStandardMaterial({
            color: 0xe0f7fa,
            metalness: 0.95,
            roughness: 0.12,
            emissive: 0x00b4d8,
            emissiveIntensity: 0.4
        });
        const blade = new THREE.Mesh(bladeGeo, bladeMat);
        blade.position.y = 1.1;
        blade.castShadow = true;
        swordGroup.add(blade);

        // Vârful Săbiei (Sharp Tip)
        const tipGeo = new THREE.ConeGeometry(0.056, 0.4, 4);
        const tip = new THREE.Mesh(tipGeo, bladeMat);
        tip.position.y = 2.2 + 0.2;
        tip.rotation.y = Math.PI / 4;
        swordGroup.add(tip);

        // Linie de energie în centrul lamei (Glowing Rune Core)
        const runeGeo = new THREE.BoxGeometry(0.02, 1.9, 0.025);
        const runeMat = new THREE.MeshBasicMaterial({ color: 0x00b4d8 });
        const rune = new THREE.Mesh(runeGeo, runeMat);
        rune.position.y = 1.05;
        swordGroup.add(rune);

        // 2. Garda Aurie (Royal Crossguard)
        const guardGeo = new THREE.BoxGeometry(0.6, 0.1, 0.1);
        const goldMat = new THREE.MeshStandardMaterial({
            color: 0xffb703,
            metalness: 0.88,
            roughness: 0.18,
            emissive: 0xd4a373,
            emissiveIntensity: 0.3
        });
        const guard = new THREE.Mesh(guardGeo, goldMat);
        guard.position.y = 0;
        guard.castShadow = true;
        swordGroup.add(guard);

        // Gemă de energie în gardă
        const gemGeo = new THREE.OctahedronGeometry(0.08);
        const gemMat = new THREE.MeshStandardMaterial({ color: 0x00b4d8, emissive: 0x00b4d8, emissiveIntensity: 1.0 });
        const gem = new THREE.Mesh(gemGeo, gemMat);
        gem.position.set(0, 0, 0.05);
        swordGroup.add(gem);

        // 3. Mâner îmbrăcat în piele (Grip)
        const gripGeo = new THREE.CylinderGeometry(0.04, 0.04, 0.45, 12);
        const gripMat = new THREE.MeshStandardMaterial({ color: 0x1f1410, roughness: 0.85 });
        const grip = new THREE.Mesh(gripGeo, gripMat);
        grip.position.y = -0.225;
        swordGroup.add(grip);

        // 4. Măciuluc (Pommel)
        const pommelGeo = new THREE.OctahedronGeometry(0.09);
        const pommel = new THREE.Mesh(pommelGeo, goldMat);
        pommel.position.y = -0.45 - 0.05;
        swordGroup.add(pommel);

        swordGroup.scale.set(1.2, 1.2, 1.2);
        return swordGroup;
    }

    // Efect de Arc de Energie la atacul cu sabia (Cyber Blade Trail)
    function spawnSwordSlashArc(x, y, z) {
        const arcGeo = new THREE.RingGeometry(0.9, 1.6, 32, 1, 0, Math.PI * 0.8);
        const arcMat = new THREE.MeshBasicMaterial({
            color: 0x00b4d8,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.95,
            blending: THREE.AdditiveBlending
        });
        const arcMesh = new THREE.Mesh(arcGeo, arcMat);
        arcMesh.position.set(x, y + 0.9, z + 0.3);
        arcMesh.rotation.x = Math.PI / 3.5;
        arcMesh.rotation.y = Math.PI / 3;
        scene.add(arcMesh);

        let op = 0.95;
        const fade = () => {
            op -= 0.08 * animSpeedModifier;
            arcMesh.material.opacity = Math.max(0, op);
            arcMesh.scale.addScalar(0.06 * animSpeedModifier);
            if (op > 0) requestAnimationFrame(fade);
            else {
                scene.remove(arcMesh);
                arcMesh.geometry.dispose();
                arcMesh.material.dispose();
            }
        };
        fade();
    }

    // Efect de Gheare Roșii la atacul Monstrului (Red Monster Claw Strike)
    function spawnMonsterClawSlash(x, y, z) {
        const clawGroup = new THREE.Group();
        const clawMat = new THREE.MeshBasicMaterial({
            color: 0xff1133,
            transparent: true,
            opacity: 1,
            blending: THREE.AdditiveBlending
        });

        // 3 lovituri paralele de gheară
        for (let i = -1; i <= 1; i++) {
            const lineGeo = new THREE.BoxGeometry(0.05, 1.8, 0.02);
            const line = new THREE.Mesh(lineGeo, clawMat);
            line.position.x = i * 0.25;
            line.rotation.z = -Math.PI / 3;
            clawGroup.add(line);
        }

        clawGroup.position.set(x, y + 0.9, z + 0.3);
        scene.add(clawGroup);

        let op = 1.0;
        const anim = () => {
            op -= 0.09 * animSpeedModifier;
            clawMat.opacity = Math.max(0, op);
            clawGroup.position.x -= 0.04 * animSpeedModifier;
            clawGroup.position.y -= 0.03 * animSpeedModifier;
            if (op > 0) requestAnimationFrame(anim);
            else {
                scene.remove(clawGroup);
                clawGroup.traverse((c) => {
                    if (c.geometry) c.geometry.dispose();
                    if (c.material) c.material.dispose();
                });
            }
        };
        anim();
    }

    // Shield Mesh for Magic Armour
    const shieldGeo = new THREE.SphereGeometry(1.4, 32, 32);
    const shieldMat = new THREE.MeshStandardMaterial({
        color: 0x00b4d8,
        transparent: true,
        opacity: 0,
        wireframe: true,
        emissive: 0x00b4d8,
        emissiveIntensity: 0.8
    });
    const shieldMesh = new THREE.Mesh(shieldGeo, shieldMat);
    scene.add(shieldMesh);

    // --- 5. LOADING & ALIGNING GLTF MODELS ON FOREST GROUND ---
    const loader = new THREE.GLTFLoader();
    let heroModel, monsterModel, arenaModel, heroSwordMesh, heroSwordPivot;

    // POZIȚIILE PE ALEEA DIN PĂDURE (PE CELE 2 PUNCTE ALBASTRE)
    let heroBasePos = new THREE.Vector3(-0.9, -1.65, -0.2);
    let monsterBasePos = new THREE.Vector3(0.1, -1.65, 1.6);
    let loadedModelsCount = 0;

    function updateLoaderProgress(pct) {
        document.getElementById('loader-progress').style.width = pct + '%';
        if (pct >= 100) {
            setTimeout(() => {
                const screen = document.getElementById('loading-screen');
                screen.style.opacity = '0';
                setTimeout(() => screen.style.display = 'none', 600);
            }, 300);
        }
    }

    function checkLoaded() {
        loadedModelsCount++;
        if (loadedModelsCount >= 3) {
            setupArenaAndAlignCharacters();
        }
        updateLoaderProgress(Math.min(100, Math.round((loadedModelsCount / 3) * 100)));
    }

    function setupArenaAndAlignCharacters() {
        if (!arenaModel || !heroModel || !monsterModel) return;

        // 1. Scalăm arena la o lățime potrivită (~14.0 unități)
        const arenaBoxInit = new THREE.Box3().setFromObject(arenaModel);
        const arenaSizeInit = arenaBoxInit.getSize(new THREE.Vector3());

        if (arenaSizeInit.x > 0) {
            const scale = 14.0 / Math.max(arenaSizeInit.x, arenaSizeInit.z);
            arenaModel.scale.set(scale, scale, scale);
        }

        // Centram centrul geometric al arenei exact în originea (0, 0, 0)
        const arenaBoxCentered = new THREE.Box3().setFromObject(arenaModel);
        const arenaCenter = arenaBoxCentered.getCenter(new THREE.Vector3());

        arenaModel.position.x = -arenaCenter.x;
        arenaModel.position.y = -arenaCenter.y;
        arenaModel.position.z = -arenaCenter.z;

        // 2. Poziționăm Kratos pe prima pata albastră (stânga-sus pe cărare)
        const heroBoxInit = new THREE.Box3().setFromObject(heroModel);
        const heroSizeInit = heroBoxInit.getSize(new THREE.Vector3());
        if (heroSizeInit.y > 0) {
            const scale = 2.4 / heroSizeInit.y;
            heroModel.scale.set(scale, scale, scale);
        }

        heroBasePos.set(-0.9, -1.65, -0.2);
        heroModel.position.copy(heroBasePos);
        heroModel.rotation.y = Math.PI / 3;
        heroModel.rotation.z = -0.05; // postura pregătită de luptă
        shieldMesh.position.copy(heroBasePos);

        // --- PIVOT PENTRU MÂNA DREAPTĂ A LUI KRATOS CARE ȚINE SABIA ---
        heroSwordPivot = new THREE.Group();
        heroSwordPivot.position.set(0.4, 0.85, 0.1); // umărul/mâna dreaptă
        heroModel.add(heroSwordPivot);

        heroSwordMesh = createHeroSword();
        heroSwordPivot.add(heroSwordMesh);
        heroSwordMesh.position.set(0, 0, 0);
        heroSwordMesh.rotation.set(Math.PI / 4, 0, -Math.PI / 4); // Sabia ridicată în poziție de atac!

        // 3. Poziționăm Monstrul pe a doua pata albastră (dreapta-jos pe cărare)
        const monsterBoxInit = new THREE.Box3().setFromObject(monsterModel);
        const monsterSizeInit = monsterBoxInit.getSize(new THREE.Vector3());
        if (monsterSizeInit.y > 0) {
            const scale = 2.6 / monsterSizeInit.y;
            monsterModel.scale.set(scale, scale, scale);
        }

        monsterBasePos.set(0.1, -1.65, 1.6);
        monsterModel.position.copy(monsterBasePos);
        monsterModel.rotation.y = -Math.PI * 0.75;
        monsterModel.rotation.x = 0.15; // postura aplecată fioroasă de fiare

        // Setăm camera să privească spre centrul luptei de pe cărare
        controls.target.set(-0.4, -0.7, 0.7);
        camera.position.set(0, 1.2, 9.5);
        controls.update();
    }

    // A) Încarcă Arena
    loader.load('assets/arena.glb', (gltf) => {
        arenaModel = gltf.scene;
        arenaModel.traverse((child) => {
            if (child.isMesh) {
                child.receiveShadow = true;
                child.castShadow = true;
                if (child.material) {
                    child.material.side = THREE.DoubleSide;
                }
            }
        });
        scene.add(arenaModel);
        checkLoaded();
    }, undefined, () => checkLoaded());

    // B) Încarcă Warrior (Hero Kratos)
    loader.load('assets/warrior.glb', (gltf) => {
        heroModel = gltf.scene;
        heroModel.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = true;
                child.receiveShadow = true;
            }
        });
        scene.add(heroModel);
        checkLoaded();
    }, undefined, () => checkLoaded());

    // C) Încarcă Monster Model
    loader.load('assets/monster.glb', (gltf) => {
        monsterModel = gltf.scene;
        monsterModel.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = true;
                child.receiveShadow = true;
            }
        });
        scene.add(monsterModel);
        checkLoaded();
    }, undefined, () => checkLoaded());

    // --- 6. PROCEDURAL ANIMATION CONTROLLER ---
    let animSpeedModifier = 1.0;
    let isAnimatingTurn = false;
    let currentTurnIndex = 0;
    let isAutoPlaying = false;
    let autoPlayTimer = null;

    function getModelByName(name) {
        if (name === 'Kratos') return heroModel;
        return monsterModel;
    }

    function getBasePosByName(name) {
        if (name === 'Kratos') return heroBasePos;
        return monsterBasePos;
    }

    // Flash Red / White on Hit
    function flashModelHit(model) {
        if (!model) return;
        model.traverse((child) => {
            if (child.isMesh && child.material) {
                const origEmissive = child.material.emissive ? child.material.emissive.getHex() : 0x000000;
                child.material.emissive = new THREE.Color(0xff2222);
                child.material.emissiveIntensity = 0.8;
                setTimeout(() => {
                    if (child.material) {
                        child.material.emissive.setHex(origEmissive);
                        child.material.emissiveIntensity = 0;
                    }
                }, 180 / animSpeedModifier);
            }
        });
    }

    // Camera Shake
    let cameraShakeIntensity = 0;
    function triggerCameraShake(intensity = 0.15) {
        cameraShakeIntensity = intensity;
    }

    // --- POPUP INDICATORS (3D -> 2D) ---
    function spawnPopup(text, position3D, className, colorCss) {
        const div = document.createElement('div');
        div.className = `pop-indicator ${className}`;
        div.innerText = text;
        if (colorCss) div.style.color = colorCss;

        document.body.appendChild(div);

        const updatePos = () => {
            const vector = position3D.clone();
            vector.project(camera);
            const x = (vector.x * 0.5 + 0.5) * window.innerWidth;
            const y = (-(vector.y * 0.5) + 0.5) * window.innerHeight;
            div.style.left = `${x}px`;
            div.style.top = `${y}px`;
        };

        updatePos();
        setTimeout(() => div.remove(), 1200 / animSpeedModifier);
    }

    // --- TURN ANIMATION PIPELINE ---
    async function playTurnAnimation(turn) {
        isAnimatingTurn = true;
        document.getElementById('turn-display-num').innerText = `TURA ${turn.number}`;

        const attackerModel = getModelByName(turn.attacker);
        const defenderModel = getModelByName(turn.defender);
        const attackerStartPos = getBasePosByName(turn.attacker).clone();
        const defenderStartPos = getBasePosByName(turn.defender).clone();

        // 1. Log line highlight
        addLogEntry(`--- ⚔️ TURA ${turn.number}: ${turn.attacker} atacă ---`, turn.attacker === 'Kratos' ? 'kratos-atk' : 'monster-atk');

        // Target positions for charge
        const attackTargetPos = defenderStartPos.clone().lerp(attackerStartPos, 0.45);

        // A) Attacker Lunge Forward
        await animateMove(attackerModel, attackerStartPos, attackTargetPos, 280 / animSpeedModifier);

        // B) SPECIFIC ATTACK ANIMATIONS (SABIA LUI KRATOS vs GHEARELE MONSTRULUI)
        if (turn.attacker === 'Kratos') {
            // Eroul taie puternic cu sabia în arc
            sfx.playSlash();
            if (heroSwordPivot) {
                heroSwordPivot.rotation.x = Math.PI / 1.5;
                heroSwordPivot.rotation.z = -Math.PI / 1.2;
            }
            spawnSwordSlashArc(attackTargetPos.x, attackTargetPos.y, attackTargetPos.z);
        } else {
            // Monstrul se apleacă și lovește fioros cu ambele gheare
            sfx.playBeastClaw();
            attackerModel.rotation.x = 0.55; // aplecare violentă în față
            spawnMonsterClawSlash(defenderStartPos.x, defenderStartPos.y, defenderStartPos.z);
        }

        // C) Check Events in turn
        const hasRapidFire = turn.events.some(e => e.skill === 'Rapid Fire');
        const hasMagicArmour = turn.events.some(e => e.skill === 'Magic Armour');
        const dodgeEvent = turn.events.find(e => e.type === 'dodge');

        if (hasMagicArmour) {
            addLogEntry(`[SKILL] Magic Armour activat! Daune înjumătățite.`, 'skill');
            sfx.playShield();

            // Trigger shield opacity
            shieldMesh.position.copy(heroModel ? heroModel.position : heroBasePos);
            shieldMat.opacity = 0.85;
            spawnPopup(`MAGIC ARMOUR! 🛡️`, heroBasePos.clone().add(new THREE.Vector3(0, 1.2, 0)), 'pop-skill');
            setTimeout(() => { shieldMat.opacity = 0; }, 600 / animSpeedModifier);
        }

        if (dodgeEvent) {
            addLogEntry(`${turn.defender} a evitat atacul cu noroc!`, 'dodge');
            sfx.playDodge();
            spawnPopup(`DODGE! 🍀`, defenderStartPos.clone().add(new THREE.Vector3(0, 1.2, 0)), 'pop-dodge');
            await animateDodge(defenderModel, defenderStartPos, 320 / animSpeedModifier);
        } else {
            // Impact
            sfx.playHit();
            flashModelHit(defenderModel);
            triggerCameraShake(0.25);
            createImpactSparks(attackTargetPos.x, attackTargetPos.y + 0.8, attackTargetPos.z, turn.attacker === 'Kratos' ? 0xffb703 : 0xff3344, 35);

            const dmgEvent1 = turn.events.find(e => e.type === 'damage');
            if (dmgEvent1) {
                spawnPopup(`-${dmgEvent1.damage}`, defenderStartPos.clone().add(new THREE.Vector3(0, 1.2, 0)), 'pop-damage');
                addLogEntry(`${turn.defender} primește ${dmgEvent1.damage} daune!`, turn.defender === 'Kratos' ? 'kratos-atk' : 'monster-atk');
            }

            const hpEvent1 = turn.events.find(e => e.type === 'hp_update');
            if (hpEvent1) {
                updateCharHp(hpEvent1.target, hpEvent1.hp);
            }
        }

        // D) Rapid Fire Second Sword Combo Hit (if active)
        if (hasRapidFire) {
            addLogEntry(`[SKILL] Rapid Fire! Kratos execută o a doua tăietură cu sabia!`, 'skill');
            spawnPopup(`RAPID FIRE! ⚡`, attackerStartPos.clone().add(new THREE.Vector3(0, 1.5, 0)), 'pop-skill');
            await new Promise(r => setTimeout(r, 200 / animSpeedModifier));

            sfx.playSlash();
            if (heroSwordPivot) {
                heroSwordPivot.rotation.x = -Math.PI / 3;
                heroSwordPivot.rotation.z = Math.PI / 1.5;
            }
            spawnSwordSlashArc(attackTargetPos.x + 0.2, attackTargetPos.y, attackTargetPos.z);
            createImpactSparks(attackTargetPos.x, attackTargetPos.y + 0.8, attackTargetPos.z, 0xffb703, 45);

            const dmgEvents = turn.events.filter(e => e.type === 'damage');
            if (dmgEvents.length > 1) {
                const secondDmg = dmgEvents[1];
                sfx.playHit();
                flashModelHit(defenderModel);
                spawnPopup(`-${secondDmg.damage}`, defenderStartPos.clone().add(new THREE.Vector3(0, 1.2, 0)), 'pop-damage');
                addLogEntry(`${turn.defender} primește ${secondDmg.damage} daune suplimentare!`, 'kratos-atk');
            }

            const hpEvents = turn.events.filter(e => e.type === 'hp_update');
            if (hpEvents.length > 1) {
                updateCharHp(hpEvents[1].target, hpEvents[1].hp);
            }
        }

        // E) Reset posture & return back to original position
        if (heroSwordPivot) {
            heroSwordPivot.rotation.set(0, 0, 0);
        }
        monsterModel.rotation.x = 0.15;

        await animateMove(attackerModel, attackTargetPos, attackerStartPos, 300 / animSpeedModifier);
        isAnimatingTurn = false;
    }

    // Lerp Move Helper
    function animateMove(model, startPos, targetPos, duration) {
        return new Promise((resolve) => {
            if (!model) return resolve();
            let elapsed = 0;
            const step = () => {
                elapsed += 16 * animSpeedModifier;
                const progress = Math.min(1, elapsed / duration);
                const ease = progress < 0.5 ? 2 * progress * progress : -1 + (4 - 2 * progress) * progress;
                model.position.lerpVectors(startPos, targetPos, ease);

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    model.position.copy(targetPos);
                    resolve();
                }
            };
            step();
        });
    }

    // Dodge Move Helper
    function animateDodge(model, basePos, duration) {
        return new Promise((resolve) => {
            if (!model) return resolve();
            let elapsed = 0;
            const direction = basePos.x < 0 ? -1 : 1;
            const dodgePos = basePos.clone().add(new THREE.Vector3(direction * 0.8, 0, -0.5));

            const step = () => {
                elapsed += 16 * animSpeedModifier;
                const progress = Math.min(1, elapsed / duration);
                if (progress <= 0.5) {
                    model.position.lerpVectors(basePos, dodgePos, progress * 2);
                } else {
                    model.position.lerpVectors(dodgePos, basePos, (progress - 0.5) * 2);
                }

                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    model.position.copy(basePos);
                    resolve();
                }
            };
            step();
        });
    }

    // HP Bar Updates
    function updateCharHp(charName, newHp) {
        if (charName === 'Kratos') {
            currentHeroHp = newHp;
            const pct = Math.max(0, (newHp / maxHeroHp) * 100);
            document.getElementById('kratos-hp-bar').style.width = pct + '%';
            document.getElementById('kratos-world-fill').style.width = pct + '%';
            document.getElementById('kratos-hp-num').innerText = `${newHp} / ${maxHeroHp} HP`;
        } else {
            currentMonsterHp = newHp;
            const pct = Math.max(0, (newHp / maxMonsterHp) * 100);
            document.getElementById('monster-hp-bar').style.width = pct + '%';
            document.getElementById('monster-world-fill').style.width = pct + '%';
            document.getElementById('monster-hp-num').innerText = `${newHp} / ${maxMonsterHp} HP`;
        }
    }

    function addLogEntry(text, type = '') {
        const body = document.getElementById('log-body-content');
        const div = document.createElement('div');
        div.className = `log-entry ${type}`;
        div.innerText = text;
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }

    // --- 7. BATTLE CONTROLLER (NEXT TURN, AUTO, WIN) ---
    async function handleNextTurn() {
        if (isAnimatingTurn) return;
        sfx.init();

        if (currentTurnIndex >= battleData.turns.length) {
            declareWinner();
            return;
        }

        const turn = battleData.turns[currentTurnIndex];
        currentTurnIndex++;

        await playTurnAnimation(turn);

        if (currentTurnIndex >= battleData.turns.length) {
            setTimeout(declareWinner, 500);
        }
    }

    function toggleAutoPlay() {
        sfx.init();
        isAutoPlaying = !isAutoPlaying;
        const btn = document.getElementById('btn-auto-play');
        const icon = document.getElementById('icon-auto');

        if (isAutoPlaying) {
            btn.classList.add('active');
            icon.className = 'fa-solid fa-pause';
            runAutoPlayLoop();
        } else {
            btn.classList.remove('active');
            icon.className = 'fa-solid fa-play';
            if (autoPlayTimer) clearTimeout(autoPlayTimer);
        }
    }

    async function runAutoPlayLoop() {
        if (!isAutoPlaying) return;
        if (currentTurnIndex >= battleData.turns.length) {
            toggleAutoPlay();
            declareWinner();
            return;
        }

        if (!isAnimatingTurn) {
            await handleNextTurn();
        }

        if (isAutoPlaying) {
            autoPlayTimer = setTimeout(runAutoPlayLoop, 1200 / animSpeedModifier);
        }
    }

    function cycleSpeed() {
        if (animSpeedModifier === 1.0) animSpeedModifier = 1.5;
        else if (animSpeedModifier === 1.5) animSpeedModifier = 2.5;
        else animSpeedModifier = 1.0;

        document.getElementById('text-speed').innerText = `${animSpeedModifier}x`;
    }

    function toggleSound() {
        sfx.enabled = !sfx.enabled;
        const icon = document.getElementById('icon-sound');
        icon.className = sfx.enabled ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark';
    }

    function toggleLogDrawer() {
        document.getElementById('log-drawer').classList.toggle('hidden');
    }

    function declareWinner() {
        if (isAutoPlaying) toggleAutoPlay();
        sfx.playVictory();

        const modal = document.getElementById('victory-modal');
        const winnerText = document.getElementById('winner-name-text');
        const statTurns = document.getElementById('vic-stat-turns');
        const statHp = document.getElementById('vic-stat-hp');

        if (battleData.winner) {
            winnerText.innerText = battleData.winner.toUpperCase();
            statTurns.innerText = battleData.turns.length;
            statHp.innerText = battleData.winner === 'Kratos' ? `${currentHeroHp} HP` : `${currentMonsterHp} HP`;
        } else {
            winnerText.innerText = 'EGALITATE (DRAW)';
            statTurns.innerText = '15';
            statHp.innerText = '-';
        }

        modal.classList.add('active');

        // Winner pose & camera spin animation
        if (battleData.winner) {
            const winnerModel = getModelByName(battleData.winner);
            const loserModel = getModelByName(battleData.winner === 'Kratos' ? 'Wild Monster' : 'Kratos');

            if (winnerModel) {
                winnerModel.position.y = heroBasePos.y;
                createImpactSparks(winnerModel.position.x, winnerModel.position.y + 1, winnerModel.position.z, 0xffb703, 60);
            }
            if (loserModel) {
                loserModel.rotation.z = Math.PI / 2;
                loserModel.position.y = heroBasePos.y - 0.2;
            }
        }
    }

    // --- 8. GAME RENDER LOOP ---
    const clock = new THREE.Clock();

    function updateWorldHpPositions() {
        if (heroModel) {
            const pos = heroModel.position.clone().add(new THREE.Vector3(0, 2.2, 0));
            pos.project(camera);
            const x = (pos.x * 0.5 + 0.5) * window.innerWidth;
            const y = (-(pos.y * 0.5) + 0.5) * window.innerHeight;
            const el = document.getElementById('kratos-world-hp');
            el.style.left = `${x}px`;
            el.style.top = `${y}px`;
        }

        if (monsterModel) {
            const pos = monsterModel.position.clone().add(new THREE.Vector3(0, 2.4, 0));
            pos.project(camera);
            const x = (pos.x * 0.5 + 0.5) * window.innerWidth;
            const y = (-(pos.y * 0.5) + 0.5) * window.innerHeight;
            const el = document.getElementById('monster-world-hp');
            el.style.left = `${x}px`;
            el.style.top = `${y}px`;
        }
    }

    function animate() {
        requestAnimationFrame(animate);

        const time = clock.getElapsedTime();

        // Idle Motion (Breathing & Sword Guard Sway)
        if (heroModel && !isAnimatingTurn) {
            heroModel.position.y = heroBasePos.y + Math.sin(time * 2.5) * 0.03;
            if (heroSwordPivot) {
                heroSwordPivot.rotation.z = Math.sin(time * 2.5) * 0.06;
                heroSwordPivot.rotation.x = Math.sin(time * 1.8) * 0.04;
            }
        }
        if (monsterModel && !isAnimatingTurn) {
            monsterModel.position.y = monsterBasePos.y + Math.sin(time * 2.2 + 1) * 0.04;
            monsterModel.rotation.x = 0.15 + Math.sin(time * 3.0) * 0.03; // beast breathing pose
        }

        // Rotate ambient particles slowly
        ambientParticles.rotation.y = time * 0.03;

        // Update spark particles
        for (let i = sparkParticles.length - 1; i >= 0; i--) {
            const p = sparkParticles[i];
            p.life -= 0.03 * animSpeedModifier;
            p.points.material.opacity = p.life;

            const posArr = p.points.geometry.attributes.position.array;
            for (let j = 0; j < p.velocities.length; j++) {
                posArr[j * 3] += p.velocities[j].x;
                posArr[j * 3 + 1] += p.velocities[j].y;
                posArr[j * 3 + 2] += p.velocities[j].z;
                p.velocities[j].y -= 0.005;
            }
            p.points.geometry.attributes.position.needsUpdate = true;

            if (p.life <= 0) {
                scene.remove(p.points);
                p.points.geometry.dispose();
                p.points.material.dispose();
                sparkParticles.splice(i, 1);
            }
        }

        // Camera Shake effect
        if (cameraShakeIntensity > 0) {
            camera.position.x += (Math.random() - 0.5) * cameraShakeIntensity;
            camera.position.y += (Math.random() - 0.5) * cameraShakeIntensity;
            cameraShakeIntensity *= 0.85;
            if (cameraShakeIntensity < 0.01) cameraShakeIntensity = 0;
        }

        // Auto Orbit Camera when victory modal is active
        if (document.getElementById('victory-modal').classList.contains('active')) {
            controls.autoRotate = true;
            controls.autoRotateSpeed = 2.0;
        }

        controls.update();
        updateWorldHpPositions();
        renderer.render(scene, camera);
    }

    animate();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
</script>
</body>
</html>
