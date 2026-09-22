<!-- Created by 🌷 ˙ᵕ˙⋆｡˚ℛᎥᎿᎯᎫ⋆｡˚✩ ˙ᵕ˙🌷 -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>COME EXPLORE WONDERLAND!!!💃🌟</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Comfortaa:wght@400;600;700&family=Playfair+Display:ital,wght@0,600;1,600&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: fixed;
            /* Prevents bounce scroll on mobile */
        }

        body {
            font-family: "Comfortaa", sans-serif;
            background: #080611;
            color: white;
        }

        .scene {
            position: fixed;
            inset: 0;
            display: none;
            overflow: hidden;
        }

        .scene.active {
            display: block;
            animation: sceneIn 1s ease forwards;
        }

        @keyframes sceneIn {
            from {
                opacity: 0;
                transform: scale(1.04);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .back {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
            border: 1px solid rgba(255, 255, 255, .35);
            background: rgba(0, 0, 0, .25);
            backdrop-filter: blur(10px);
            color: white;
            padding: 8px 14px;
            border-radius: 30px;
            cursor: pointer;
            transition: .3s;
            font-family: inherit;
            font-size: 13px;
        }

        .back:hover {
            background: rgba(255, 255, 255, .15);
            transform: translateY(-2px);
        }

        .vignette {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(circle, transparent 35%, rgba(0, 0, 0, .45) 100%);
            z-index: 90;
        }

        #title {
            display: block;
            background:
                radial-gradient(circle at 50% 45%, #35245b, transparent 35%),
                linear-gradient(135deg, #080611, #170d29 50%, #05040b);
        }

        .title-stars {
            position: absolute;
            inset: 0;
        }

        .star {
            position: absolute;
            width: 3px;
            height: 3px;
            background: white;
            border-radius: 50%;
            animation: twinkle 2s infinite alternate;
        }

        @keyframes twinkle {
            from {
                opacity: .2;
                transform: scale(.7);
            }

            to {
                opacity: 1;
                transform: scale(1.5);
            }
        }

        .title-content {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            z-index: 5;
            padding: 20px;
        }

        .title-content h1 {
            font-family: "Cinzel", serif;
            font-size: clamp(32px, 7vw, 100px);
            letter-spacing: 4px;
            font-weight: 500;
            text-shadow:
                0 0 15px #d4a9ff,
                0 0 40px #8f4cff;
            animation: titleFloat 4s ease-in-out infinite;
        }

        .title-content p {
            margin-top: 18px;
            opacity: .65;
            letter-spacing: 3px;
            font-size: 11px;
            padding: 0 10px;
        }

        .enter {
            margin-top: 35px;
            padding: 12px 30px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, .5);
            background: rgba(255, 255, 255, .07);
            color: white;
            cursor: pointer;
            font-family: inherit;
            letter-spacing: 3px;
            transition: .4s;
            font-size: 14px;
        }

        .enter:hover {
            background: white;
            color: #171020;
            box-shadow: 0 0 35px #c68cff;
            transform: scale(1.08);
        }

        @keyframes titleFloat {
            50% {
                transform: translateY(-10px);
            }
        }

        /* --- WELCOME PAGE --- */
        #welcome-scene {
            background: radial-gradient(circle at center, #1b102b, #07040e 70%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .welcome-title {
            font-family: "Cinzel", serif;
            font-size: clamp(24px, 5vw, 55px);
            letter-spacing: 4px;
            margin-bottom: 15px;
            text-shadow: 0 0 25px rgba(212, 169, 255, 0.4);
        }

        .welcome-subtitle {
            font-size: clamp(12px, 2vw, 18px);
            opacity: 0.7;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        /* --- SUITCASE SCENE --- */
        #suitcase-scene {
            background: radial-gradient(circle at center, #26163b, #0d0718 70%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .suitcase-container {
            position: relative;
            width: 250px;
            height: 180px;
            margin-bottom: 30px;
        }

        .suitcase-body {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 130px;
            background: linear-gradient(135deg, #2dd4bf, #0d9488);
            border-radius: 16px;
            border: 4px solid #115e59;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7), inset 0 0 20px rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .suitcase-stripes {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent 40%, rgba(255, 255, 255, 0.15) 40%, rgba(255, 255, 255, 0.15) 60%, transparent 60%);
        }

        .suitcase-straps {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50px;
            width: 20px;
            background: #78350f;
            border-left: 2px solid #451a03;
            border-right: 2px solid #451a03;
        }

        .suitcase-straps.right {
            left: auto;
            right: 50px;
        }

        .suitcase-handle {
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 22px;
            border: 4px solid #78350f;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
            background: #451a03;
        }

        .suitcase-lock {
            position: absolute;
            top: 45px;
            left: 50%;
            transform: translateX(-50%);
            width: 22px;
            height: 15px;
            background: #fbbf24;
            border: 2px solid #b45309;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .packing-items {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .pack-item {
            position: absolute;
            opacity: 0;
            transition: 1s ease;
        }

        .item1 {
            width: 40px;
            height: 30px;
            background: #f43f5e;
            top: -60px;
            left: 15px;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .item2 {
            width: 35px;
            height: 45px;
            background: #8b5cf6;
            top: -70px;
            right: 20px;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .item3 {
            width: 45px;
            height: 22px;
            background: #fbbf24;
            top: -50px;
            left: 95px;
            border-radius: 6px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .packing .item1 {
            animation: flyIn1 1.2s forwards;
        }

        .packing .item2 {
            animation: flyIn2 1.2s forwards;
        }

        .packing .item3 {
            animation: flyIn3 1.2s forwards;
        }

        @keyframes flyIn1 {
            0% {
                transform: translateY(-50px) scale(0.4) rotate(-20deg);
                opacity: 0;
            }

            100% {
                transform: translateY(45px) translateX(30px) scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        @keyframes flyIn2 {
            0% {
                transform: translateY(-60px) scale(0.4) rotate(20deg);
                opacity: 0;
            }

            100% {
                transform: translateY(50px) translateX(-30px) scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        @keyframes flyIn3 {
            0% {
                transform: translateY(-55px) scale(0.4) rotate(10deg);
                opacity: 0;
            }

            100% {
                transform: translateY(55px) translateX(10px) scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .suitcase-text {
            font-family: "Cinzel", serif;
            font-size: clamp(18px, 3.5vw, 26px);
            letter-spacing: 2px;
            margin-bottom: 20px;
            color: #e2d9f3;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
            text-align: center;
        }

        #doors {
            background: radial-gradient(circle at center, #342445, #100d18 60%, #050409);
            overflow-y: auto;
            /* Allow smooth scrolling if doors wrap on mobile */
        }

        .room-title {
            position: absolute;
            top: 30px;
            width: 100%;
            text-align: center;
            font-family: "Cinzel", serif;
            letter-spacing: 4px;
            font-size: clamp(18px, 4vw, 38px);
            z-index: 10;
        }

        .room {
            position: absolute;
            inset: 90px 20px 20px 20px;
            display: flex;
            flex-wrap: wrap;
            /* Allows doors to drop down nicely on mobile screens */
            align-items: center;
            justify-content: center;
            gap: 12px;
            perspective: 1200px;
            overflow-y: auto;
            padding-bottom: 30px;
        }

        .door {
            position: relative;
            height: 170px;
            width: 100px;
            border-radius: 60px 60px 10px 10px;
            cursor: pointer;
            overflow: hidden;
            transition: .5s cubic-bezier(.2, .8, .2, 1);
            box-shadow:
                inset 0 0 20px rgba(255, 255, 255, .12),
                0 15px 30px rgba(0, 0, 0, .5);
            flex-shrink: 0;
        }

        @media (min-width: 600px) {
            .door {
                height: min(62vh, 450px);
                width: min(14vw, 160px);
                min-width: 80px;
                border-radius: 110px 110px 15px 15px;
            }
        }

        .door:hover {
            transform: translateY(-10px) scale(1.02);
            filter: brightness(1.25);
        }

        .door::before {
            content: "";
            position: absolute;
            inset: 0;
            border: 4px solid rgba(255, 255, 255, .12);
            border-radius: inherit;
            pointer-events: none;
        }

        .door-number {
            position: absolute;
            top: 15px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 11px;
            letter-spacing: 2px;
            opacity: .8;
        }

        .door-label {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-family: "Cinzel", serif;
            font-size: 11px;
            text-shadow: 0 2px 10px black;
        }

        .door1 {
            background: radial-gradient(circle at 30% 30%, #fff5a8 0 3%, transparent 4%), radial-gradient(circle at 75% 25%, #ffb6ed 0 4%, transparent 5%), linear-gradient(135deg, #ff4f91, #7a35d9 45%, #19b8d1);
        }

        .door2 {
            background: linear-gradient(to bottom, #10162f 0%, #27376d 60%, #111326 100%);
        }

        .door3 {
            background: radial-gradient(circle at 70% 15%, #f9f0b0 0 8%, transparent 9%), linear-gradient(160deg, #182747, #07131e);
        }

        .door4 {
            background: radial-gradient(circle at 50% 20%, #ffe3a4, transparent 25%), linear-gradient(150deg, #ffb4c9, #ffd88f 50%, #b8e9df);
        }

        .door5 {
            background: conic-gradient(from 90deg, #6c39c9, #ed5b92, #4bd2b1, #6c39c9);
        }

        .door6 {
            background: radial-gradient(circle at 50% 50%, #ffeb3b 0, #ff9800 60%, #e91e63 100%);
            border: 3px dashed #fff;
        }

        .door1::after {
            content: "★  🎈  ★";
            position: absolute;
            top: 45%;
            width: 100%;
            text-align: center;
            font-size: 16px;
        }

        .door2::after {
            content: "✦  ▪  ✦";
            position: absolute;
            top: 42%;
            width: 100%;
            text-align: center;
            color: #b5caff;
            font-size: 12px;
        }

        .door3::after {
            content: "☾";
            position: absolute;
            top: 38%;
            left: 38%;
            font-size: 32px;
            color: #fff4b0;
        }

        .door4::after {
            content: "🍥";
            position: absolute;
            top: 42%;
            width: 100%;
            text-align: center;
            font-size: 30px;
        }

        .door5::after {
            content: "♠ ♥ ♣ ♦";
            position: absolute;
            top: 45%;
            width: 100%;
            text-align: center;
            font-size: 15px;
        }

        .door6::after {
            content: "STARLISH";
            position: absolute;
            top: 42%;
            width: 100%;
            text-align: center;
            font-size: 12px;
        }

        #circus {
            background: radial-gradient(circle at 50% 25%, #ffed7a, transparent 10%), linear-gradient(#1a0735, #5e126e 55%, #18051e);
        }

        .circus-stars {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(#fff 1px, transparent 1px), radial-gradient(#ffe76b 1px, transparent 1px);
            background-size: 70px 70px, 110px 110px;
            animation: starMove 10s linear infinite;
        }

        @keyframes starMove {
            to {
                background-position: 70px 70px, -110px -110px;
            }
        }

        .circus-title {
            position: absolute;
            top: 12%;
            width: 100%;
            text-align: center;
            font-family: "Cinzel", serif;
            font-size: clamp(24px, 5vw, 70px);
            color: #ffe56c;
            text-shadow: 0 0 20px #ff4ca5;
            padding: 0 10px;
        }

        .big-top {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 800px;
            height: 45vh;
            background: repeating-conic-gradient(from 180deg, #ef426f 0deg 15deg, #ffe45d 15deg 30deg);
            clip-path: polygon(50% 0, 100% 100%, 0 100%);
            border-top: 10px solid #fff4a3;
        }

        .big-top::after {
            content: "";
            position: absolute;
            width: 60px;
            height: 60px;
            background: #fff0a1;
            border-radius: 50%;
            top: -25px;
            left: calc(50% - 30px);
            box-shadow: 0 0 25px #fff08b;
        }

        .circus-lights {
            position: absolute;
            bottom: 15px;
            left: 0;
            width: 100%;
            height: 8px;
            background: repeating-linear-gradient(90deg, #fff 0 15px, transparent 15px 40px);
            box-shadow: 0 0 15px white;
            animation: lightBlink .8s infinite alternate;
        }

        @keyframes lightBlink {
            to {
                filter: hue-rotate(100deg);
                opacity: .5;
            }
        }

        .balloon {
            position: absolute;
            width: 35px;
            height: 45px;
            border-radius: 50%;
            animation: balloonFloat 5s ease-in-out infinite;
        }

        .balloon::after {
            content: "";
            position: absolute;
            width: 1px;
            height: 50px;
            background: rgba(255, 255, 255, .5);
            top: 42px;
            left: 50%;
        }

        .b1 {
            left: 8%;
            top: 28%;
            background: #ff547e;
        }

        .b2 {
            right: 8%;
            top: 35%;
            background: #55d9ff;
            animation-delay: 1s;
        }

        .b3 {
            left: 18%;
            top: 20%;
            background: #ffe15c;
            animation-delay: 2s;
        }

        @keyframes balloonFloat {
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }

        /* --- CITY --- */
        #city {
            background: linear-gradient(#03050f, #0b112c 50%, #04060c);
            overflow: hidden;
        }

        .moon {
            position: absolute;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #fdf8df;
            top: 10%;
            right: 10%;
            box-shadow: 0 0 35px rgba(253, 248, 223, 0.8);
        }

        .cloud {
            position: absolute;
            width: 200px;
            height: 30px;
            border-radius: 50px;
            background: rgba(150, 170, 210, .08);
            filter: blur(2px);
            animation: cloudMove 25s linear infinite;
        }

        .cloud:nth-child(2) {
            top: 25%;
            left: -20%;
        }

        .cloud:nth-child(3) {
            top: 40%;
            left: -40%;
            animation-delay: 8s;
        }

        @keyframes cloudMove {
            to {
                transform: translateX(140vw);
            }
        }

        .detailed-cityscape {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 70%;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            padding: 0 5px;
        }

        .skyscraper {
            position: relative;
            background: #0d1224;
            border-radius: 4px 4px 0 0;
            box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.05);
        }

        .sk1 {
            width: 45px;
            height: 60%;
            background: #0c1021;
        }

        .sk2 {
            width: 60px;
            height: 80%;
            background: #111732;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
        }

        .sk3 {
            width: 35px;
            height: 50%;
            background: #080b17;
            display: none;
        }

        .sk4 {
            width: 70px;
            height: 95%;
            background: #141c3f;
            border-top: 10px solid #1e295b;
        }

        .sk5 {
            width: 50px;
            height: 68%;
            background: #0f152d;
        }

        .sk6 {
            width: 65px;
            height: 85%;
            background: #131a39;
            clip-path: polygon(20% 0, 80% 0, 100% 100%, 0 100%);
            display: none;
        }

        .sk7 {
            width: 45px;
            height: 55%;
            background: #0a0e1e;
        }

        @media (min-width: 600px) {

            .sk3,
            .sk6 {
                display: block;
            }

            .sk1 {
                width: 65px;
            }

            .sk2 {
                width: 85px;
            }

            .sk4 {
                width: 100px;
            }

            .sk5 {
                width: 70px;
            }

            .sk7 {
                width: 60px;
            }
        }

        .building-windows {
            position: absolute;
            inset: 8px 4px;
            background-image: radial-gradient(#ffe082 1px, transparent 1.5px);
            background-size: 10px 14px;
            opacity: 0.8;
        }

        .neon-stripe {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            background: #38bdf8;
            box-shadow: 0 0 8px #38bdf8;
        }

        .flying-car {
            position: absolute;
            top: 35%;
            left: -100px;
            width: 35px;
            height: 8px;
            background: #f43f5e;
            border-radius: 10px;
            box-shadow: 0 0 10px #f43f5e;
            animation: flyCar 8s linear infinite;
        }

        .flying-car::before {
            content: "";
            position: absolute;
            top: -2px;
            left: 8px;
            width: 16px;
            height: 5px;
            background: #38bdf8;
            border-radius: 4px;
        }

        @keyframes flyCar {
            0% {
                transform: translateX(0) translateY(0);
                opacity: 0;
            }

            20% {
                opacity: 1;
            }

            80% {
                opacity: 1;
            }

            100% {
                transform: translateX(120vw) translateY(-30px);
                opacity: 0;
            }
        }

        .city-road {
            position: absolute;
            bottom: 0;
            height: 10%;
            width: 100%;
            background: #05070d;
            box-shadow: inset 0 5px 20px #1e295b;
            z-index: 5;
        }

        .city-lamp {
            position: absolute;
            bottom: 8%;
            width: 3px;
            height: 140px;
            background: #1e295b;
            z-index: 6;
            display: none;
        }

        @media (min-width: 600px) {
            .city-lamp {
                display: block;
            }
        }

        .city-lamp::before {
            content: "";
            position: absolute;
            width: 22px;
            height: 22px;
            background: #fff2a5;
            border-radius: 50%;
            top: -8px;
            left: -10px;
            box-shadow: 0 0 20px #ffe994;
        }

        .lamp1 {
            left: 22%;
        }

        .lamp2 {
            right: 25%;
        }

        /* --- RAIN SCENE --- */
        #rain {
            background: radial-gradient(circle at 70% 16%, #fff5b4 0 5%, transparent 6%), linear-gradient(#071326, #0c2031 55%, #061017);
        }

        .rain-moon {
            position: absolute;
            top: 10%;
            right: 12%;
            width: 80px;
            height: 80px;
            background: #f5edba;
            border-radius: 50%;
            box-shadow: 0 0 40px rgba(255, 240, 160, .6);
        }

        .rainfall {
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(105deg, transparent 0 18px, rgba(160, 210, 255, .28) 19px 20px, transparent 21px 35px);
            animation: rain 0.45s linear infinite;
        }

        @keyframes rain {
            to {
                background-position: 0 45px;
            }
        }

        .ground {
            position: absolute;
            bottom: 0;
            height: 20%;
            width: 100%;
            background: #061316;
        }

        .tree {
            position: absolute;
            bottom: 12%;
            left: 8%;
            width: 70px;
            height: 350px;
            background: #11100e;
            border-radius: 50% 50% 5% 5%;
            transform: rotate(5deg);
        }

        .tree::before,
        .tree::after {
            content: "";
            position: absolute;
            width: 200px;
            height: 80px;
            background: #0c1715;
            border-radius: 50%;
            top: -25px;
        }

        .tree::before {
            left: -90px;
        }

        .tree::after {
            left: 0;
            top: -60px;
        }

        .small-girl {
            position: absolute;
            bottom: 12%;
            left: 50%;
            transform: translateX(-50%) scale(0.55);
            width: 150px;
            height: 300px;
            transform-origin: bottom center;
        }

        .girl-head {
            position: absolute;
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: #d99b78;
            left: 45px;
            top: 20px;
            box-shadow: inset -3px -3px rgba(0, 0, 0, 0.1);
        }

        .girl-hair {
            position: absolute;
            width: 95px;
            height: 135px;
            border-radius: 50%;
            background: #21151c;
            left: 27px;
            top: 5px;
        }

        .girl-face-details {
            position: absolute;
            top: 22px;
            left: 10px;
            width: 38px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .girl-eyes {
            font-size: 11px;
            letter-spacing: 6px;
            color: #111;
            margin-bottom: 4px;
        }

        .girl-smile {
            width: 8px;
            height: 4px;
            border-bottom: 2px solid #8b4513;
            border-radius: 0 0 50% 50%;
        }

        .girl-body {
            position: absolute;
            width: 115px;
            height: 160px;
            background: #443b70;
            left: 20px;
            top: 78px;
            border-radius: 55px 55px 10px 10px;
        }

        .book {
            position: absolute;
            width: 105px;
            height: 65px;
            background: #d5bd8e;
            left: 23px;
            top: 170px;
            transform: rotate(-8deg);
            box-shadow: 0 0 15px rgba(255, 220, 140, .2);
        }

        .pen {
            position: absolute;
            width: 15px;
            height: 3px;
            background: #facc15;
            top: 195px;
            left: 65px;
            transform: rotate(-30deg);
            animation: writeMotion 1.5s ease-in-out infinite alternate;
        }

        @keyframes writeMotion {
            0% {
                transform: translateY(0) rotate(-30deg);
            }

            100% {
                transform: translateY(4px) translateX(-4px) rotate(-20deg);
            }
        }

        .firefly {
            position: absolute;
            width: 5px;
            height: 5px;
            background: #d9ff9d;
            border-radius: 50%;
            box-shadow: 0 0 15px #d9ff9d;
            animation: firefly 3s ease-in-out infinite;
        }

        @keyframes firefly {
            50% {
                transform: translate(20px, -20px);
                opacity: .3;
            }
        }

        /* --- SUGAR WORLD --- */
        #sweet {
            background: linear-gradient(#fbcfe8, #fef08a 55%, #99f6e4);
            overflow: hidden;
        }

        .sweet-cloud-cotton {
            position: absolute;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 100px;
            filter: blur(1px);
            box-shadow: 0 10px 30px rgba(255, 105, 180, 0.2);
            animation: sweetCloud 14s linear infinite;
        }

        .sc1 {
            width: 180px;
            height: 70px;
            top: 8%;
            left: -200px;
        }

        .sc2 {
            width: 220px;
            height: 80px;
            top: 22%;
            left: -300px;
            animation-delay: 6s;
        }

        @keyframes sweetCloud {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(130vw);
            }
        }

        .candy-hill {
            position: absolute;
            bottom: -10%;
            width: 100%;
            height: 50%;
            background: #f472b6;
            border-radius: 50% 50% 0 0;
        }

        .lollipop-tree {
            position: absolute;
            bottom: 25%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .lollipop-stick {
            width: 8px;
            height: 120px;
            background: linear-gradient(90deg, #fff, #cbd5e1);
            border-radius: 4px;
        }

        .lollipop-candy {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: repeating-radial-gradient(circle, #f43f5e 0 6px, #ffffff 6px 12px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .lollipop-tree.lp1 {
            left: 8%;
        }

        .lollipop-tree.lp2 {
            right: 8%;
            bottom: 28%;
            transform: scale(0.85);
        }

        .cinnamon {
            position: absolute;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: repeating-radial-gradient(circle, #c8783c 0 6px, #f2bd72 6px 12px);
            box-shadow: 0 8px 20px rgba(120, 60, 50, .25);
        }

        .cinnamon:nth-child(1) {
            left: 25%;
            bottom: 18%;
            transform: scale(0.65) rotate(-12deg);
        }

        .detailed-penguin {
            position: absolute;
            bottom: 15%;
            right: 28%;
            width: 70px;
            height: 100px;
            background: #1e1b4b;
            border-radius: 35px 35px 25px 25px;
            animation: waddle 2s ease-in-out infinite;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .penguin-belly {
            position: absolute;
            bottom: 6px;
            left: 9px;
            width: 52px;
            height: 70px;
            background: #f8fafc;
            border-radius: 50% 50% 25px 25px;
        }

        .penguin-face {
            position: absolute;
            top: 18px;
            left: 15px;
            width: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            color: #1e1b4b;
        }

        .penguin-beak {
            position: absolute;
            top: 30px;
            left: 30px;
            width: 10px;
            height: 6px;
            background: #f59e0b;
            border-radius: 50%;
        }

        @keyframes waddle {

            0%,
            100% {
                transform: rotate(-4deg) translateX(0);
            }

            50% {
                transform: rotate(4deg) translateX(10px);
            }
        }

        .sweet-title {
            position: absolute;
            top: 10%;
            width: 100%;
            text-align: center;
            color: #831843;
            font-family: "Playfair Display", serif;
            font-size: clamp(24px, 5vw, 70px);
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.6);
            padding: 0 10px;
        }

        /* --- WONDERLAND --- */
        #wonderland {
            background: radial-gradient(circle at center, #7454a8, #24143d 60%, #08050d);
        }

        .wonder-bg {
            position: absolute;
            inset: 0;
            background: radial-gradient(#ffcf70 1px, transparent 2px);
            background-size: 45px 45px;
            animation: wonderMove 8s linear infinite;
        }

        @keyframes wonderMove {
            to {
                background-position: 45px 45px;
            }
        }

        .mushroom {
            position: absolute;
            bottom: 10%;
            width: 100px;
            height: 130px;
        }

        .mushroom::before {
            content: "";
            position: absolute;
            bottom: 0;
            left: 35px;
            width: 30px;
            height: 90px;
            background: #f4d9a3;
            border-radius: 15px;
        }

        .mushroom::after {
            content: "";
            position: absolute;
            top: 0;
            width: 100px;
            height: 60px;
            border-radius: 50px 50px 15px 15px;
            background: #e75d80;
            box-shadow: inset 20px 15px #fff0ca, inset -30px 20px #8f3f78;
        }

        .m1 {
            left: 5%;
            transform: scale(1.1);
        }

        .m2 {
            right: 5%;
            transform: scale(.7);
        }

        .m3 {
            left: 40%;
            transform: scale(.45);
            display: none;
        }

        @media (min-width: 600px) {
            .m3 {
                display: block;
            }
        }

        .clock {
            position: absolute;
            top: 15%;
            left: 50%;
            transform: translateX(-50%);
            width: 75px;
            height: 75px;
            border: 6px solid #e9d69c;
            border-radius: 50%;
            background: #291a39;
            box-shadow: 0 0 20px #e9b85d;
            animation: clockSpin 12s linear infinite;
        }

        .clock::before {
            content: "";
            position: absolute;
            width: 2px;
            height: 26px;
            background: #fff0b0;
            left: 34px;
            top: 10px;
            transform-origin: bottom;
            transform: rotate(35deg);
        }

        .clock::after {
            content: "";
            position: absolute;
            width: 2px;
            height: 20px;
            background: #fff0b0;
            left: 34px;
            top: 16px;
            transform-origin: bottom;
            transform: rotate(115deg);
        }

        @keyframes clockSpin {
            from {
                filter: hue-rotate(0deg);
            }

            to {
                filter: hue-rotate(360deg);
            }
        }

        /* --- SHOWER SCENE --- */
        #shower {
            background: linear-gradient(#112233, #0b131a);
        }

        .bathroom-tiles {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .shower-head {
            position: absolute;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            width: 25px;
            height: 12px;
            background: #b0c4de;
            border-radius: 4px;
        }

        .shower-stream {
            position: absolute;
            top: 15%;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 60vh;
            background: repeating-linear-gradient(0deg,
                    transparent 0 10px,
                    rgba(173, 216, 230, 0.4) 10px 15px);
            animation: waterFall 0.3s linear infinite;
        }

        @keyframes waterFall {
            to {
                background-position: 0 30px;
            }
        }

        .shower-person {
            position: absolute;
            bottom: 10%;
            left: 50%;
            transform: translateX(-50%) scale(0.85);
            width: 140px;
            height: 300px;
            transform-origin: bottom center;
        }

        .shower-body {
            position: absolute;
            bottom: 0;
            left: 25px;
            width: 90px;
            height: 150px;
            background: #4c566a;
            border-radius: 40px 40px 10px 10px;
        }

        .human-head {
            position: absolute;
            top: 50px;
            left: 39px;
            width: 62px;
            height: 75px;
            background: #e5c198;
            border-radius: 35px;
            box-shadow: inset -5px -5px rgba(0, 0, 0, 0.1);
            transform: rotate(-5deg);
        }

        .human-hair {
            position: absolute;
            top: -5px;
            left: -6px;
            width: 74px;
            height: 45px;
            background: #3b2219;
            border-radius: 35px 35px 15px 15px;
        }

        .human-eyes {
            position: absolute;
            top: 32px;
            left: 14px;
            width: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            color: #2e3440;
        }

        .human-eyebrows {
            position: absolute;
            top: 22px;
            left: 12px;
            width: 34px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            color: #991b1b;
        }

        .human-nose {
            position: absolute;
            top: 42px;
            left: 28px;
            width: 6px;
            height: 8px;
            background: #d4a373;
            border-radius: 3px;
        }

        .human-mouth {
            position: absolute;
            top: 55px;
            left: 21px;
            width: 20px;
            height: 10px;
            background: #581813;
            border-radius: 4px;
        }

        .speech-bubble {
            position: absolute;
            top: 12vh;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            color: #0f172a;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: bold;
            font-family: "Comfortaa", sans-serif;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
            animation: bubbleBounce 2s ease-in-out infinite alternate;
            z-index: 50;
            white-space: nowrap;
            max-width: 90vw;
            text-align: center;
        }

        @media (min-width: 600px) {
            .speech-bubble {
                top: 5vh;
                left: calc(50% + 40px);
                transform: none;
                font-size: 14px;
            }
        }

        .speech-bubble::after {
            content: "";
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 8px 8px 0;
            border-style: solid;
            border-color: #ffffff transparent;
            display: block;
            width: 0;
        }

        @media (min-width: 600px) {
            .speech-bubble::after {
                left: 20px;
                transform: none;
            }
        }

        @keyframes bubbleBounce {
            from {
                transform: translateY(0) translateX(-50%);
            }

            to {
                transform: translateY(-6px) translateX(-50%);
            }
        }

        @media (min-width: 600px) {
            @keyframes bubbleBounce {
                from {
                    transform: translateY(0);
                }

                to {
                    transform: translateY(-6px);
                }
            }
        }
    </style>
</head>

<body>

    <div id="title" class="scene active">
        <div class="title-stars">
            <div class="star" style="top:20%; left:30%;"></div>
            <div class="star" style="top:60%; left:80%;"></div>
            <div class="star" style="top:40%; left:70%;"></div>
            <div class="star" style="top:80%; left:20%;"></div>
        </div>
        <div class="title-content">
            <h1>What My Mind Is</h1>
            <p>A VISUAL JOURNEY INTO IMAGINATION</p>
            <button class="enter" onclick="switchScene('welcome-scene')">ENTER</button>
        </div>
    </div>

    <!-- Welcome Page -->
    <div id="welcome-scene" class="scene">
        <div class="welcome-title">Welcome to the Mind</div>
        <div class="welcome-subtitle">PREPARE FOR YOUR VOYAGE</div>
        <button class="enter" onclick="switchScene('suitcase-scene')">START</button>
    </div>

    <!-- Suitcase Packing Scene -->
    <div id="suitcase-scene" class="scene">
        <div class="suitcase-text">PACK UR SUITCASES!</div>
        <div id="suitcaseBox" class="suitcase-container">
            <div class="packing-items">
                <div class="pack-item item1"></div>
                <div class="pack-item item2"></div>
                <div class="pack-item item3"></div>
            </div>
            <div class="suitcase-body">
                <div class="suitcase-stripes"></div>
                <div class="suitcase-straps"></div>
                <div class="suitcase-straps right"></div>
                <div class="suitcase-handle"></div>
                <div class="suitcase-lock"></div>
            </div>
        </div>
        <button id="packBtn" class="enter" onclick="packAndFly()">PACK & FLY</button>
    </div>

    <div id="doors" class="scene">
        <div class="room-title">CHOOSE A REALM</div>
        <div class="room">
            <div class="door door1" onclick="switchScene('circus')">
                <div class="door-number">01</div>
                <div class="door-label">Circus</div>
            </div>
            <div class="door door2" onclick="switchScene('city')">
                <div class="door-number">02</div>
                <div class="door-label">Midnight City</div>
            </div>
            <div class="door door3" onclick="switchScene('rain')">
                <div class="door-number">03</div>
                <div class="door-label">Rainy Night</div>
            </div>
            <div class="door door4" onclick="switchScene('sweet')">
                <div class="door-number">04</div>
                <div class="door-label">Sweet World</div>
            </div>
            <div class="door door5" onclick="switchScene('wonderland')">
                <div class="door-number">05</div>
                <div class="door-label">Wonderland</div>
            </div>
            <div class="door door6" onclick="switchScene('shower')">
                <div class="door-number">06</div>
                <div class="door-label">SPECIAL</div>
            </div>
        </div>
    </div>

    <div id="circus" class="scene">
        <button class="back" onclick="switchScene('doors')">← Back</button>
        <div class="circus-stars"></div>
        <div class="circus-title">The Grand Circus</div>
        <div class="balloon b1"></div>
        <div class="balloon b2"></div>
        <div class="balloon b3"></div>
        <div class="big-top">
            <div class="circus-lights"></div>
        </div>
    </div>

    <div id="city" class="scene">
        <button class="back" onclick="switchScene('doors')">← Back</button>
        <div class="moon"></div>
        <div class="cloud" style="top:15%;"></div>
        <div class="cloud"></div>
        <div class="flying-car"></div>
        <div class="detailed-cityscape">
            <div class="skyscraper sk1">
                <div class="building-windows"></div>
            </div>
            <div class="skyscraper sk2">
                <div class="building-windows"></div>
                <div class="neon-stripe"></div>
            </div>
            <div class="skyscraper sk3">
                <div class="building-windows"></div>
            </div>
            <div class="skyscraper sk4">
                <div class="building-windows"></div>
                <div class="neon-stripe"></div>
            </div>
            <div class="skyscraper sk5">
                <div class="building-windows"></div>
            </div>
            <div class="skyscraper sk6">
                <div class="building-windows"></div>
                <div class="neon-stripe"></div>
            </div>
            <div class="skyscraper sk7">
                <div class="building-windows"></div>
            </div>
        </div>
        <div class="city-road"></div>
        <div class="city-lamp lamp1"></div>
        <div class="city-lamp lamp2"></div>
    </div>

    <div id="rain" class="scene">
        <button class="back" onclick="switchScene('doors')">← Back</button>
        <div class="rain-moon"></div>
        <div class="rainfall"></div>
        <div class="tree"></div>
        <div class="ground"></div>
        <div class="small-girl">
            <div class="girl-hair"></div>
            <div class="girl-head">
                <div class="girl-face-details">
                    <div class="girl-eyes">• •</div>
                    <div class="girl-smile"></div>
                </div>
            </div>
            <div class="girl-body"></div>
            <div class="book"></div>
            <div class="pen"></div>
        </div>
        <div class="firefly" style="top:50%; left:40%;"></div>
        <div class="firefly" style="top:65%; left:60%; animation-delay: 1s;"></div>
    </div>

    <div id="sweet" class="scene">
        <button class="back" onclick="switchScene('doors')">← Back</button>
        <div class="sweet-cloud-cotton sc1"></div>
        <div class="sweet-cloud-cotton sc2"></div>
        <div class="sweet-title">Realm of Sugar</div>
        <div class="candy-hill">
            <div class="lollipop-tree lp1">
                <div class="lollipop-candy"></div>
                <div class="lollipop-stick"></div>
            </div>
            <div class="lollipop-tree lp2">
                <div class="lollipop-candy" style="background: repeating-radial-gradient(circle, #38bdf8 0 6px, #ffffff 6px 12px);"></div>
                <div class="lollipop-stick"></div>
            </div>
            <div class="cinnamon"></div>
            <div class="detailed-penguin">
                <div class="penguin-belly"></div>
                <div class="penguin-face"><span>•</span><span>•</span></div>
                <div class="penguin-beak"></div>
            </div>
        </div>
    </div>

    <div id="wonderland" class="scene">
        <button class="back" onclick="switchScene('doors')">← Back</button>
        <div class="wonder-bg"></div>
        <div class="clock"></div>
        <div class="mushroom m1"></div>
        <div class="mushroom m2"></div>
        <div class="mushroom m3"></div>
    </div>

    <div id="shower" class="scene">
        <button class="back" onclick="switchScene('doors')">← Back</button>
        <div class="bathroom-tiles"></div>
        <div class="speech-bubble">HEY, WATCH WHERE UR GOING! 😡</div>
        <div class="shower-head"></div>
        <div class="shower-stream"></div>
        <div class="shower-person">
            <div class="shower-body"></div>
            <div class="human-head">
                <div class="human-hair"></div>
                <div class="human-eyebrows"><span>></span><span>&lt;</span></div>
                <div class="human-eyes"><span>x</span><span>x</span></div>
                <div class="human-nose"></div>
                <div class="human-mouth"></div>
            </div>
        </div>
    </div>

    <script>
        function switchScene(sceneId) {
            const scenes = document.querySelectorAll('.scene');
            scenes.forEach(scene => scene.classList.remove('active'));

            const targetScene = document.getElementById(sceneId);
            if (targetScene) {
                targetScene.classList.add('active');
            }
        }

        function packAndFly() {
            const box = document.getElementById('suitcaseBox');
            const btn = document.getElementById('packBtn');

            btn.style.opacity = '0';
            box.classList.add('packing');

            setTimeout(() => {
                switchScene('doors');
                box.classList.remove('packing');
                btn.style.opacity = '1';
            }, 1200);
        }
    </script>
</body>

</html>
