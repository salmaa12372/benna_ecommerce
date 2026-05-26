<?php
session_start();

if (!defined('BASE')) {
    define('BASE', '/benafinal');
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/app.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Inter:wght@300;400;500&display=swap"
        rel="stylesheet">
</head>
<style>* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    background: #e9e9e9;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}

/* PAGE */
.error-page {
    max-width: 700px;
}

/* 4  LOGO  4 */
.error-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
}

/* digits 4 */
.digit {
    font-size: 180px;
    font-weight: 700;
    color: rgba(180, 120, 120, 0.6);
    font-family: 'Playfair Display', serif;
}

/* logo in middle (the 0) */
.logo-box {
    width: 150px;
height: 140px;                                                                                                                                                                                                          
    border-radius: 50%;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-box img {
    width: 200px;
    height: 200px;
        top: 20px;

    bottom: 20px;
    object-fit: contain;
    border-radius: 50%;
}

/* TEXT */
h1 {
    font-size: 26px;
    margin-bottom: 10px;
    color: #2c2c2c;
}

p {
    color: #555;
    margin-bottom: 25px;
}

/* BUTTON */
.btn {
    display: inline-block;
    padding: 12px 28px;
    background: #2c2c2c;
    color: white;
    text-decoration: none;
    border-radius: 30px;
    transition: 0.3s;
}

.btn:hover {
    background: #e63946;
    transform: scale(1.05);
}









.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 84px;
    z-index: 999;
    background: transparent;
    transition: background .4s, box-shadow .4s, border-color .4s;
}



.nav-container {
    height: 100%;
    max-width: 2000px;
    flex-wrap: wrap;

    margin: 0 auto;
    padding: 0 54px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.nav-logo {
    font-family: var(--font-display);
    font-size: 1.8rem;
    font-weight: 700;
    letter-spacing: .05em;
    color: green;
    display: flex;
    align-items: center;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
    transition: color .3s, text-shadow .3s;
}


.nav-logo-img {
    height: 52px;
    width: auto;
    margin-right: 10px;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 40px;
}

.nav-links2 {
    display: flex;
    align-items: center;
    gap: 40px;
}

.nav-links a {
    color: rgba(255, 255, 255, 0.85);
    font-size: 1rem;
    letter-spacing: .02em;
    transition: color .2s;
    text-shadow: 0 1px 6px rgba(0, 0, 0, 0.35);
}

.nav-links2 a {
    color: rgba(255, 255, 255, 0.85);
    font-size: 1rem;
    letter-spacing: .02em;
    transition: color .2s;
    text-shadow: 0 1px 6px rgba(0, 0, 0, 0.35);
}


.navbar.scrolled .nav-links a {
    color: color-mix(in oklab, var(--fg) 70%, transparent);
    text-shadow: none;
}

.navbar.scrolled .nav-links2 a {
    color: color-mix(in oklab, var(--fg) 70%, transparent);
    text-shadow: none;
}

.nav-links a:hover {
    color: var(--gold-light);
    font-size: 1.2rem;
    border-bottom: 2px solid var(--gold-light);
}

.nav-links2 a:hover {
    color: var(--gold-light);
    font-size: 1.2rem;
}

.navbar.scrolled .nav-links a:hover {
    color: var(--green);
}

.navbar.scrolled .nav-links2 a:hover {
    color: var(--green);
}



.nav-join {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 16px;
    font-size: 0.72rem;
    letter-spacing: .06em;
    font-family: var(--font-display);
    font-weight: 700;
    text-transform: uppercase;
    color: var(--join-text);
    background: var(--join-bg);
    border: 1px solid var(--join-border);
    border-radius: 8px;
    white-space: nowrap;
    overflow: hidden;
    transition: transform .25s, box-shadow .25s, filter .25s;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
}

.nav-join::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0) 55%);
    opacity: 0;
    pointer-events: none;
    transition: opacity .35s;
}

.nav-join:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px var(--join-hover-shadow);
    filter: saturate(1.12);
}

.nav-join:hover::after {
    opacity: 1;
}

.nav-join span {
    position: absolute;
    border-radius: 50%;
    background: var(--join-star);
    transition: opacity .3s, transform .3s;
    z-index: -1;
    opacity: .2;
}

.nav-join .star-1 {
    top: 20%;
    left: 20%;
    width: 12px;
    height: 12px;
}

.nav-join .star-2 {
    top: 45%;
    left: 45%;
    width: 8px;
    height: 8px;
}

.nav-join .star-3 {
    top: 40%;
    left: 40%;
    width: 5px;
    height: 5px;
}

.nav-join .star-4 {
    top: 20%;
    left: 40%;
    width: 7px;
    height: 7px;
}

.nav-join .star-5 {
    top: 25%;
    left: 45%;
    width: 8px;
    height: 8px;
}

.nav-join .star-6 {
    top: 5%;
    left: 50%;
    width: 5px;
    height: 5px;
}

.nav-join:hover .star-1,
.nav-join:hover .star-2,
.nav-join:hover .star-3,
.nav-join:hover .star-4,
.nav-join:hover .star-5,
.nav-join:hover .star-6 {
    opacity: 0;
    transform: scale(0.6);
}

.nav-toggle {
    display: none;
    width: 46px;
    height: 42px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.12);
    cursor: pointer;
    padding: 10px;
    gap: 6px;
    flex-direction: column;
    justify-content: center;
}

.nav-toggle span {
    display: block;
    width: 100%;
    height: 2px;
    background: white;
    opacity: .9;
}

.navbar.scrolled .nav-toggle {
    border-color: var(--border);
    background: color-mix(in oklab, var(--card) 88%, transparent);
}

.navbar.scrolled .nav-toggle span {
    background: var(--fg);
}

/* ---------- SIMPLE BLACK & WHITE TOGGLE (REPLACED) ---------- */
.toggle-container {
    position: relative;
    width: 76px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
}

.toggle-wrap {
    position: relative;
    width: 100%;
    height: 100%;
}

.toggle-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-track {
    display: block;
    width: 100%;
    height: 100%;
    color: white;
    background: #d1d1d1;
    border-radius: 30px;
    position: relative;
    cursor: pointer;
    transition: background 0.25s ease;
    border: 1px solid #7a7a7a;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}



.toggle-input:checked+.toggle-track {
    background: #2a2a2a;
    border-color: #444;
}

.toggle-input:checked+.toggle-track .toggle-thumb {
    transform: translateX(28px);
}

/* Hide all decorative glitter elements from original toggle */
.track-lines,
.track-line,
.thumb-core,
.thumb-inner,
.thumb-scan,
.thumb-particles,
.thumb-particle,
.toggle-data,
.data-text,
.status-indicator,
.energy-rings,
.energy-ring,
.interface-lines,
.interface-line,
.toggle-reflection,
.holo-glow {
    display: none !important;
}



:root {
    --bg: hsl(90, 100%, 89%);
    --fg: hsl(25, 18%, 14%);
    --card: hsl(90, 69%, 82%);
    --cream: hsl(90, 60%, 95%);
    --cream-dark: hsl(90, 50%, 90%);
    --green: hsl(142, 34%, 28%);
    --green-light: hsl(142, 35%, 40%);
    --green-dark: hsl(142, 38%, 18%);
    --gold: hsl(40, 60%, 52%);
    --gold-light: hsl(40, 52%, 70%);
    --terracotta: hsl(14, 55%, 55%);
    --border: hsl(40, 15%, 85%);
    --muted: hsl(30, 15%, 45%);
    --red: hsl(0, 75%, 45%);
    --shadow: 0 12px 50px rgba(0, 0, 0, 0.08);
    --join-bg: linear-gradient(135deg, #7ec08d, #34714a);
    --join-border: rgba(255, 255, 255, 0.18);
    --join-text: white;
    --join-hover-shadow: rgba(246, 146, 146, 0.35);
    --join-star: rgba(255, 255, 255, 0.95);
    --font-display: "Playfair Display", Georgia, serif;
    --font-body: "Playfair Display", "EB Garamond", system-ui, -apple-system, sans-serif;
}

body.dark {
    --bg: hsl(0, 0%, 4%);
    --fg: hsl(40, 33%, 96%);
    --card: hsl(146, 26%, 7%);
    --cream: hsl(150, 20%, 12%);
    --cream-dark: hsl(0, 0%, 6%);
    --border: hsl(150, 10%, 22%);
    --muted: hsl(40, 10%, 65%);
    --shadow: 0 12px 50px rgba(0, 0, 0, 0.5);
    --join-bg: linear-gradient(135deg, #102e1f, #256446);
    --join-border: rgba(0, 0, 0, 0.8);
    --join-text: #eff9ea;
    --join-hover-shadow: rgba(95, 197, 140, 0.45);
    --join-star: rgba(200, 255, 210, 0.9);
}</style>
<body>



      <section class="error-page">
  <div class="error-wrapper">

    <!-- 4 -->
    <span class="digit">4</span>

    <!-- LOGO au milieu (remplace le 0) -->
    <div class="logo-box">
<img src="<?= BASE ?>/view/pics/logo.png" alt="Logo">    </div>

    <!-- 4 -->
    <span class="digit">4</span>

  </div>

  <h1>Page Not Found</h1>
  <p>The page you are looking for doesn't exist or has been moved.</p>

  <a href="<?= BASE ?>/view/client/home.php" class="btn">Back Home</a>

</section>

</body>

</html>