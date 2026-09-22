<!-- Created by 💚Messy🌿 -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>3D Ninja Mimileni — Three.js</title>
    <style>
        :root {
            --bg-top: #10161c;
            --bg-bottom: #03050a;
            --panel-bg: rgba(10, 14, 20, 0.6);
            --panel-border: rgba(255, 255, 255, 0.12);
            --text: #dcecf2;
            --accent: #3fd9c7;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            overscroll-behavior: none;
            background: radial-gradient(circle at 50% 40%, var(--bg-top), var(--bg-bottom));
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text);
        }

        html,
        body {
            height: 100vh;
        }

        @supports (height: 100svh) {

            html,
            body {
                height: 100svh;
            }
        }

        @supports (height: 100dvh) {

            html,
            body {
                height: 100dvh;
            }
        }

        #canvas-holder {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            touch-action: none;
            cursor: grab;
        }

        #canvas-holder:active {
            cursor: grabbing;
        }

        #canvas-holder canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        /* Two vertical stacks, one hugging each edge of the screen and centered
     top-to-bottom, instead of one wide bar across the bottom. Each stack
     keeps its buttons grouped and labeled the same way as before — the
     groups themselves are just handed out so each side carries six buttons. */
        .panel {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            backdrop-filter: blur(10px);
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
            padding: 12px 10px;
            font-size: 13px;
            text-align: center;
            width: 128px;
            max-width: 30vw;
            max-height: calc(100vh - 32px);
            max-height: calc(100svh - 32px);
            max-height: calc(100dvh - 32px);
            overflow-y: auto;
            color: var(--text);
            z-index: 2;
            transition: opacity .25s ease, transform .25s ease;
        }

        .panel.panel-left {
            left: calc(16px + env(safe-area-inset-left, 0px));
        }

        .panel.panel-right {
            right: calc(16px + env(safe-area-inset-right, 0px));
        }

        /* Cinematic mode hides the UI without unmounting it, so every toggle keeps
     its state and one tap brings the whole panel back. */
        /* !important is deliberate. Without it the title and the top-right buttons
     faded but the panel stayed put, and nothing in the cascade accounted for
     it — this is a UI state override, so it wins outright rather than relying
     on specificity holding up. */
        body.cinematic .panel,
        body.cinematic .hintBar,
        body.cinematic .title,
        body.cinematic .topRightBtns {
            opacity: 0 !important;
            pointer-events: none !important;
        }

        body.cinematic .panel.panel-left {
            transform: translateY(-50%) translateX(-10px) !important;
        }

        body.cinematic .panel.panel-right {
            transform: translateY(-50%) translateX(10px) !important;
        }

        body.cinematic .hintBar {
            transform: translateX(-50%) translateY(8px) !important;
        }

        body.cinematic .title,
        body.cinematic .topRightBtns {
            transform: translateY(-8px) !important;
        }

        body.cinematic #cinemaExit {
            display: block;
        }

        /* The orbit/zoom hint used to live inside the button panel; now that the
     panel has split to the two edges, it gets its own small pill so it keeps
     its old bottom-center spot without belonging to either side. */
        .hintBar {
            position: fixed;
            left: 50%;
            bottom: calc(14px + env(safe-area-inset-bottom, 0px));
            transform: translateX(-50%);
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            backdrop-filter: blur(10px);
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
            padding: 7px 14px;
            font-size: 12px;
            text-align: center;
            color: var(--text);
            z-index: 2;
            max-width: 90vw;
            transition: opacity .25s ease, transform .25s ease;
        }

        .hintBar .hint {
            white-space: wrap;
            opacity: .85;
        }

        .hintBar .hint .pan-hint::before {
            content: " • ";
        }

        /* Buttons stack in a single vertical column down each side rather than
     flowing in a horizontal grid — the panel itself is now the narrow axis. */
        .panel .btn-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }

        .panel .btn-row button {
            width: 100%;
            text-align: center;
        }

        .group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            width: 100%;
        }

        .rowLabel {
            font-size: 9px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #7d8a9c;
            text-align: center;
        }

        .title {
            position: fixed;
            top: calc(16px + env(safe-area-inset-top, 0px));
            left: 16px;
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            z-index: 2;
            transition: opacity .25s ease, transform .25s ease;
        }

        .topRightBtns {
            position: fixed;
            top: calc(16px + env(safe-area-inset-top, 0px));
            right: 16px;
            z-index: 2;
            display: flex;
            gap: 8px;
            transition: opacity .25s ease, transform .25s ease;
        }

        #cinemaExit {
            display: none;
            position: fixed;
            bottom: calc(18px + env(safe-area-inset-bottom, 0px));
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            opacity: .5;
            font-size: 11px;
        }

        .noteBtn {
            position: relative;
            z-index: 2;
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
            overflow: hidden;
            padding: 1.5px;
            cursor: pointer;
        }

        .noteBtn::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 260%;
            height: 260%;
            background: conic-gradient(from 0deg, transparent 0deg, var(--accent) 35deg, transparent 80deg, transparent 360deg);
            animation: noteBorderSpin 4s linear infinite;
            z-index: 0;
        }

        .noteBtn .noteBtnInner {
            position: relative;
            z-index: 1;
            display: block;
            background: var(--panel-bg);
            backdrop-filter: blur(10px);
            clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px);
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
        }

        .noteBtn:hover .noteBtnInner {
            filter: brightness(1.15);
        }

        .noteOverlay {
            position: fixed;
            inset: 0;
            background: rgba(2, 4, 10, 0.72);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
            padding: 24px;
            padding-top: calc(24px + env(safe-area-inset-top, 0px));
            padding-bottom: calc(24px + env(safe-area-inset-bottom, 0px));
        }

        .noteOverlay.open {
            display: flex;
        }

        .noteCard {
            position: relative;
            clip-path: polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px);
            max-width: 480px;
            width: 100%;
            max-height: calc(100vh - 96px);
            max-height: calc(100svh - 96px);
            max-height: calc(100dvh - 96px);
            overflow: hidden;
            padding: 2px;
        }

        .noteCard::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 260%;
            height: 260%;
            background: conic-gradient(from 0deg, transparent 0deg, var(--accent) 35deg, transparent 80deg, transparent 360deg);
            animation: noteBorderSpin 4s linear infinite;
            z-index: 0;
        }

        @keyframes noteBorderSpin {
            from {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .noteContent {
            position: relative;
            z-index: 1;
            background: rgba(8, 12, 22, 0.94);
            backdrop-filter: blur(14px);
            clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px);
            max-height: calc(100vh - 100px);
            max-height: calc(100svh - 100px);
            max-height: calc(100dvh - 100px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            touch-action: pan-y;
            padding: 24px 26px;
            color: var(--text);
            line-height: 1.6;
            font-size: 14px;
        }

        .noteContent p {
            margin: 0 0 14px 0;
            white-space: pre-line;
        }

        .noteClose {
            display: block;
            margin: 10px auto 0 auto;
            background: var(--accent);
            border: none;
            color: #041018;
            padding: 6px 16px;
            clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        button {
            background: var(--accent);
            border: none;
            color: #041018;
            padding: 6px 12px;
            clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        button:hover {
            filter: brightness(1.1);
        }

        button:disabled {
            opacity: .45;
            cursor: default;
            filter: none;
        }

        /* Toggles read as outlined when off, filled when on, so their state is
     visible without reading the label. */
        button.toggle {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--panel-border);
        }

        button.toggle.on {
            background: var(--accent);
            color: #041018;
            border-color: var(--accent);
        }

        #loadingOverlay {
            position: fixed;
            inset: 0;
            z-index: 5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            background: radial-gradient(circle at 50% 40%, var(--bg-top), var(--bg-bottom));
            transition: opacity 0.5s ease;
            padding: 24px;
            text-align: center;
        }

        #loadingOverlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loaderRing {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 3px solid rgba(63, 217, 199, 0.2);
            border-top-color: var(--accent);
            animation: loaderSpin 0.9s linear infinite;
            box-shadow: 0 0 16px rgba(63, 217, 199, 0.35);
        }

        @keyframes loaderSpin {
            to {
                transform: rotate(360deg);
            }
        }

        .loaderText {
            font-size: 13px;
            color: var(--text);
            opacity: 0.85;
            letter-spacing: 0.02em;
            max-width: 34em;
            line-height: 1.6;
        }

        .toast {
            position: fixed;
            left: 50%;
            top: calc(64px + env(safe-area-inset-top, 0px));
            transform: translateX(-50%) translateY(-6px);
            background: rgba(8, 12, 22, .92);
            border: 1px solid var(--panel-border);
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
            padding: 7px 14px;
            font-size: 12px;
            color: var(--text);
            z-index: 6;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease, transform .25s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ---- Lesson overlay ---- */
        .lessonOverlay {
            position: fixed;
            inset: 0;
            background: rgba(2, 4, 10, 0.78);
            display: none;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            z-index: 10;
            padding: 20px;
            padding-top: calc(20px + env(safe-area-inset-top, 0px));
            padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        }

        .lessonOverlay.open {
            display: flex;
        }

        .lessonCard {
            position: relative;
            clip-path: polygon(16px 0, 100% 0, 100% calc(100% - 16px), calc(100% - 16px) 100%, 0 100%, 0 16px);
            max-width: 640px;
            width: 100%;
            height: calc(100vh - 40px);
            height: calc(100svh - 40px);
            height: calc(100dvh - 40px);
            max-height: calc(100vh - 40px);
            max-height: calc(100svh - 40px);
            max-height: calc(100dvh - 40px);
            overflow: hidden;
            padding: 2px;
        }

        .lessonCard::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 260%;
            height: 260%;
            background: conic-gradient(from 0deg, transparent 0deg, var(--accent) 35deg, transparent 80deg, transparent 360deg);
            animation: noteBorderSpin 4s linear infinite;
            z-index: 0;
        }

        .lessonContent {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            background: rgba(8, 12, 22, 0.96);
            backdrop-filter: blur(14px);
            clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px);
            height: calc(100vh - 44px);
            height: calc(100svh - 44px);
            height: calc(100dvh - 44px);
            max-height: calc(100vh - 44px);
            max-height: calc(100svh - 44px);
            max-height: calc(100dvh - 44px);
            color: var(--text);
        }

        .lessonHeader {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid var(--panel-border);
            flex-shrink: 0;
        }

        .lessonHeaderTitle {
            font-size: 13px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: 0.03em;
        }

        .lessonCounter {
            font-size: 12px;
            color: #8a95a8;
        }

        .lessonCloseBtn {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--panel-border);
            clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px);
            padding: 4px 10px;
            font-size: 13px;
            cursor: pointer;
            margin-left: 10px;
        }

        .lessonViewport {
            position: relative;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            touch-action: pan-y;
            flex: 1 1 auto;
            min-height: 0;
            scroll-behavior: smooth;
            scroll-snap-type: y proximity;
        }

        .lessonSlides {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .lessonSlide {
            width: 100%;
            min-height: 100%;
            box-sizing: border-box;
            padding: 18px 22px 40px 22px;
            scroll-snap-align: start;
            border-bottom: 1px solid var(--panel-border);
        }

        .lessonSlide:last-child {
            border-bottom: none;
        }

        .lessonSlide .slide-inner {
            max-width: 100%;
        }

        .lessonOverlay .kicker {
            color: var(--accent);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .lessonOverlay h1 {
            font-size: 21px;
            margin: 0 0 10px 0;
            line-height: 1.3;
            color: var(--text);
        }

        .lessonOverlay h2.slideTitle {
            font-size: 16px;
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
        }

        .lessonOverlay .sub {
            color: #8a95a8;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 6px;
        }

        .lessonOverlay p {
            font-size: 13.5px;
            line-height: 1.65;
            color: #c4cede;
            margin: 0 0 12px 0;
        }

        .lessonOverlay .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--accent);
            color: #041018;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .lessonOverlay pre {
            background: #0a0d13;
            border: 1px solid var(--panel-border);
            border-radius: 10px;
            padding: 11px 13px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x;
            font-size: 10.5px;
            line-height: 1.5;
            margin: 8px 0 4px 0;
            text-align: left;
        }

        .lessonOverlay .codeHint {
            font-size: 10px;
            color: #6b7688;
            text-align: center;
            margin: 0 0 14px 0;
            letter-spacing: 0.02em;
        }

        .lessonOverlay code {
            font-family: "SF Mono", Menlo, Consolas, monospace;
        }

        .lessonOverlay .kw {
            color: #ff9d5c;
        }

        .lessonOverlay .fn {
            color: #35d0ff;
        }

        .lessonOverlay .str {
            color: #a3e07a;
        }

        .lessonOverlay .cm {
            color: #6b7688;
        }

        .lessonOverlay .try {
            background: rgba(63, 217, 199, 0.07);
            border: 1px solid rgba(63, 217, 199, 0.25);
            border-radius: 10px;
            padding: 11px 13px;
            font-size: 12.5px;
            color: #c4cede;
            margin: 6px 0 12px 0;
        }

        .lessonOverlay .try b {
            color: var(--accent);
        }

        .lessonOverlay .title-slide {
            text-align: center;
            padding-top: 8px;
        }

        .lessonOverlay .title-emoji {
            font-size: 38px;
            margin-bottom: 8px;
        }

        #lessonCanvasHolder {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            max-height: 40vh;
            border-radius: 12px;
            overflow: hidden;
            touch-action: none;
            cursor: grab;
            background: radial-gradient(circle at 50% 40%, #131a1c, #05070a);
            border: 1px solid var(--panel-border);
            margin-bottom: 12px;
        }

        #lessonCanvasHolder:active {
            cursor: grabbing;
        }

        #lessonCanvasHolder canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .lessonOverlay .demo-hint {
            font-size: 11px;
            color: #8a95a8;
            text-align: center;
            margin-bottom: 12px;
        }

        .lessonOverlay .controls-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
            margin: 4px 0 4px 0;
        }

        .lessonOverlay button.uiBtn {
            padding: 7px 12px;
            font-size: 12px;
        }

        .lessonOverlay button.uiBtn.secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--panel-border);
        }

        .lessonNavStack {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 3;
        }

        .lessonNav {
            position: static;
            background: rgba(10, 14, 22, 0.7);
            border: 1px solid var(--panel-border);
            color: var(--text);
            width: 34px;
            height: 34px;
            clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(8px);
            padding: 0;
        }

        .lessonNav:hover {
            filter: brightness(1.2);
        }

        .lessonNav[disabled] {
            opacity: 0.25;
            cursor: default;
        }

        .lessonDots {
            display: flex;
            gap: 7px;
            justify-content: center;
            align-items: center;
            padding: 10px 0 12px 0;
            flex-shrink: 0;
            border-top: 1px solid var(--panel-border);
        }

        .lessonDot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.22);
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .lessonDot.active {
            background: var(--accent);
            transform: scale(1.3);
        }

        @media (max-width: 480px) {
            .panel {
                width: 100px;
                max-width: 32vw;
                padding: 8px 6px;
                gap: 8px;
                font-size: 11px;
            }

            .panel.panel-left {
                left: calc(8px + env(safe-area-inset-left, 0px));
            }

            .panel.panel-right {
                right: calc(8px + env(safe-area-inset-right, 0px));
            }

            .panel .btn-row {
                gap: 4px;
            }

            button {
                padding: 5px 6px;
                font-size: 10px;
            }

            /* The title ran straight under the Lesson / A note buttons on a narrow
       phone. Capped to whatever is left beside them — 155px of buttons plus
       their margins — and truncated rather than allowed to collide. */
            .title {
                font-size: 15px;
                top: calc(12px + env(safe-area-inset-top, 0px));
                left: 12px;
                max-width: calc(100vw - 195px);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .topRightBtns {
                top: calc(12px + env(safe-area-inset-top, 0px));
                right: 12px;
                gap: 6px;
            }

            .noteBtn .noteBtnInner {
                padding: 6px 10px;
                font-size: 11px;
            }

            .hintBar {
                max-width: 78vw;
                padding: 6px 10px;
                font-size: 11px;
            }

            .hintBar .hint .pan-hint {
                display: none;
            }
        }

        @media (max-width: 360px) {
            .panel {
                width: 88px;
                padding: 7px 5px;
                gap: 6px;
            }

            .panel .btn-row {
                gap: 3px;
            }

            button {
                padding: 4px 5px;
                font-size: 9.5px;
            }
        }
    </style>
</head>

<body>

    <div class="title">🗡️ 3D Ninja Mimileni</div>
    <div id="loadingOverlay">
        <div class="loaderRing"></div>
        <div class="loaderText">Sharpening the details…</div>
    </div>
    <div class="toast" id="toast"></div>

    <div class="topRightBtns">
        <div class="noteBtn" id="noteBtn"><span class="noteBtnInner">📝 A note</span></div>
    </div>

    <div id="canvas-holder"></div>

    <div class="hintBar" id="hintBar">
        <span class="hint">• Drag to orbit • Pinch to zoom<span class="pan-hint">2-finger drag to pan</span></span>
    </div>

    <!-- Buttons split across the two edges of the screen, six per side, each
     kept in its original group (moves that play once, poses that stay on,
     what the camera does, what the scene does) rather than broken apart. -->
    <div class="panel panel-left" id="panelLeft">
        <div class="group">
            <div class="rowLabel">View</div>
            <div class="btn-row">
                <button id="orbitBtn" class="toggle">Orbit</button>
                <button id="wireBtn" class="toggle">Wireframe</button>
                <button id="auraBtn">Aura</button>
            </div>
        </div>

        <div class="group">
            <div class="rowLabel">Pose</div>
            <div class="btn-row">
                <button id="stanceBtn" class="toggle">Stance</button>
                <button id="sheatheBtn" class="toggle">Hold</button>
            </div>
        </div>
    </div>

    <div class="panel panel-right" id="panelRight">
        <div class="group">
            <div class="rowLabel">Moves</div>
            <div class="btn-row">
                <button id="actionBtn">Draw</button>
                <button id="spinBtn">Spin</button>
                <button id="bowBtn">Bow</button>
            </div>
        </div>

        <div class="group">
            <div class="rowLabel">Scene</div>
            <div class="btn-row">
                <button id="resetBtn">Reset</button>
                <button id="shotBtn">Photo</button>
                <button id="cinemaBtn">Hide UI</button>
            </div>
        </div>
    </div>

    <button id="cinemaExit">tap to show controls</button>

    <div class="noteOverlay" id="noteOverlay">
        <div class="noteCard">
            <div class="noteContent">
                <p>Thank you for coming back to us! Last time: you… This time: me!At least I’m not heartless like you! 🤪 I’m leaving a little note LOL Stay happy & confident! 😊 Visit me sometimes: idevmeet.com </br> When we meet again, I hope we’ll all have achieved our goals. Enjoy your time with sololearners! 😌😇</p>
                <button class="noteClose" id="noteClose">Close</button>
            </div>
        </div>
    </div>

    <div class="lessonOverlay" id="lessonOverlay">
        <div class="lessonCard">
            <div class="lessonContent">
                <div class="lessonHeader">
                    <span class="lessonHeaderTitle">📘 THREE.JS · LESSON 1</span>
                    <span style="display:flex; align-items:center;">
                        <span class="lessonCounter" id="lessonCounter">1 / 16</span>
                        <button class="lessonCloseBtn" id="lessonClose">✕</button>
                    </span>
                </div>

                <div class="lessonViewport">
                    <div class="lessonSlides" id="lessonSlides">
                        <section class="lessonSlide">
                            <div class="slide-inner title-slide">
                                <div class="title-emoji">🟢</div>
                                <div class="kicker">Three.js · Lesson 1 of a series</div>
                                <h1>Your First 3D Scene</h1>
                                <p class="sub">By the end of this lesson you'll understand what Three.js and WebGL are, what a 3D scene actually is, and you'll get a real object — the Ninja Mimileni's favorite shape, a cube — onto the screen yourself.</p>
                                <p class="sub">Scroll down to move through the lesson ↓ (or use the arrows / dots)</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">1</span>What is Three.js? What is WebGL?</h2>
                                <p><b>Three.js</b> is a JavaScript library for creating and displaying 3D graphics in a web browser. Browsers don't give you an easy way to draw 3D directly — Three.js is the toolbox that makes it manageable. With it, people build 3D cubes, planets, cars, environments, games, particle effects, entire interactive worlds — like Ninja Mimileni behind this card.</p>
                                <p><b>WebGL</b> is the browser technology that lets JavaScript talk to your graphics card. You don't need to understand it deeply — Three.js sits on top of it and does the hard part for you:</p>
                                <pre><code>JavaScript
     ↓
  Three.js
     ↓
   WebGL
     ↓
Graphics Card
     ↓
   Screen</code></pre>
                                <p>So instead of writing raw WebGL, we get to write things like <code>scene.add(cube)</code> and Three.js handles the rest.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">2</span>What you need</h2>
                                <p>Just a browser, a text/code editor, and Three.js itself. Start by making one folder with one file inside it:</p>
                                <pre><code>threejs-lesson-1/
└── index.html</code></pre>
                                <p>That's the whole project for now — one HTML file is enough to get a cube on screen.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">3</span>Your first HTML file</h2>
                                <p>Start with a bare page and one <code>&lt;script type="module"&gt;</code> tag — modules are what let us <code>import</code> Three.js.</p>
                                <pre><code><span class="cm">&lt;!DOCTYPE html&gt;</span>
<span class="fn">&lt;html&gt;</span>
<span class="fn">&lt;head&gt;</span>
    <span class="fn">&lt;title&gt;</span>My First Three.js Scene<span class="fn">&lt;/title&gt;</span>
<span class="fn">&lt;/head&gt;</span>

<span class="fn">&lt;body&gt;</span>

    <span class="fn">&lt;script type="module"&gt;</span>
        <span class="cm">// Three.js code will go here</span>
    <span class="fn">&lt;/script&gt;</span>

<span class="fn">&lt;/body&gt;</span>
<span class="fn">&lt;/html&gt;</span></code></pre>
                                <p>Then import Three.js straight from a CDN — no install step needed:</p>
                                <pre><code><span class="kw">import</span> * <span class="kw">as</span> THREE <span class="kw">from</span> <span class="str">'https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js'</span>;</code></pre>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">4</span>The three most important things</h2>
                                <p>Before we build anything, get this mental model locked in:</p>
                                <pre><code>Scene
  +
Camera
  +
Renderer
  ↓
3D image on the screen</code></pre>
                                <p>Think of it like making a movie:</p>
                                <pre><code>Scene     → Movie set
Camera    → Camera filming the set
Renderer  → Produces what we see on the screen</code></pre>
                                <p>The <b>scene</b> is your 3D world. The <b>camera</b> looks at it. The <b>renderer</b> draws what the camera sees onto the page. Every single thing in this demo — including Ninja Mimileni — comes from those three pieces plus objects added to the scene.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">5</span>Create a Scene</h2>
                                <p>One line gives you an empty 3D world:</p>
                                <pre><code><span class="kw">const</span> scene = <span class="kw">new</span> <span class="fn">THREE.Scene</span>();</code></pre>
                                <p>That's it — nothing is in it yet. Just imagine an empty void waiting to be filled.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">6</span>Create a Camera</h2>
                                <p>We'll use a <code>PerspectiveCamera</code> — it takes four settings:</p>
                                <pre><code><span class="kw">const</span> camera = <span class="kw">new</span> <span class="fn">THREE.PerspectiveCamera</span>(
    <span class="str">75</span>,
    window.innerWidth / window.innerHeight,
    <span class="str">0.1</span>,
    <span class="str">1000</span>
);</code></pre>
                                <p><b>75</b> is the field of view — how wide the camera can see. The <b>width / height</b> is the aspect ratio, matching the shape of your screen. <b>0.1</b> is the nearest distance the camera can see, and <b>1000</b> is the farthest. Don't worry about memorizing the numbers yet — just know what each one represents.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">7</span>Move the Camera</h2>
                                <p>A fresh camera starts right at the center of the world — which means it's sitting inside anything placed there. Move it backward so it can see:</p>
                                <pre><code>camera.position.z = <span class="str">5</span>;</code></pre>
                                <p>Every position in Three.js is a point on three axes:</p>
                                <pre><code>             Y
             ↑
             |
             |
             |
             +----------→ X
            /
           /
          Z</code></pre>
                                <p>We'll cover X, Y and Z properly in a later lesson — for now, just know that moving <code>z</code> to <b>5</b> pulls the camera away from the origin so it isn't stuck inside our cube.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">8</span>Create the Renderer</h2>
                                <p>The renderer is what actually draws the scene:</p>
                                <pre><code><span class="kw">const</span> renderer = <span class="kw">new</span> <span class="fn">THREE.WebGLRenderer</span>();

renderer.setSize(
    window.innerWidth,
    window.innerHeight
);

document.body.appendChild(renderer.domElement);</code></pre>
                                <p><code>setSize</code> tells it how big to draw, and <code>appendChild</code> drops its <code>&lt;canvas&gt;</code> element onto the page — that canvas is where the 3D world will actually appear.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">9</span>Put it together — and see nothing</h2>
                                <p>Stack everything so far and run it:</p>
                                <pre><code><span class="kw">import</span> * <span class="kw">as</span> THREE <span class="kw">from</span> <span class="str">'https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js'</span>;

<span class="kw">const</span> scene = <span class="kw">new</span> <span class="fn">THREE.Scene</span>();

<span class="kw">const</span> camera = <span class="kw">new</span> <span class="fn">THREE.PerspectiveCamera</span>(
    <span class="str">75</span>,
    window.innerWidth / window.innerHeight,
    <span class="str">0.1</span>,
    <span class="str">1000</span>
);

camera.position.z = <span class="str">5</span>;

<span class="kw">const</span> renderer = <span class="kw">new</span> <span class="fn">THREE.WebGLRenderer</span>();

renderer.setSize(
    window.innerWidth,
    window.innerHeight
);

document.body.appendChild(renderer.domElement);</code></pre>
                                <p>...and we see nothing 😅. That's expected — the scene is still completely empty. A camera and a renderer with nothing to look at just show a blank page. We need to add an object.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">10</span>Create a Cube</h2>
                                <p>Every visible object needs a <b>geometry</b> (its shape) and a <b>material</b> (how it looks), combined into a <b>mesh</b>:</p>
                                <pre><code><span class="cm">// the shape</span>
<span class="kw">const</span> geometry = <span class="kw">new</span> <span class="fn">THREE.BoxGeometry</span>();

<span class="cm">// the appearance</span>
<span class="kw">const</span> material = <span class="kw">new</span> <span class="fn">THREE.MeshBasicMaterial</span>({
    color: <span class="str">0x00ff00</span>
});

<span class="cm">// geometry + material = a visible object</span>
<span class="kw">const</span> cube = <span class="kw">new</span> <span class="fn">THREE.Mesh</span>(
    geometry,
    material
);</code></pre>
                                <p><code>0x00ff00</code> is green. Geometry is the shape, material is the appearance, and a mesh is what you get once you combine them — this exact pattern is how every part of Ninja Mimileni behind this card is built too.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">11</span>Add it to the scene & render</h2>
                                <p>The cube exists, but it isn't part of our world until we add it:</p>
                                <pre><code>scene.add(cube);</code></pre>
                                <p>Then tell Three.js to actually show what the camera sees:</p>
                                <pre><code>renderer.render(scene, camera);</code></pre>
                                <p>🎉 That's a complete Three.js scene — scene, camera, renderer, and one object tying them all together.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">12</span>The complete Lesson 1 code</h2>
                                <p>Everything from this lesson, together in one file:</p>
                                <pre><code><span class="kw">import</span> * <span class="kw">as</span> THREE <span class="kw">from</span> <span class="str">'https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js'</span>;

<span class="cm">// 1. Create the scene</span>
<span class="kw">const</span> scene = <span class="kw">new</span> <span class="fn">THREE.Scene</span>();

<span class="cm">// 2. Create the camera</span>
<span class="kw">const</span> camera = <span class="kw">new</span> <span class="fn">THREE.PerspectiveCamera</span>(
    <span class="str">75</span>,
    window.innerWidth / window.innerHeight,
    <span class="str">0.1</span>,
    <span class="str">1000</span>
);
camera.position.z = <span class="str">5</span>;

<span class="cm">// 3. Create the renderer</span>
<span class="kw">const</span> renderer = <span class="kw">new</span> <span class="fn">THREE.WebGLRenderer</span>();
renderer.setSize(window.innerWidth, window.innerHeight);
document.body.appendChild(renderer.domElement);

<span class="cm">// 4. Create cube geometry</span>
<span class="kw">const</span> geometry = <span class="kw">new</span> <span class="fn">THREE.BoxGeometry</span>();

<span class="cm">// 5. Create a material</span>
<span class="kw">const</span> material = <span class="kw">new</span> <span class="fn">THREE.MeshBasicMaterial</span>({ color: <span class="str">0x00ff00</span> });

<span class="cm">// 6. Combine geometry and material</span>
<span class="kw">const</span> cube = <span class="kw">new</span> <span class="fn">THREE.Mesh</span>(geometry, material);

<span class="cm">// 7. Add the cube to the scene</span>
scene.add(cube);

<span class="cm">// 8. Render the scene</span>
renderer.render(scene, camera);</code></pre>
                                <p class="sub">Geometry + Material = Mesh. Mesh + Scene = an object in your 3D world. Scene + Camera + Renderer = something you can actually see.</p>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">🎮</span>Try it live</h2>
                                <div id="lessonCanvasHolder"></div>
                                <div class="demo-hint">Drag to orbit — this is scene + camera + renderer + one mesh, running together</div>
                                <div class="controls-row">
                                    <button class="uiBtn" id="lessonColorBtn">Change color</button>
                                    <button class="uiBtn" id="lessonShapeBtn">Change shape</button>
                                    <button class="uiBtn secondary" id="lessonWireBtn">Toggle wireframe</button>
                                </div>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">❓</span>Mini exercise & quiz</h2>
                                <p><b>Exercise:</b> change the cube's color — try <code>0xff0000</code>, then <code>0x0000ff</code>. Then swap the geometry for a <code>THREE.SphereGeometry()</code> and see it appear as a sphere instead. Can you get a sphere on screen using everything from this lesson?</p>
                                <p><b>Quick quiz</b> — see if you can answer these before moving on:</p>
                                <pre><code>1. What is Three.js?
2. What does a Scene represent?
3. What does a Camera do?
4. What does a Renderer do?
5. What is BoxGeometry?
6. What is a Material?
7. What is a Mesh?
8. Why do we use scene.add(cube)?
9. Why do we use renderer.render(scene, camera)?
10. What happens if the scene has no objects in it?</code></pre>
                            </div>
                        </section>

                        <section class="lessonSlide">
                            <div class="slide-inner">
                                <h2 class="slideTitle"><span class="step-num">✓</span>What's next</h2>
                                <div class="try"><b>⭐ One sentence to remember:</b> "The scene is my 3D world, the camera looks at it, and the renderer displays what the camera sees."</div>
                                <p>Once this feels natural, Lesson 2 is: grouping several meshes together with <code>THREE.Group()</code> so they move as one unit (e.g. an "arm" made of a shoulder + elbow + hand), and positioning child meshes relative to that group.</p>
                                <p>That single idea — group, nest, repeat — is literally how Ninja Mimileni is built: torso, legs, arms, head, hair strands and a sheathed sword, all grouped and animated together by the same render loop you just saw.</p>
                                <div class="try"><b>Ready?</b> Just say "next lesson" and I'll build Lesson 2 the same way.</div>
                            </div>
                        </section>

                    </div>
                </div>

                <div class="lessonNavStack">
                    <button class="lessonNav prev" id="lessonPrev" title="Previous slide">↑</button>
                    <button class="lessonNav next" id="lessonNext" title="Next slide">↓</button>
                </div>

                <div class="lessonDots" id="lessonDots"></div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        (function() {

            // A toast is used for anything that would otherwise need an alert() — saving a
            // photo, switching aura — so nothing ever interrupts the scene.
            const toastEl = document.getElementById('toast');
            let toastTimer = null;

            function toast(message) {
                toastEl.textContent = message;
                toastEl.classList.add('show');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toastEl.classList.remove('show'), 1900);
            }

            if (typeof THREE === 'undefined') {
                const overlay = document.getElementById('loadingOverlay');
                overlay.querySelector('.loaderRing').style.display = 'none';
                overlay.querySelector('.loaderText').textContent =
                    'Could not load the Three.js library. Check your internet connection, then reload.';
                return;
            }

            try {

                const holder = document.getElementById('canvas-holder');

                function getViewportAspect() {
                    return holder.clientWidth / Math.max(1, holder.clientHeight);
                }

                function getResponsiveSettings() {
                    const aspect = getViewportAspect();
                    const portrait = aspect < 0.85;
                    const veryNarrow = aspect < 0.55;
                    return {
                        fov: veryNarrow ? 56 : portrait ? 50 : 42,
                        radius: veryNarrow ? 6.2 : portrait ? 5.2 : 4.2,
                        lookY: portrait ? -0.05 : 0.15,
                        camYOffset: portrait ? 0.0 : 0.1
                    };
                }

                // ---- Scene setup ----
                const scene = new THREE.Scene();
                scene.background = null;

                const initSettings = getResponsiveSettings();
                const camera = new THREE.PerspectiveCamera(initSettings.fov, getViewportAspect(), 0.1, 100);
                camera.position.set(2.6, 1.7, 4.2);

                const renderer = new THREE.WebGLRenderer({
                    antialias: true,
                    alpha: true,
                    powerPreference: 'default',
                    failIfMajorPerformanceCaveat: false,
                    // Required for the Photo button: without it the drawing buffer is cleared
                    // before toDataURL() can read it and every saved image comes out blank.
                    preserveDrawingBuffer: true
                });
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                renderer.setSize(holder.clientWidth, holder.clientHeight);
                renderer.shadowMap.enabled = true;
                holder.appendChild(renderer.domElement);

                // ---- Lights ----
                const hemi = new THREE.HemisphereLight(0x2fd0c0, 0x05070a, 0.55);
                scene.add(hemi);

                const rim = new THREE.DirectionalLight(0x6fe8d8, 0.9);
                rim.position.set(-3, 3, -2);
                scene.add(rim);

                const keyLight = new THREE.DirectionalLight(0xbfe8ff, 0.35);
                keyLight.position.set(2, 4, 3);
                keyLight.castShadow = true;
                keyLight.shadow.mapSize.set(1024, 1024);
                scene.add(keyLight);

                const teal = new THREE.PointLight(0x3fd9c7, 1.4, 6, 2);
                teal.position.set(0.4, 1.3, 0.6);
                scene.add(teal);

                // ---- Ground ----
                const ground = new THREE.Mesh(
                    new THREE.CircleGeometry(6, 48),
                    new THREE.MeshStandardMaterial({
                        color: 0x040608,
                        roughness: 0.95,
                        metalness: 0.05
                    })
                );
                ground.rotation.x = -Math.PI / 2;
                ground.position.y = -1.05;
                ground.receiveShadow = true;
                scene.add(ground);

                const glowRing = new THREE.Mesh(
                    new THREE.RingGeometry(0.75, 0.9, 48),
                    new THREE.MeshBasicMaterial({
                        color: 0x3fd9c7,
                        transparent: true,
                        opacity: 0.3,
                        side: THREE.DoubleSide
                    })
                );
                glowRing.rotation.x = -Math.PI / 2;
                glowRing.position.y = -1.04;
                scene.add(glowRing);

                // Swept arc that appears only while a blade is actually moving through air.
                const swoosh = new THREE.Mesh(
                    new THREE.TorusGeometry(0.95, 0.035, 8, 40, Math.PI * 0.9),
                    new THREE.MeshBasicMaterial({
                        color: 0x6ff5e5,
                        transparent: true,
                        opacity: 0,
                        side: THREE.DoubleSide
                    })
                );
                swoosh.position.y = 0.1;
                scene.add(swoosh);

                // ---- Materials ----
                const cloth = new THREE.MeshStandardMaterial({
                    color: 0x1a1d22,
                    roughness: 0.75,
                    metalness: 0.1
                });
                const clothDark = new THREE.MeshStandardMaterial({
                    color: 0x101216,
                    roughness: 0.8,
                    metalness: 0.05
                });
                const skin = new THREE.MeshStandardMaterial({
                    color: 0xe0b89c,
                    roughness: 0.55,
                    metalness: 0.0
                });
                const hair = new THREE.MeshStandardMaterial({
                    color: 0x0c0d10,
                    roughness: 0.4,
                    metalness: 0.2
                });
                const metal = new THREE.MeshStandardMaterial({
                    color: 0xb9c4c9,
                    roughness: 0.25,
                    metalness: 0.85
                });
                const metalDark = new THREE.MeshStandardMaterial({
                    color: 0x2b2f33,
                    roughness: 0.4,
                    metalness: 0.7
                });
                const eyeGlow = new THREE.MeshBasicMaterial({
                    color: 0x6ff5e5
                });
                const wrap = new THREE.MeshStandardMaterial({
                    color: 0x2a2d33,
                    roughness: 0.6,
                    metalness: 0.3
                });

                const character = new THREE.Group();
                scene.add(character);

                const torsoGroup = new THREE.Group();
                torsoGroup.position.y = 0.15;
                character.add(torsoGroup);

                // ---- Legs ----
                function makeLeg(x) {
                    const leg = new THREE.Group();
                    leg.position.set(x, -0.55, 0);
                    const upper = new THREE.Mesh(new THREE.CylinderGeometry(0.11, 0.1, 0.5, 10), cloth);
                    upper.position.y = -0.25;
                    upper.castShadow = true;
                    leg.add(upper);
                    const lower = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.07, 0.45, 10), clothDark);
                    lower.position.y = -0.68;
                    lower.castShadow = true;
                    leg.add(lower);
                    const boot = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.1, 0.26), clothDark);
                    boot.position.set(0.02, -0.95, 0.03);
                    boot.castShadow = true;
                    leg.add(boot);
                    torsoGroup.add(leg);
                    return leg;
                }
                const legL = makeLeg(-0.14);
                const legR = makeLeg(0.14);

                // ---- Torso / cloak ----
                const torso = new THREE.Mesh(new THREE.CylinderGeometry(0.26, 0.2, 0.62, 12), cloth);
                torso.position.y = -0.05;
                torso.castShadow = true;
                torsoGroup.add(torso);

                const chestWrap = new THREE.Mesh(new THREE.CylinderGeometry(0.27, 0.21, 0.18, 12), wrap);
                chestWrap.position.y = 0.15;
                torsoGroup.add(chestWrap);

                const cloak = new THREE.Mesh(new THREE.ConeGeometry(0.34, 0.85, 10, 1, true), clothDark);
                cloak.position.set(0, -0.35, -0.05);
                cloak.rotation.x = Math.PI;
                cloak.scale.set(1.15, 1, 0.7);
                cloak.castShadow = true;
                torsoGroup.add(cloak);

                const belt = new THREE.Mesh(new THREE.TorusGeometry(0.24, 0.035, 8, 24), metalDark);
                belt.rotation.x = Math.PI / 2;
                belt.position.y = -0.24;
                torsoGroup.add(belt);

                // ---- Head ----
                const headGroup = new THREE.Group();
                headGroup.position.set(0, 0.48, 0);
                torsoGroup.add(headGroup);

                const head = new THREE.Mesh(new THREE.SphereGeometry(0.17, 20, 20), skin);
                head.scale.set(0.9, 1.05, 0.9);
                head.castShadow = true;
                headGroup.add(head);

                const mask = new THREE.Mesh(
                    new THREE.SphereGeometry(0.155, 16, 16, 0, Math.PI * 2, Math.PI * 0.35, Math.PI * 0.4),
                    clothDark
                );
                mask.position.set(0, -0.04, 0.01);
                headGroup.add(mask);

                [-0.06, 0.06].forEach((x) => {
                    const eye = new THREE.Mesh(new THREE.SphereGeometry(0.02, 8, 8), eyeGlow);
                    eye.position.set(x, 0.03, 0.155);
                    eye.scale.set(1.4, 0.6, 0.6);
                    headGroup.add(eye);
                });

                const hairCap = new THREE.Mesh(
                    new THREE.SphereGeometry(0.175, 16, 16, 0, Math.PI * 2, 0, Math.PI * 0.62), hair
                );
                hairCap.position.y = 0.02;
                hairCap.scale.set(1.02, 1.0, 1.02);
                headGroup.add(hairCap);

                const hairStrands = [];

                function makeHairStrand(startX, startZ, length, bend) {
                    const pts = [
                        new THREE.Vector3(startX, 0.02, startZ),
                        new THREE.Vector3(startX * 1.3, -length * 0.35, startZ - bend * 0.3),
                        new THREE.Vector3(startX * 1.5, -length * 0.7, startZ - bend * 0.6),
                        new THREE.Vector3(startX * 1.6, -length, startZ - bend)
                    ];
                    const curve = new THREE.CatmullRomCurve3(pts);
                    const mesh = new THREE.Mesh(new THREE.TubeGeometry(curve, 12, 0.018, 6, false), hair);
                    mesh.castShadow = true;
                    headGroup.add(mesh);
                    mesh.userData = {
                        phase: Math.random() * Math.PI * 2
                    };
                    hairStrands.push(mesh);
                    return mesh;
                }
                makeHairStrand(-0.1, -0.15, 0.85, 0.08);
                makeHairStrand(0.02, -0.17, 0.95, 0.05);
                makeHairStrand(0.12, -0.14, 0.8, -0.1);
                makeHairStrand(-0.15, -0.1, 0.55, 0.15);
                makeHairStrand(0.16, -0.1, 0.5, -0.15);

                const hood = new THREE.Mesh(new THREE.TorusGeometry(0.2, 0.06, 10, 20, Math.PI * 1.3), clothDark);
                hood.position.set(0, -0.16, -0.06);
                hood.rotation.x = Math.PI * 0.55;
                hood.rotation.z = Math.PI;
                torsoGroup.add(hood);

                // ---- Arms ----
                function makeArm(side) {
                    const shoulder = new THREE.Group();
                    shoulder.position.set(side * 0.27, 0.2, 0);
                    torsoGroup.add(shoulder);

                    const upperArm = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.06, 0.32, 10), cloth);
                    upperArm.position.y = -0.16;
                    upperArm.castShadow = true;
                    shoulder.add(upperArm);

                    const elbow = new THREE.Group();
                    elbow.position.y = -0.32;
                    shoulder.add(elbow);

                    const foreArm = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.045, 0.3, 10), clothDark);
                    foreArm.position.y = -0.15;
                    foreArm.castShadow = true;
                    elbow.add(foreArm);

                    const hand = new THREE.Mesh(new THREE.SphereGeometry(0.055, 10, 10), skin);
                    hand.position.y = -0.32;
                    elbow.add(hand);

                    return {
                        shoulder,
                        elbow,
                        hand
                    };
                }
                const armL = makeArm(-1);
                const armR = makeArm(1);

                // ---- Sheathed sword at the hip ----
                const sheathGroup = new THREE.Group();
                sheathGroup.position.set(-0.02, -0.18, -0.22);
                sheathGroup.rotation.set(0.15, 0.3, 2.7);
                torsoGroup.add(sheathGroup);

                const sheath = new THREE.Mesh(new THREE.CylinderGeometry(0.028, 0.024, 0.95, 10), metalDark);
                sheath.castShadow = true;
                sheathGroup.add(sheath);

                const sheathTrim1 = new THREE.Mesh(new THREE.TorusGeometry(0.03, 0.006, 6, 14), metal);
                sheathTrim1.position.y = 0.3;
                sheathTrim1.rotation.x = Math.PI / 2;
                sheathGroup.add(sheathTrim1);
                const sheathTrim2 = sheathTrim1.clone();
                sheathTrim2.position.y = -0.3;
                sheathGroup.add(sheathTrim2);

                const hiltCap = new THREE.Mesh(new THREE.CylinderGeometry(0.032, 0.026, 0.16, 10), wrap);
                hiltCap.position.y = 0.53;
                sheathGroup.add(hiltCap);

                const guard = new THREE.Mesh(new THREE.TorusGeometry(0.045, 0.012, 6, 16), metal);
                guard.position.y = 0.46;
                guard.rotation.x = Math.PI / 2;
                sheathGroup.add(guard);

                // ---- Held sword ----
                const bladeMat = new THREE.MeshStandardMaterial({
                    color: 0xdfe8ea,
                    roughness: 0.15,
                    metalness: 0.95,
                    emissive: 0x1a3f3a,
                    emissiveIntensity: 0.15
                });
                const heldSword = new THREE.Group();
                heldSword.visible = false;
                armR.hand.add(heldSword);

                const heldHilt = new THREE.Mesh(new THREE.CylinderGeometry(0.028, 0.024, 0.15, 10), wrap);
                heldHilt.rotation.x = Math.PI / 2;
                heldHilt.position.set(0, 0, 0.08);
                heldSword.add(heldHilt);

                const heldGuard = new THREE.Mesh(new THREE.TorusGeometry(0.05, 0.012, 6, 16), metal);
                heldGuard.rotation.y = Math.PI / 2;
                heldGuard.position.set(0, 0, 0.16);
                heldSword.add(heldGuard);

                const heldBlade = new THREE.Mesh(new THREE.BoxGeometry(0.045, 0.012, 0.82), bladeMat);
                heldBlade.position.set(0, 0, 0.6);
                heldBlade.castShadow = true;
                heldSword.add(heldBlade);

                const bladeGlow = new THREE.PointLight(0x6ff5e5, 0, 2.5, 2);
                bladeGlow.position.set(0, 0, 0.6);
                heldSword.add(bladeGlow);

                // ---- Camera orbit ----
                let isDragging = false;
                let prevX = 0,
                    prevY = 0;
                let theta = Math.atan2(camera.position.x, camera.position.z);
                let phi = Math.acos(camera.position.y / camera.position.length());
                let radius = initSettings.radius;
                let lookY = initSettings.lookY;
                let camYOffset = initSettings.camYOffset;
                let panX = 0,
                    panY = 0,
                    panZ = 0;

                function updateCamera() {
                    phi = Math.max(0.15, Math.min(1.5, phi));
                    radius = Math.max(1.6, Math.min(13, radius));
                    camera.position.x = panX + radius * Math.sin(phi) * Math.sin(theta);
                    camera.position.z = panZ + radius * Math.sin(phi) * Math.cos(theta);
                    camera.position.y = panY + radius * Math.cos(phi) + camYOffset;
                    camera.lookAt(panX, panY + lookY, panZ);
                }
                updateCamera();

                function pointerDown(x, y) {
                    isDragging = true;
                    prevX = x;
                    prevY = y;
                }

                function pointerMove(x, y) {
                    if (!isDragging) return;
                    theta -= (x - prevX) * 0.008;
                    phi -= (y - prevY) * 0.008;
                    updateCamera();
                    prevX = x;
                    prevY = y;
                }

                function pointerUp() {
                    isDragging = false;
                }

                let multiTouchActive = false;
                let pinchStartDist = 0,
                    pinchStartRadius = 0;
                let panStartMidX = 0,
                    panStartMidY = 0;
                let panStartPanX = 0,
                    panStartPanY = 0,
                    panStartPanZ = 0;

                holder.addEventListener('mousedown', (e) => pointerDown(e.clientX, e.clientY));
                window.addEventListener('mousemove', (e) => pointerMove(e.clientX, e.clientY));
                window.addEventListener('mouseup', pointerUp);

                holder.addEventListener('touchstart', (e) => {
                    if (e.touches.length === 1) {
                        multiTouchActive = false;
                        pointerDown(e.touches[0].clientX, e.touches[0].clientY);
                    } else if (e.touches.length === 2) {
                        isDragging = false;
                        multiTouchActive = true;
                        const t0 = e.touches[0],
                            t1 = e.touches[1];
                        pinchStartDist = Math.hypot(t0.clientX - t1.clientX, t0.clientY - t1.clientY);
                        pinchStartRadius = radius;
                        panStartMidX = (t0.clientX + t1.clientX) / 2;
                        panStartMidY = (t0.clientY + t1.clientY) / 2;
                        panStartPanX = panX;
                        panStartPanY = panY;
                        panStartPanZ = panZ;
                    }
                }, {
                    passive: true
                });

                holder.addEventListener('touchmove', (e) => {
                    if (e.touches.length === 1 && !multiTouchActive) {
                        pointerMove(e.touches[0].clientX, e.touches[0].clientY);
                    } else if (e.touches.length === 2) {
                        const t0 = e.touches[0],
                            t1 = e.touches[1];
                        const dist = Math.hypot(t0.clientX - t1.clientX, t0.clientY - t1.clientY);
                        if (pinchStartDist > 0) radius = pinchStartRadius * (pinchStartDist / dist);
                        const midX = (t0.clientX + t1.clientX) / 2;
                        const midY = (t0.clientY + t1.clientY) / 2;
                        const dx = midX - panStartMidX,
                            dy = midY - panStartMidY;
                        const panSpeed = radius * 0.0022;
                        const rightX = Math.cos(theta),
                            rightZ = -Math.sin(theta);
                        panX = panStartPanX - dx * panSpeed * rightX;
                        panZ = panStartPanZ - dx * panSpeed * rightZ;
                        panY = panStartPanY + dy * panSpeed;
                        updateCamera();
                    }
                }, {
                    passive: true
                });

                holder.addEventListener('touchend', (e) => {
                    if (e.touches.length === 0) {
                        isDragging = false;
                        multiTouchActive = false;
                    } else if (e.touches.length === 1) {
                        multiTouchActive = false;
                        pointerDown(e.touches[0].clientX, e.touches[0].clientY);
                    }
                }, {
                    passive: true
                });

                holder.addEventListener('touchcancel', () => {
                    isDragging = false;
                    multiTouchActive = false;
                }, {
                    passive: true
                });

                holder.addEventListener('wheel', (e) => {
                    e.preventDefault();
                    radius += e.deltaY * 0.008;
                    updateCamera();
                }, {
                    passive: false
                });

                function handleResize() {
                    renderer.setSize(holder.clientWidth, holder.clientHeight);
                    const s = getResponsiveSettings();
                    camera.aspect = getViewportAspect();
                    camera.fov = s.fov;
                    camera.updateProjectionMatrix();
                    radius = s.radius;
                    lookY = s.lookY;
                    camYOffset = s.camYOffset;
                    updateCamera();
                }
                window.addEventListener('resize', handleResize);
                window.addEventListener('orientationchange', () => setTimeout(handleResize, 200));

                // =====================================================================
                // Feature state
                // =====================================================================
                function easeInOut(x) {
                    return x < 0.5 ? 2 * x * x : 1 - Math.pow(-2 * x + 2, 2) / 2;
                }

                function lerp(a, b, f) {
                    return a + (b - a) * f;
                }

                // One action runs at a time. Each entry owns its length and its own pose
                // function, so adding a move later means adding one object here rather than
                // threading another flag through the render loop.
                const ACTIONS = {
                    draw: {
                        duration: 1.7,
                        label: 'Draw',
                        button: null
                    },
                    spin: {
                        duration: 1.9,
                        label: 'Spin',
                        button: null
                    },
                    bow: {
                        duration: 2.1,
                        label: 'Bow',
                        button: null
                    }
                };
                let action = null; // { name, clock }
                let stanceOn = false; // persistent combat pose
                let holdBlade = false; // keep the sword drawn between moves
                // Starts OFF. It used to start ON, which meant the first press turned the
                // drift off rather than on — and since the drift was slow enough to be easy
                // to miss, pressing "Orbit" looked like it did nothing at all.
                let autoOrbit = false;
                // Radians per real second (~26°/s, one full turn in about 14s — the original
                // rate was ~4.9°/s, or 74s per turn, which was slow enough to look like the
                // button did nothing). Measured against real elapsed time instead of a fixed
                // amount per frame, which is what used to make the same page spin twice as
                // fast on a 120Hz screen as on a 60Hz one.
                const AUTO_ORBIT_RATE = 0.45;
                let autoOrbitLast = performance.now();
                let wireframe = false;
                let cinematic = false;
                let auraIndex = 0;

                const AURAS = [{
                        name: 'Teal',
                        light: 0x3fd9c7,
                        glow: 0x6ff5e5,
                        css: '#3fd9c7'
                    },
                    {
                        name: 'Crimson',
                        light: 0xff4d5e,
                        glow: 0xff8d95,
                        css: '#ff6b76'
                    },
                    {
                        name: 'Violet',
                        light: 0xa66bff,
                        glow: 0xc9a3ff,
                        css: '#a66bff'
                    },
                    {
                        name: 'Amber',
                        light: 0xffb23f,
                        glow: 0xffd48a,
                        css: '#ffb23f'
                    },
                    {
                        name: 'Frost',
                        light: 0x6ec6ff,
                        glow: 0xb7e4ff,
                        css: '#6ec6ff'
                    }
                ];

                const btn = (id) => document.getElementById(id);
                const actionBtn = btn('actionBtn');
                const spinBtn = btn('spinBtn');
                const bowBtn = btn('bowBtn');
                const stanceBtn = btn('stanceBtn');
                const sheatheBtn = btn('sheatheBtn');
                const orbitBtn = btn('orbitBtn');
                const wireBtn = btn('wireBtn');
                const auraBtn = btn('auraBtn');
                const resetBtn = btn('resetBtn');
                const shotBtn = btn('shotBtn');
                const cinemaBtn = btn('cinemaBtn');
                const cinemaExit = btn('cinemaExit');

                ACTIONS.draw.button = actionBtn;
                ACTIONS.spin.button = spinBtn;
                ACTIONS.bow.button = bowBtn;

                function startAction(name) {
                    if (action) return; // one move at a time
                    action = {
                        name,
                        clock: 0
                    };
                    Object.values(ACTIONS).forEach((a) => {
                        a.button.disabled = true;
                    });
                    ACTIONS[name].button.textContent = '…';
                }

                function endAction() {
                    const finished = action && action.name;
                    action = null;
                    Object.entries(ACTIONS).forEach(([key, a]) => {
                        a.button.disabled = false;
                        a.button.textContent = a.label;
                    });
                    // The blade only goes away if it is not being deliberately held out.
                    if (!holdBlade && !stanceOn) {
                        heldSword.visible = false;
                        bladeGlow.intensity = 0;
                    }
                    return finished;
                }

                actionBtn.addEventListener('click', () => startAction('draw'));
                spinBtn.addEventListener('click', () => startAction('spin'));
                bowBtn.addEventListener('click', () => startAction('bow'));

                function setToggle(button, on) {
                    button.classList.toggle('on', on);
                }

                stanceBtn.addEventListener('click', () => {
                    stanceOn = !stanceOn;
                    setToggle(stanceBtn, stanceOn);
                    if (stanceOn) {
                        heldSword.visible = true;
                    } else if (!holdBlade) {
                        heldSword.visible = false;
                        bladeGlow.intensity = 0;
                    }
                    toast(stanceOn ? 'Ready stance' : 'At ease');
                });

                sheatheBtn.addEventListener('click', () => {
                    holdBlade = !holdBlade;
                    setToggle(sheatheBtn, holdBlade);
                    sheatheBtn.textContent = holdBlade ? 'Sheathe' : 'Hold';
                    if (holdBlade) heldSword.visible = true;
                    else if (!stanceOn && !action) {
                        heldSword.visible = false;
                        bladeGlow.intensity = 0;
                    }
                    toast(holdBlade ? 'Blade drawn' : 'Blade sheathed');
                });

                orbitBtn.addEventListener('click', () => {
                    autoOrbit = !autoOrbit;
                    setToggle(orbitBtn, autoOrbit);
                    toast(autoOrbit ? 'Auto-orbit on' : 'Auto-orbit off');
                });

                wireBtn.addEventListener('click', () => {
                    wireframe = !wireframe;
                    setToggle(wireBtn, wireframe);
                    // Walks every material in the scene, so this also reveals the ground and
                    // the hair tubes — the point is seeing how she is actually built.
                    scene.traverse((obj) => {
                        if (obj.isMesh && obj.material && 'wireframe' in obj.material) {
                            obj.material.wireframe = wireframe;
                        }
                    });
                    toast(wireframe ? 'Wireframe — this is the geometry' : 'Solid');
                });

                function applyAura() {
                    const a = AURAS[auraIndex];
                    teal.color.setHex(a.light);
                    hemi.color.setHex(a.light);
                    rim.color.setHex(a.glow);
                    glowRing.material.color.setHex(a.light);
                    eyeGlow.color.setHex(a.glow);
                    bladeGlow.color.setHex(a.glow);
                    bladeMat.emissive.setHex(a.light);
                    swoosh.material.color.setHex(a.glow);
                    document.documentElement.style.setProperty('--accent', a.css);
                }
                auraBtn.addEventListener('click', () => {
                    auraIndex = (auraIndex + 1) % AURAS.length;
                    applyAura();
                    toast('Aura: ' + AURAS[auraIndex].name);
                });

                resetBtn.addEventListener('click', () => {
                    const s = getResponsiveSettings();
                    panX = panY = panZ = 0;
                    radius = s.radius;
                    theta = Math.atan2(2.6, 4.2);
                    phi = 1.0;
                    updateCamera();
                    toast('View reset');
                });

                shotBtn.addEventListener('click', () => {
                    // Render once immediately so the buffer holds the current frame, then read it.
                    renderer.render(scene, camera);
                    try {
                        const url = renderer.domElement.toDataURL('image/png');
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = '3d-ninja-mimileni.png';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        toast('Photo saved');
                    } catch (err) {
                        toast('Could not save the photo here');
                    }
                });

                // Driven by inline styles rather than left to the stylesheet. The class alone
                // hid the title and the top-right buttons but not the panel, with nothing in
                // the cascade explaining the difference; setting the property on the element
                // itself is not something another rule can quietly outrank. The class stays so
                // #cinemaExit still appears.
                const cinematicTargets = [
                    document.getElementById('panelLeft'),
                    document.getElementById('panelRight'),
                    document.getElementById('hintBar'),
                    document.querySelector('.title'),
                    document.querySelector('.topRightBtns')
                ];

                function setCinematic(on) {
                    cinematic = on;
                    document.body.classList.toggle('cinematic', on);
                    cinematicTargets.forEach((el) => {
                        if (!el) return;
                        el.style.opacity = on ? '0' : '';
                        el.style.pointerEvents = on ? 'none' : '';
                    });
                }
                cinemaBtn.addEventListener('click', () => {
                    setCinematic(true);
                    toast('Tap anywhere to bring the controls back');
                });
                cinemaExit.addEventListener('click', () => setCinematic(false));
                holder.addEventListener('click', () => {
                    if (cinematic) setCinematic(false);
                });

                // Keyboard shortcuts — quicker than aiming at small buttons on a laptop.
                window.addEventListener('keydown', (e) => {
                    if (document.querySelector('.lessonOverlay.open') || document.querySelector('.noteOverlay.open')) return;
                    const key = e.key.toLowerCase();
                    if (key === ' ') {
                        e.preventDefault();
                        startAction('draw');
                    } else if (key === 's') startAction('spin');
                    else if (key === 'b') startAction('bow');
                    else if (key === 'r') resetBtn.click();
                    else if (key === 'w') wireBtn.click();
                    else if (key === 'c') auraBtn.click();
                    else if (key === 'o') orbitBtn.click();
                    else if (key === 'h') cinematic ? setCinematic(false) : setCinematic(true);
                });

                // ---- Note overlay ----
                const noteBtn = document.getElementById('noteBtn');
                const noteOverlay = document.getElementById('noteOverlay');
                const noteClose = document.getElementById('noteClose');
                noteBtn.addEventListener('click', () => noteOverlay.classList.add('open'));
                noteClose.addEventListener('click', () => noteOverlay.classList.remove('open'));
                noteOverlay.addEventListener('click', (e) => {
                    if (e.target === noteOverlay) noteOverlay.classList.remove('open');
                });

                // =====================================================================
                // Animation
                // =====================================================================
                let t = 0;
                let firstFrameShown = false;
                const loadingOverlay = document.getElementById('loadingOverlay');

                function poseDraw(p) {
                    const T_REACH = 0.18,
                        T_DRAW = 0.32,
                        T_SLASH = 0.55,
                        T_HOLD = 0.78;
                    let shoulderZ, shoulderX, elbowZ, torque = 0;
                    if (p < T_REACH) {
                        const f = easeInOut(p / T_REACH);
                        shoulderZ = lerp(0.06, -0.55, f);
                        shoulderX = lerp(0, 0.1, f);
                        elbowZ = lerp(-0.15, -1.0, f);
                        heldSword.visible = f > 0.6 || holdBlade || stanceOn;
                    } else if (p < T_DRAW) {
                        const f = easeInOut((p - T_REACH) / (T_DRAW - T_REACH));
                        shoulderZ = lerp(-0.55, -1.4, f);
                        shoulderX = lerp(0.1, -0.9, f);
                        elbowZ = lerp(-1.0, -0.3, f);
                        heldSword.visible = true;
                        bladeGlow.intensity = lerp(0, 1.5, f);
                    } else if (p < T_SLASH) {
                        const f = easeInOut((p - T_DRAW) / (T_SLASH - T_DRAW));
                        shoulderZ = lerp(-1.4, 0.9, f);
                        shoulderX = lerp(-0.9, 0.5, f);
                        elbowZ = lerp(-0.3, -0.1, f);
                        torque = Math.sin(f * Math.PI) * 0.3;
                        bladeGlow.intensity = 2.5 + Math.sin(f * Math.PI) * 1.5;
                        swoosh.material.opacity = Math.sin(f * Math.PI) * 0.5;
                        swoosh.rotation.set(Math.PI * 0.35, theta + f * 1.4, 0.6);
                    } else if (p < T_HOLD) {
                        const f = (p - T_SLASH) / (T_HOLD - T_SLASH);
                        shoulderZ = 0.9 - f * 0.05;
                        shoulderX = 0.5;
                        elbowZ = -0.1;
                        bladeGlow.intensity = lerp(3.5, 1.5, f);
                    } else {
                        const f = easeInOut((p - T_HOLD) / (1 - T_HOLD));
                        shoulderZ = lerp(0.85, 0.06, f);
                        shoulderX = lerp(0.5, 0, f);
                        elbowZ = lerp(-0.1, -0.15, f);
                        bladeGlow.intensity = lerp(1.5, holdBlade || stanceOn ? 1.2 : 0, f);
                        if (f > 0.85 && !holdBlade && !stanceOn) heldSword.visible = false;
                    }
                    armR.shoulder.rotation.z = shoulderZ;
                    armR.shoulder.rotation.x = shoulderX;
                    armR.elbow.rotation.z = elbowZ;
                    torsoGroup.rotation.y += torque * 0.4;
                    headGroup.rotation.x = torque * 0.5;
                }

                function poseSpin(p) {
                    // A full turn with the blade out, wound up at the start and settling at the end.
                    const windUp = 0.18,
                        strike = 0.72;
                    heldSword.visible = true;
                    if (p < windUp) {
                        const f = easeInOut(p / windUp);
                        character.rotation.y = lerp(0, -0.5, f);
                        armR.shoulder.rotation.z = lerp(0.06, -0.7, f);
                        armR.shoulder.rotation.x = lerp(0, 0.4, f);
                        armR.elbow.rotation.z = lerp(-0.15, -0.6, f);
                        bladeGlow.intensity = lerp(0, 1.2, f);
                        legL.rotation.x = lerp(0, -0.2, f);
                    } else if (p < strike) {
                        const f = easeInOut((p - windUp) / (strike - windUp));
                        character.rotation.y = lerp(-0.5, Math.PI * 2, f);
                        armR.shoulder.rotation.z = lerp(-0.7, 1.15, Math.sin(f * Math.PI));
                        armR.shoulder.rotation.x = lerp(0.4, 0.15, f);
                        armR.elbow.rotation.z = -0.1;
                        bladeGlow.intensity = 3.2 + Math.sin(f * Math.PI * 2) * 1.2;
                        swoosh.material.opacity = 0.55 * Math.sin(f * Math.PI);
                        swoosh.rotation.set(Math.PI * 0.5, character.rotation.y, 0);
                        torsoGroup.rotation.z = Math.sin(f * Math.PI) * 0.16;
                    } else {
                        const f = easeInOut((p - strike) / (1 - strike));
                        character.rotation.y = lerp(Math.PI * 2, Math.PI * 2, f);
                        armR.shoulder.rotation.z = lerp(1.15, stanceOn ? 0.5 : 0.06, f);
                        armR.shoulder.rotation.x = lerp(0.15, 0, f);
                        armR.elbow.rotation.z = lerp(-0.1, -0.15, f);
                        torsoGroup.rotation.z = lerp(0.16, 0, f);
                        legL.rotation.x = lerp(-0.2, 0, f);
                        bladeGlow.intensity = lerp(3.2, holdBlade || stanceOn ? 1.2 : 0, f);
                        if (f > 0.9) character.rotation.y = 0;
                    }
                }

                function poseBow(p) {
                    // Slow, deliberate, and it does not draw a weapon — the opposite of the slash.
                    const down = 0.35,
                        hold = 0.65;
                    let bend, headTilt;
                    if (p < down) {
                        const f = easeInOut(p / down);
                        bend = lerp(0, 0.62, f);
                        headTilt = lerp(0, 0.35, f);
                    } else if (p < hold) {
                        bend = 0.62;
                        headTilt = 0.35;
                    } else {
                        const f = easeInOut((p - hold) / (1 - hold));
                        bend = lerp(0.62, 0, f);
                        headTilt = lerp(0.35, 0, f);
                    }
                    torsoGroup.rotation.x = bend;
                    headGroup.rotation.x = headTilt;
                    // Arms fall towards the body as she folds forward.
                    armL.shoulder.rotation.x = -bend * 0.55;
                    armR.shoulder.rotation.x = -bend * 0.55;
                    armL.shoulder.rotation.z = lerp(-0.08, -0.18, bend / 0.62);
                    armR.shoulder.rotation.z = lerp(0.06, 0.18, bend / 0.62);
                    armL.elbow.rotation.z = -0.25;
                    armR.elbow.rotation.z = -0.25;
                }

                function poseStanceIdle() {
                    // Blade forward, weight low, feet apart — held for as long as the toggle is on.
                    armR.shoulder.rotation.z += (0.55 - armR.shoulder.rotation.z) * 0.09;
                    armR.shoulder.rotation.x += (0.35 - armR.shoulder.rotation.x) * 0.09;
                    armR.elbow.rotation.z += (-0.75 - armR.elbow.rotation.z) * 0.09;
                    armL.shoulder.rotation.z += (-0.45 - armL.shoulder.rotation.z) * 0.09;
                    armL.elbow.rotation.z += (-0.9 - armL.elbow.rotation.z) * 0.09;
                    legL.rotation.z += (0.16 - legL.rotation.z) * 0.09;
                    legR.rotation.z += (-0.16 - legR.rotation.z) * 0.09;
                    torsoGroup.position.y += (0.06 - torsoGroup.position.y) * 0.09;
                    bladeGlow.intensity += (1.2 - bladeGlow.intensity) * 0.08;
                }

                function poseRelaxedIdle() {
                    armR.shoulder.rotation.z += (0.06 - armR.shoulder.rotation.z) * 0.08;
                    armR.shoulder.rotation.x += (0 - armR.shoulder.rotation.x) * 0.08;
                    armR.elbow.rotation.z += (-0.15 - armR.elbow.rotation.z) * 0.08;
                    armL.shoulder.rotation.z += (-0.08 - armL.shoulder.rotation.z) * 0.08;
                    armL.shoulder.rotation.x += (0 - armL.shoulder.rotation.x) * 0.08;
                    armL.elbow.rotation.z += (-0.15 - armL.elbow.rotation.z) * 0.08;
                    legL.rotation.z += (0 - legL.rotation.z) * 0.08;
                    legR.rotation.z += (0 - legR.rotation.z) * 0.08;
                    legL.rotation.x += (0 - legL.rotation.x) * 0.08;
                    torsoGroup.position.y += (0.15 - torsoGroup.position.y) * 0.08;
                    if (holdBlade) bladeGlow.intensity += (1.0 - bladeGlow.intensity) * 0.08;
                }

                function animate() {
                    requestAnimationFrame(animate);

                    // Fixed step per frame; this used to be scaled by the Slow-mo button,
                    // which has been removed — everything now runs at normal speed.
                    const dt = 0.02;
                    t += dt;

                    character.position.y = Math.sin(t * 1.4) * 0.015;
                    torsoGroup.rotation.y = Math.sin(t * 0.6) * 0.04;
                    headGroup.rotation.y = Math.sin(t * 0.5 + 0.4) * 0.08;

                    const breathe = 1 + Math.sin(t * 1.2) * 0.01;
                    torso.scale.set(breathe, 1, breathe);

                    hairStrands.forEach((mesh) => {
                        mesh.rotation.z = Math.sin(t * 1.1 + mesh.userData.phase) * 0.03;
                        mesh.rotation.x = Math.sin(t * 0.8 + mesh.userData.phase) * 0.02;
                    });

                    // Anything an action does not drive relaxes back on its own.
                    if (!action || action.name !== 'bow') {
                        torsoGroup.rotation.x += (0 - torsoGroup.rotation.x) * 0.1;
                        headGroup.rotation.x += (0 - headGroup.rotation.x) * 0.1;
                    }
                    if (!action || action.name !== 'spin') {
                        character.rotation.y += (0 - character.rotation.y) * 0.1;
                        torsoGroup.rotation.z += (0 - torsoGroup.rotation.z) * 0.1;
                    }
                    swoosh.material.opacity *= 0.88;

                    if (action) {
                        action.clock += dt;
                        const p = Math.min(1, action.clock / ACTIONS[action.name].duration);
                        if (action.name === 'draw') poseDraw(p);
                        else if (action.name === 'spin') poseSpin(p);
                        else if (action.name === 'bow') poseBow(p);
                        if (p >= 1) endAction();
                    } else if (stanceOn) {
                        poseStanceIdle();
                    } else {
                        poseRelaxedIdle();
                    }

                    const active = !!action;
                    teal.intensity = 1.2 + Math.sin(t * 2.2) * 0.25 + (active ? 0.6 : 0) + (stanceOn ? 0.3 : 0);
                    glowRing.material.opacity = 0.25 + Math.sin(t * 1.6) * 0.06 + (active ? 0.15 : 0);

                    // Clamped so a tab left in the background — where requestAnimationFrame
                    // stops — does not swing the camera through a huge jump the moment it is
                    // focused again.
                    const orbitDt = Math.min(0.1, (performance.now() - autoOrbitLast) / 1000);
                    autoOrbitLast = performance.now();
                    if (!isDragging && autoOrbit) {
                        theta += AUTO_ORBIT_RATE * orbitDt;
                        updateCamera();
                    }

                    renderer.render(scene, camera);

                    if (!firstFrameShown) {
                        firstFrameShown = true;
                        loadingOverlay.classList.add('hidden');
                        setTimeout(() => loadingOverlay.remove(), 600);
                    }
                }

                applyAura();
                animate();

            } catch (err) {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) {
                    overlay.querySelector('.loaderRing').style.display = 'none';
                    overlay.querySelector('.loaderText').textContent =
                        'Could not start the 3D scene on this device (' + (err && err.message ? err.message : err) + '). Try a different browser, or make sure hardware acceleration / WebGL is enabled.';
                    overlay.classList.remove('hidden');
                }
                console.error('3D Ninja Mimileni failed to start:', err);
            }

            // =====================================================================
            // Lesson deck
            // =====================================================================
            let lessonInited = false;
            let lessonAnimating = false;
            let lessonRAF = null;
            let lessonScene, lessonCamera, lessonRenderer, lessonMesh, lessonMaterial;
            let lessonTheta = 0.4,
                lessonPhi = 1.1,
                lessonRadius = 4.2;
            let lessonDragging = false,
                lessonPrevX = 0,
                lessonPrevY = 0;

            function lessonHolderEl() {
                return document.getElementById('lessonCanvasHolder');
            }

            function lessonSize() {
                const h = lessonHolderEl();
                return {
                    w: h.clientWidth || 320,
                    h: h.clientHeight || 240
                };
            }

            function initLessonDemo() {
                if (lessonInited) return;
                lessonInited = true;
                const h = lessonHolderEl();
                const s = lessonSize();

                lessonScene = new THREE.Scene();
                lessonCamera = new THREE.PerspectiveCamera(45, s.w / s.h, 0.1, 100);
                lessonCamera.position.set(0, 1, 4.2);

                lessonRenderer = new THREE.WebGLRenderer({
                    antialias: true,
                    alpha: true,
                    powerPreference: 'default',
                    failIfMajorPerformanceCaveat: false
                });
                lessonRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                lessonRenderer.setSize(s.w, s.h);
                h.appendChild(lessonRenderer.domElement);

                const light = new THREE.DirectionalLight(0xffffff, 1.2);
                light.position.set(3, 5, 2);
                lessonScene.add(light);
                lessonScene.add(new THREE.AmbientLight(0x223344, 0.8));

                const colors = [0x35d0ff, 0xe9382a, 0xffd23f, 0x3fae4a, 0xb15fff];
                let colorIndex = 0;
                lessonMaterial = new THREE.MeshStandardMaterial({
                    color: colors[0],
                    roughness: 0.4,
                    metalness: 0.15
                });

                const shapeFns = [
                    () => new THREE.SphereGeometry(1, 32, 32),
                    () => new THREE.BoxGeometry(1.4, 1.4, 1.4),
                    () => new THREE.ConeGeometry(1, 1.7, 24),
                    () => new THREE.TorusGeometry(0.9, 0.32, 16, 40),
                ];
                let shapeIndex = 0;
                lessonMesh = new THREE.Mesh(shapeFns[0](), lessonMaterial);
                lessonScene.add(lessonMesh);

                function updateLessonCamera() {
                    lessonPhi = Math.max(0.3, Math.min(1.4, lessonPhi));
                    lessonRadius = Math.max(2, Math.min(9, lessonRadius));
                    lessonCamera.position.x = lessonRadius * Math.sin(lessonPhi) * Math.sin(lessonTheta);
                    lessonCamera.position.z = lessonRadius * Math.sin(lessonPhi) * Math.cos(lessonTheta);
                    lessonCamera.position.y = lessonRadius * Math.cos(lessonPhi) + 0.3;
                    lessonCamera.lookAt(0, 0, 0);
                }
                updateLessonCamera();

                function down(x, y) {
                    lessonDragging = true;
                    lessonPrevX = x;
                    lessonPrevY = y;
                }

                function move(x, y) {
                    if (!lessonDragging) return;
                    lessonTheta -= (x - lessonPrevX) * 0.008;
                    lessonPhi -= (y - lessonPrevY) * 0.008;
                    updateLessonCamera();
                    lessonPrevX = x;
                    lessonPrevY = y;
                }

                function up() {
                    lessonDragging = false;
                }
                h.addEventListener('mousedown', e => down(e.clientX, e.clientY));
                window.addEventListener('mousemove', e => move(e.clientX, e.clientY));
                window.addEventListener('mouseup', up);
                h.addEventListener('touchstart', e => {
                    if (e.touches.length === 1) down(e.touches[0].clientX, e.touches[0].clientY);
                }, {
                    passive: true
                });
                h.addEventListener('touchmove', e => {
                    if (e.touches.length === 1) move(e.touches[0].clientX, e.touches[0].clientY);
                }, {
                    passive: true
                });
                h.addEventListener('touchend', up);
                h.addEventListener('wheel', e => {
                    e.preventDefault();
                    lessonRadius += e.deltaY * 0.006;
                    updateLessonCamera();
                }, {
                    passive: false
                });

                window.addEventListener('resize', () => {
                    if (!lessonInited) return;
                    const sz = lessonSize();
                    lessonCamera.aspect = sz.w / sz.h;
                    lessonCamera.updateProjectionMatrix();
                    lessonRenderer.setSize(sz.w, sz.h);
                });

                document.getElementById('lessonColorBtn').addEventListener('click', () => {
                    colorIndex = (colorIndex + 1) % colors.length;
                    lessonMaterial.color.setHex(colors[colorIndex]);
                });
                document.getElementById('lessonShapeBtn').addEventListener('click', () => {
                    shapeIndex = (shapeIndex + 1) % shapeFns.length;
                    lessonMesh.geometry.dispose();
                    lessonMesh.geometry = shapeFns[shapeIndex]();
                });
                document.getElementById('lessonWireBtn').addEventListener('click', () => {
                    lessonMaterial.wireframe = !lessonMaterial.wireframe;
                });
            }

            function lessonAnimate() {
                if (!lessonAnimating) return;
                lessonRAF = requestAnimationFrame(lessonAnimate);
                lessonMesh.rotation.y += 0.01;
                lessonMesh.rotation.x += 0.003;
                lessonRenderer.render(lessonScene, lessonCamera);
            }

            function startLessonDemo() {
                initLessonDemo();
                const sz = lessonSize();
                lessonCamera.aspect = sz.w / sz.h;
                lessonCamera.updateProjectionMatrix();
                lessonRenderer.setSize(sz.w, sz.h);
                lessonAnimating = true;
                lessonAnimate();
            }

            function stopLessonDemo() {
                lessonAnimating = false;
                if (lessonRAF) cancelAnimationFrame(lessonRAF);
            }

            const lessonSlidesEl = document.getElementById('lessonSlides');
            const lessonSlideEls = Array.from(lessonSlidesEl.children);
            const lessonTotal = lessonSlideEls.length;
            let lessonCurrent = 0;

            const lessonDotsEl = document.getElementById('lessonDots');
            lessonSlideEls.forEach((_, i) => {
                const d = document.createElement('div');
                d.className = 'lessonDot' + (i === 0 ? ' active' : '');
                d.addEventListener('click', () => lessonGoTo(i));
                lessonDotsEl.appendChild(d);
            });
            const lessonDots = Array.from(lessonDotsEl.children);
            const lessonCounterEl = document.getElementById('lessonCounter');
            const lessonPrevBtn = document.getElementById('lessonPrev');
            const lessonNextBtn = document.getElementById('lessonNext');

            let suppressScrollTracking = false;

            function updateNavUI() {
                lessonDots.forEach((d, idx) => d.classList.toggle('active', idx === lessonCurrent));
                lessonCounterEl.textContent = (lessonCurrent + 1) + ' / ' + lessonTotal;
                lessonPrevBtn.disabled = lessonCurrent === 0;
                lessonNextBtn.disabled = lessonCurrent === lessonTotal - 1;
            }

            function lessonGoTo(i, instant) {
                lessonCurrent = Math.max(0, Math.min(lessonTotal - 1, i));
                suppressScrollTracking = true;
                lessonSlideEls[lessonCurrent].scrollIntoView({
                    behavior: instant ? 'auto' : 'smooth',
                    block: 'start'
                });
                updateNavUI();
                clearTimeout(lessonGoTo._t);
                lessonGoTo._t = setTimeout(() => {
                    suppressScrollTracking = false;
                }, 600);
            }
            lessonPrevBtn.addEventListener('click', () => lessonGoTo(lessonCurrent - 1));
            lessonNextBtn.addEventListener('click', () => lessonGoTo(lessonCurrent + 1));

            const lessonObserver = new IntersectionObserver((entries) => {
                if (suppressScrollTracking) return;
                let best = null;
                entries.forEach((entry) => {
                    if (entry.isIntersecting && (!best || entry.intersectionRatio > best.intersectionRatio)) best = entry;
                });
                if (best) {
                    const idx = lessonSlideEls.indexOf(best.target);
                    if (idx !== -1 && idx !== lessonCurrent) {
                        lessonCurrent = idx;
                        updateNavUI();
                    }
                }
            }, {
                root: lessonSlidesEl.parentElement,
                threshold: [0.25, 0.5, 0.75]
            });

            lessonSlideEls.forEach((slide) => lessonObserver.observe(slide));

            // The Lesson button was removed from the page, so nothing opens this overlay
            // any more. Its wiring below is kept intact (minus the button listener) so the
            // lessons can be brought back by re-adding the button markup — the close
            // button, Escape, and the slide observer all still work if it ever opens.
            const lessonOverlay = document.getElementById('lessonOverlay');
            const lessonCloseBtn = document.getElementById('lessonClose');

            function addCodeScrollHints() {
                lessonSlidesEl.querySelectorAll('pre').forEach((pre) => {
                    const next = pre.nextElementSibling;
                    const hasHint = next && next.classList && next.classList.contains('codeHint');
                    if (pre.scrollWidth > pre.clientWidth + 2) {
                        if (!hasHint) {
                            const hint = document.createElement('div');
                            hint.className = 'codeHint';
                            hint.textContent = '← scroll to see more →';
                            pre.insertAdjacentElement('afterend', hint);
                        }
                    } else if (hasHint) {
                        next.remove();
                    }
                });
            }

            function openLesson() {
                lessonOverlay.classList.add('open');
                requestAnimationFrame(() => {
                    lessonGoTo(lessonCurrent, true);
                    startLessonDemo();
                    addCodeScrollHints();
                });
            }

            function closeLesson() {
                lessonOverlay.classList.remove('open');
                stopLessonDemo();
            }
            lessonCloseBtn.addEventListener('click', closeLesson);
            lessonOverlay.addEventListener('click', (e) => {
                if (e.target === lessonOverlay) closeLesson();
            });

            window.addEventListener('keydown', (e) => {
                if (!lessonOverlay.classList.contains('open')) return;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') lessonGoTo(lessonCurrent + 1);
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') lessonGoTo(lessonCurrent - 1);
                if (e.key === 'Escape') closeLesson();
            });

            window.addEventListener('resize', () => {
                if (lessonOverlay.classList.contains('open')) addCodeScrollHints();
            });

            lessonGoTo(0);

        })();
    </script>
</body>

</html>
