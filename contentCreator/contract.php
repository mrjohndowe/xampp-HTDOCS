<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shibari | Consent & Safety Agreement</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0b0c12;
            --panel: #161820;
            --panel2: #1d202b;
            --border: #343643;
            --text: #f4f0ed;
            --muted: #a6a5ae;
            --accent: #dd8c83;
            --accent2: #f2b6aa;
            --green: #a4c3a8;
            --shadow: 0 18px 60px #0006
        }

        * {
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            background: radial-gradient(ellipse at 10% 0, #372027 0, transparent 38%), radial-gradient(ellipse at 90% 15%, #192034 0, transparent 30%), var(--bg);
            font: 15px/1.55 system-ui, -apple-system, Segoe UI, sans-serif;
            color: var(--text)
        }

        button,
        input,
        textarea,
        select {
            font: inherit
        }

        button {
            cursor: pointer
        }

        a {
            color: var(--accent2)
        }

        .shell {
            max-width: 1190px;
            margin: auto;
            padding: 32px 24px 100px
        }

        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 26px
        }

        .brand {
            font-size: 12px;
            letter-spacing: .22em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--accent2)
        }

        .top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px
        }

        button {
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 11px 15px;
            background: #292a34;
            color: var(--text);
            font-weight: 650
        }

        button:hover {
            border-color: var(--accent)
        }

        button.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #191115
        }

        button:focus-visible,
        input:focus-visible,
        textarea:focus-visible,
        select:focus-visible {
            outline: 3px solid #dd8c8399;
            outline-offset: 2px
        }

        .hero {
            padding: 44px;
            border: 1px solid #5b4046;
            border-radius: 24px;
            background: linear-gradient(125deg, #332126, #181922 62%);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden
        }

        .hero:after {
            content: '◈';
            position: absolute;
            right: 35px;
            top: 0;
            font-size: 210px;
            color: #ffffff08;
            pointer-events: none
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: .19em;
            font-size: 11px;
            font-weight: 800;
            color: var(--accent2)
        }

        h1 {
            font: 500 clamp(32px, 4vw, 51px)/1.1 Georgia, serif;
            letter-spacing: -.025em;
            margin: 12px 0
        }

        h2 {
            font: 500 25px/1.2 Georgia, serif;
            margin: 0
        }

        h3 {
            font-size: 15px;
            margin: 0 0 12px
        }

        .hero p {
            color: #ddd0d2;
            max-width: 720px;
            margin: 0
        }

        .hero-badges {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            margin-top: 25px
        }

        .badge {
            border: 1px solid #70565b;
            color: #e9c2be;
            padding: 7px 10px;
            border-radius: 99px;
            font-size: 12px
        }

        .layout {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            gap: 24px;
            margin-top: 24px
        }

        .nav {
            position: sticky;
            top: 18px;
            align-self: start;
            background: var(--panel);
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 16px
        }

        .nav a {
            display: block;
            text-decoration: none;
            color: var(--muted);
            padding: 10px 13px;
            border-radius: 9px;
            font-size: 13px
        }

        .nav a:hover {
            color: var(--text);
            background: var(--panel2)
        }

        .content {
            display: grid;
            gap: 20px
        }

        .section {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--panel);
            padding: 27px;
            break-inside: avoid
        }

        .section-title {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 22px
        }

        .number {
            height: 35px;
            min-width: 35px;
            display: grid;
            place-items: center;
            background: #38292e;
            color: var(--accent2);
            font-size: 12px;
            font-weight: 800;
            border-radius: 10px
        }

        .sub {
            color: var(--muted);
            font-size: 13px;
            margin: 6px 0 0
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px
        }

        .field.full {
            grid-column: 1/-1
        }

        label {
            color: #ddd9dc;
            font-size: 13px;
            font-weight: 650
        }

        input[type=text],
        input[type=date],
        input[type=time],
        input[type=tel],
        textarea,
        select {
            width: 100%;
            min-height: 43px;
            background: #101218;
            color: var(--text);
            border: 1px solid #41424b;
            border-radius: 9px;
            padding: 10px 12px
        }

        textarea {
            min-height: 87px;
            resize: vertical
        }

        .note {
            margin: 15px 0 0;
            font-size: 13px;
            color: var(--muted)
        }

        .callout {
            padding: 15px 18px;
            border: 1px solid #72504e;
            border-left: 4px solid var(--accent);
            border-radius: 9px;
            background: #35252a;
            color: #f5dbd8;
            margin: 17px 0
        }

        .check-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px
        }

        .check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-height: 46px;
            padding: 11px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #11131a;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer
        }

        .check input {
            width: 17px;
            height: 17px;
            accent-color: var(--accent);
            flex: 0 0 auto;
            margin: 2px 0 0
        }

        .check strong {
            display: block;
            color: var(--text)
        }

        .rows {
            display: grid;
            gap: 11px
        }

        .row {
            display: flex;
            align-items: flex-start;
            gap: 11px
        }

        .row input {
            accent-color: var(--accent);
            margin-top: 5px;
            width: 17px;
            height: 17px;
            flex-shrink: 0
        }

        .row span {
            font-size: 14px
        }

        .limits {
            display: grid;
            gap: 12px
        }

        .limit-item {
            display: grid;
            grid-template-columns: 1.1fr 160px;
            align-items: center;
            gap: 15px;
            padding: 11px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #11131a
        }

        .limit-item strong {
            display: block;
            font-size: 13px
        }

        .limit-item small {
            color: var(--muted);
            display: block
        }

        .safety-words {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px
        }

        .word {
            border-radius: 12px;
            padding: 15px;
            border: 1px solid var(--border);
            background: #11131a
        }

        .word strong {
            display: block;
            font-size: 17px
        }

        .word p {
            margin: 3px 0 0;
            font-size: 13px;
            color: var(--muted)
        }

        .green strong {
            color: #a5d5af
        }

        .yellow strong {
            color: #ffde91
        }

        .red strong {
            color: #ffb1a8
        }

        .signature {
            background: #101218;
            border: 1px dashed #6d5c60;
            border-radius: 11px;
            padding: 17px
        }

        .signature input {
            font: italic 24px Georgia, serif;
            letter-spacing: .02em
        }

        .fineprint {
            font-size: 12px;
            color: var(--muted)
        }

        .footer {
            margin-top: 20px;
            color: var(--muted);
            font-size: 12px;
            text-align: center
        }

        .status {
            font-size: 12px;
            color: var(--green);
            align-self: center
        }

        .print-head {
            display: none
        }

        .compact {
            margin-top: 12px
        }

        .age {
            display: flex;
            gap: 12px;
            align-items: flex-start
        }

        .age input {
            accent-color: var(--accent);
            width: 19px;
            height: 19px;
            margin-top: 2px
        }

        .age label {
            font-weight: 500
        }

        .muted {
            color: var(--muted)
        }

        @media(max-width:900px) {
            .layout {
                grid-template-columns: 1fr
            }

            .nav {
                position: static;
                display: flex;
                overflow: auto
            }

            .nav a {
                white-space: nowrap
            }

            .shell {
                padding: 20px 14px 55px
            }

            .hero {
                padding: 30px
            }

            .section {
                padding: 20px
            }
        }

        @media(max-width:580px) {
            .top {
                align-items: flex-start;
                flex-direction: column
            }

            .grid,
            .check-grid,
            .safety-words {
                grid-template-columns: 1fr
            }

            .limit-item {
                grid-template-columns: 1fr
            }

            .hero {
                padding: 24px
            }

            .section-title {
                gap: 10px
            }
        }

        @page {
            size: letter;
            margin: 14mm
        }

        @media print {
            * {
                box-shadow: none !important
            }

            html,
            body {
                background: white !important;
                color: #111 !important;
                font-size: 10px;
                line-height: 1.35
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact
            }

            .shell {
                max-width: none;
                padding: 0
            }

            .top,
            .nav,
            .top-actions,
            .footer,
            .no-print {
                display: none !important
            }

            .hero {
                border: 0;
                border-bottom: 2px solid #333;
                border-radius: 0;
                background: white !important;
                color: #111 !important;
                padding: 0 0 14px;
                margin-bottom: 14px
            }

            .hero:after,
            .hero-badges {
                display: none
            }

            .hero h1 {
                color: #111;
                font-size: 26px;
                margin: 4px 0
            }

            .hero p,
            .eyebrow {
                color: #444
            }

            .layout {
                display: block;
                margin-top: 0
            }

            .content {
                display: block
            }

            .section {
                padding: 12px 0;
                border: 0;
                border-bottom: 1px solid #ccc;
                border-radius: 0;
                background: white !important;
                break-inside: auto;
                page-break-inside: auto
            }

            .section-title {
                margin-bottom: 10px
            }

            .number {
                color: #222;
                background: #eee !important;
                height: 25px;
                min-width: 25px
            }

            .section h2 {
                font-size: 17px
            }

            .sub,
            .note,
            .fineprint,
            .muted {
                color: #555 !important
            }

            .grid {
                gap: 7px 15px
            }

            .field {
                gap: 3px
            }

            label,
            .check strong {
                color: #111 !important
            }

            input[type=text],
            input[type=date],
            input[type=time],
            input[type=tel],
            textarea,
            select {
                min-height: 26px;
                padding: 3px 5px;
                border: 1px solid #bbb;
                border-radius: 2px;
                color: #111 !important;
                background: white !important;
                font-size: 10px
            }

            textarea {
                min-height: 38px
            }

            .check-grid,
            .rows,
            .limits {
                gap: 5px
            }

            .check,
            .limit-item,
            .word,
            .signature {
                background: white !important;
                color: #111 !important;
                border: 1px solid #ccc;
                padding: 5px;
                min-height: 0
            }

            .word p,
            .limit-item small {
                color: #444 !important
            }

            .callout {
                background: #f3f3f3 !important;
                color: #111 !important;
                padding: 7px;
                border-color: #aaa;
                margin: 8px 0
            }

            .row span {
                font-size: 10px
            }

            .check input,
            .row input {
                accent-color: #333
            }

            .signature input {
                font-size: 16px
            }

            h3 {
                margin-bottom: 5px
            }

            .print-head {
                display: block;
                color: #555;
                text-align: right;
                font-size: 9px
            }

            .safety-words {
                gap: 5px
            }

            .word strong {
                font-size: 12px;
                color: #111 !important
            }

            .word p {
                font-size: 10px
            }

            .section:last-child {
                border-bottom: 0
            }
        }

        [hidden],
        #mediaPrint {
            display: none !important;
        }

        #mediaDialog {
            width: min(680px, calc(100% - 24px));
            max-height: calc(100dvh - 24px);
            padding: clamp(16px, 4vw, 27px);
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--panel);
            color: var(--text);
            box-shadow: var(--shadow);
            overscroll-behavior: contain;
        }

        #mediaDialog::backdrop {
            background: #090910cc;
        }

        .media-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .media-actions .badge[data-state="complete"] {
            color: var(--green);
        }

        #mediaForm fieldset {
            margin: 16px 0;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        #mediaForm legend {
            font-weight: 650;
            padding: 0 6px;
        }

        .media-error {
            color: #ffb1a8;
        }

        @media print {

            #mediaDialog,
            #mediaDialog::backdrop {
                display: none !important;
            }

            #mediaPrint:not([hidden]) {
                display: block !important;
            }

            #mediaPrint p {
                white-space: pre-wrap;
                overflow-wrap: anywhere;
            }

            #mediaPrint li {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        <header class="top">
            <div class="brand">Rope · Trust · Communication</div>
            <div class="top-actions"><span id="saveStatus" class="status" role="status">Saved locally</span><button
                    type="button" id="resetBtn">Reset form</button><button type="button" class="primary"
                    id="printBtn">Finalize / Print PDF</button></div>
        </header>
        <div class="hero"><span class="eyebrow">Negotiated participation document</span>
            <h1>Shibari Consent<br>& Safety Agreement</h1>
            <p>A practical, editable agreement for two consenting adults. Establish intentions, boundaries, emergency
                steps, and aftercare before any rope activity begins.</p>
            <div class="hero-badges"><span class="badge">18+ adults only</span><span class="badge">Consent can be
                    revoked anytime</span><span class="badge">No suspension by default</span><span class="badge">Private
                    local autosave</span></div>
        </div>
        <div class="layout">
            <nav class="nav no-print" aria-label="Agreement sections"><a href="#parties">01 · Participants</a><a
                    href="#scope">02 · Scope & limits</a><a href="#safety">03 · Safety plan</a><a href="#consent">04 ·
                    Consent rules</a><a href="#privacy">05 · Privacy</a><a href="#aftercare">06 · Aftercare</a><a
                    href="#signatures">07 · Signatures</a></nav>
            <form class="content" id="agreement" autocomplete="on">
                <div class="print-head">Adult consensual rope practice · Confidential personal agreement</div>
                <section class="section" id="parties">
                    <div class="section-title"><span class="number">01</span>
                        <div>
                            <h2>Participants & session</h2>
                            <p class="sub">Complete both participants' identifying and contact information.</p>
                        </div>
                    </div>
                    <div class="grid">
                        <div class="field"><label for="topName">Rope practitioner / rigger</label><input id="topName"
                                name="topName" type="text" placeholder="Full legal name"></div>
                        <div class="field"><label for="bottomName">Rope recipient / model</label><input id="bottomName"
                                name="bottomName" type="text" placeholder="Full legal name"></div>
                        <div class="field"><label for="sessionDate">Session date</label><input id="sessionDate"
                                name="sessionDate" type="date"></div>
                        <div class="field"><label for="sessionTime">Start time</label><input id="sessionTime"
                                name="sessionTime" type="time"></div>
                        <div class="field full"><label for="location">Agreed location</label><input id="location"
                                name="location" type="text" placeholder="Private agreed location"></div>
                        <div class="field"><label for="emergencyContact">Emergency contact name</label><input
                                id="emergencyContact" name="emergencyContact" type="text" placeholder="Contact person">
                        </div>
                        <div class="field"><label for="emergencyPhone">Emergency contact phone</label><input
                                id="emergencyPhone" name="emergencyPhone" type="tel" placeholder="Phone number"></div>
                    </div>
                    <div class="callout">
                        <div class="age"><input id="adults" name="adults" type="checkbox"><label for="adults">Both
                                participants affirm they are at least 18 years old, legally able to consent, sober, and
                                participating voluntarily.</label></div>
                    </div>
                </section>
                <section class="section" id="scope">
                    <div class="section-title"><span class="number">02</span>
                        <div>
                            <h2>Scope, boundaries & activities</h2>
                            <p class="sub">Default settings are conservative. Change any category only after discussion.
                            </p>
                        </div>
                    </div>
                    <div class="limits">
                        <div class="limit-item">
                            <div><strong>Decorative / loose rope positioning</strong><small>Low tension, easy removal,
                                    no weight-bearing.</small></div><select name="decorative"
                                aria-label="Decorative rope permission">
                                <option>Allowed</option>
                                <option>Ask each time</option>
                                <option>Not allowed</option>
                            </select>
                        </div>
                        <div class="limit-item">
                            <div><strong>Restrained wrist or ankle positioning</strong><small>Only with mobility and
                                    sensation checks.</small></div><select name="limb"
                                aria-label="Limb restraint permission">
                                <option>Ask each time</option>
                                <option>Allowed</option>
                                <option>Not allowed</option>
                            </select>
                        </div>
                        <div class="limit-item">
                            <div><strong>Chest or torso rope positioning</strong><small>Breathing must remain completely
                                    unrestricted.</small></div><select name="torso" aria-label="Torso rope permission">
                                <option>Ask each time</option>
                                <option>Allowed</option>
                                <option>Not allowed</option>
                            </select>
                        </div>
                        <div class="limit-item">
                            <div><strong>Blindfolding or sensory restriction</strong><small>Only with an accessible
                                    alternate stop signal.</small></div><select name="blindfold"
                                aria-label="Sensory restriction permission">
                                <option>Not allowed</option>
                                <option>Ask each time</option>
                                <option>Allowed</option>
                            </select>
                        </div>
                        <div class="limit-item">
                            <div><strong>Suspension / partial suspension</strong><small>Advanced, high-risk,
                                    weight-bearing technique.</small></div><select name="suspension"
                                aria-label="Suspension permission">
                                <option>Not allowed</option>
                                <option>Not part of this agreement</option>
                            </select>
                        </div>
                        <div class="limit-item">
                            <div><strong>Neck, throat, or airway pressure</strong><small>Excluded due to severe injury
                                    risk.</small></div><select name="neck" aria-label="Neck pressure permission">
                                <option>Not allowed</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid compact">
                        <div class="field full"><label for="hardLimits">Hard limits (strictly
                                prohibited)</label><textarea id="hardLimits"
                                name="hardLimits">No neck or airway compression; no suspension; no intoxication; no ignoring a stop signal; no tying unattended; no intentional loss of consciousness; no photography or recording without separate permission.</textarea>
                        </div>
                        <div class="field full"><label for="softLimits">Soft limits / discuss before
                                proceeding</label><textarea id="softLimits" name="softLimits"
                                placeholder="List areas that require an explicit check-in each time"></textarea></div>
                        <div class="field full"><label for="healthNotes">Relevant safety information, access needs, and
                                areas to avoid</label><textarea id="healthNotes" name="healthNotes"
                                placeholder="Optional. Share only what is needed to plan safely; avoid unnecessary private health details."></textarea>
                        </div>
                    </div>
                </section>
                <section class="section" id="safety">
                    <div class="section-title"><span class="number">03</span>
                        <div>
                            <h2>Safety & stop signals</h2>
                            <p class="sub">Discuss and practice these signals before starting.</p>
                        </div>
                    </div>
                    <div class="safety-words">
                        <div class="word green"><strong>GREEN</strong>
                            <p>Comfortable; continue at the agreed pace.</p>
                        </div>
                        <div class="word yellow"><strong>YELLOW</strong>
                            <p>Pause or reduce intensity; check in.</p>
                        </div>
                        <div class="word red"><strong>RED</strong>
                            <p>Stop immediately and begin safe release.</p>
                        </div>
                    </div>
                    <div class="grid compact">
                        <div class="field"><label for="customWord">Additional safeword (optional)</label><input
                                id="customWord" name="customWord" type="text" placeholder="Optional custom word"></div>
                        <div class="field"><label for="nonverbal">Nonverbal stop signal</label><input id="nonverbal"
                                name="nonverbal" type="text" value="Three deliberate taps or dropping a held object">
                        </div>
                    </div>
                    <div class="callout"><strong>Immediate stop or release:</strong> Numbness, tingling, sharp pain,
                        weakness, unusual color or temperature changes, dizziness, breathing difficulty, panic, impaired
                        communication, or any request to stop. Call emergency services for serious symptoms or injuries.
                    </div>
                    <h3>Required precautions</h3>
                    <div class="rows"><label class="row"><input type="checkbox" name="shears" ><span>Safety
                                shears are immediately available and both people know where they
                                are.</span></label><label class="row"><input type="checkbox" name="monitor"
                                ><span>The rope recipient is continuously supervised and can communicate
                                throughout.</span></label><label class="row"><input type="checkbox" name="checkins"
                                ><span>Frequent verbal check-ins and sensation, movement, and circulation checks
                                are required.</span></label><label class="row"><input type="checkbox" name="noNeck"
                                ><span>No rope pressure on the neck or airway, no breathing restriction, and no
                                weight-bearing suspension.</span></label><label class="row"><input type="checkbox"
                                name="plan" ><span>Either person may stop at any time, with no explanation,
                                pressure, or penalty.</span></label></div>
                    <p class="note">Rope can cause nerve injury, loss of circulation, falls, or other serious harm even
                        during apparently gentle activity. This form is not a substitute for practical safety training.
                    </p>
                </section>
                <section class="section" id="consent">
                    <div class="section-title"><span class="number">04</span>
                        <div>
                            <h2>Ongoing consent & responsibilities</h2>
                            <p class="sub">A signature is never advance permission for anything.</p>
                        </div>
                    </div>
                    <div class="rows"><label class="row"><input type="checkbox" name="informed" ><span>Both
                                people have discussed the planned activity, foreseeable risks, limits, and release
                                procedure.</span></label><label class="row"><input type="checkbox" name="specific"
                                ><span>Consent is specific to the negotiated activities and must be renewed for
                                any new activity.</span></label><label class="row"><input type="checkbox"
                                name="withdraw" ><span>Consent is continuous and may be withdrawn at any moment,
                                including after signing.</span></label><label class="row"><input type="checkbox"
                                name="noPressure" ><span>There will be no coercion, threats, retaliation, or
                                assumption that silence means consent.</span></label><label class="row"><input
                                type="checkbox" name="noWaiver" ><span>This is a communication record, not a
                                waiver of legal rights, liability, or responsibility.</span></label></div>
                </section>
                <section class="section" id="privacy">
                    <div class="section-title"><span class="number">05</span>
                        <div>
                            <h2>Privacy &amp; media</h2>
                            <p class="sub">Both participants must sign the addendum, including when all permissions are
                                declined.</p>
                        </div>
                    </div>
                    <div class="field full"><label for="privacyNotes">Additional confidentiality and retention
                            terms</label><textarea id="privacyNotes"
                            name="privacyNotes">No recording, live streaming, posting, or sharing identifying details without separate permission. Either participant may request deletion of privately held images or recordings, subject to applicable law.</textarea>
                    </div>
                    <div class="media-actions no-print">
                        <button type="button" id="openMedia" aria-haspopup="dialog" aria-describedby="mediaStatus">Open
                            consent addendum</button>
                        <span class="badge" id="mediaStatus" role="status">Required · Incomplete</span>
                    </div>
                    <section id="mediaPrint" hidden aria-labelledby="mediaPrintTitle">
                        <h3 id="mediaPrintTitle">Privacy &amp; Media Consent Addendum</h3>
                        <div id="mediaPrintBody"></div>
                    </section>
                </section>
                <section class="section" id="aftercare">
                    <div class="section-title"><span class="number">06</span>
                        <div>
                            <h2>Aftercare & follow-up</h2>
                            <p class="sub">Agree on what support looks like after the activity ends.</p>
                        </div>
                    </div>
                    <div class="check-grid"><label class="check"><input type="checkbox" name="water" ><span>Water
                                and a quiet recovery period</span></label><label class="check"><input type="checkbox"
                                name="check" ><span>Physical and emotional check-in</span></label><label
                            class="check"><input type="checkbox" name="warmth"><span>Blanket / warmth if
                                requested</span></label><label class="check"><input type="checkbox" name="space"
                                ><span>Respect for space or touch preferences</span></label></div>
                    <div class="grid compact">
                        <div class="field full"><label for="aftercareNotes">Specific aftercare preferences and follow-up
                                arrangements</label><textarea id="aftercareNotes"
                                name="aftercareNotes">Check for discomfort or marks after release. Discuss any concerns privately and follow up the next day if both agree.</textarea>
                        </div>
                    </div>
                </section>
                <section class="section" id="signatures">
                    <div class="section-title"><span class="number">07</span>
                        <div>
                            <h2>Acknowledgment & signatures</h2>
                            <p class="sub">Confirm discussion and understanding, not irrevocable permission.</p>
                        </div>
                    </div>
                    <p>By signing, each adult acknowledges reviewing this document, discussing limits and safety, and
                        understanding that consent remains voluntary and revocable. This document does not authorize any
                        activity without consent at the time it occurs.</p>
                    <div class="grid">
                        <div class="field signature"><label for="topSign">Rope practitioner signature
                                (typed)</label><input id="topSign" name="topSign" type="text"
                                placeholder="Type full name"><label for="topSignDate">Date signed</label><input
                                id="topSignDate" name="topSignDate" type="date"></div>
                        <div class="field signature"><label for="bottomSign">Rope recipient signature
                                (typed)</label><input id="bottomSign" name="bottomSign" type="text"
                                placeholder="Type full name"><label for="bottomSignDate">Date signed</label><input
                                id="bottomSignDate" name="bottomSignDate" type="date"></div>
                    </div>
                    <p class="fineprint">Typed names are for personal acknowledgment and may not satisfy legal signature
                        requirements. No claim of legal enforceability is made. This agreement must be revisited
                        whenever circumstances or boundaries change.</p>
                </section>
            </form>
        </div>
        <div class="footer">Personal consent worksheet · Saved only in this browser on this device · Use Print / Save
            PDF for a portable copy.</div>
    </div>
    <dialog id="mediaDialog" class="no-print" aria-labelledby="mediaTitle">
        <form id="mediaForm" autocomplete="off">
            <h2 id="mediaTitle">Privacy &amp; Media Consent Addendum</h2>
            <p id="mediaMessage" class="sub" role="status"></p>
            <fieldset>
                <legend>Permissions for this session</legend>
                <p class="sub">Check only what both participants explicitly agree to. Un permissions are
                    declined. Recording, retention, sharing and posting each require their own opt-in. Livestreaming is
                    excluded.</p>
                <div class="check-grid compact">
                    <label class="check"><input type="checkbox" name="photo" data-label="Photography">Allow
                        photography</label>
                    <label class="check"><input type="checkbox" name="video" data-label="Video recording">Allow video
                        recording</label>
                    <label class="check"><input type="checkbox" name="audio" data-label="Audio recording">Allow audio
                        recording</label>
                    <label class="check"><input type="checkbox" name="retain" data-label="Private retention">Allow
                        private retention</label>
                    <label class="check"><input type="checkbox" name="share"
                            data-label="Sharing with specified recipients">Allow sharing with specified
                        recipients</label>
                    <label class="check"><input type="checkbox" name="post"
                            data-label="Public posting on specified platforms">Allow public posting on specified
                        platforms</label>
                </div>
            </fieldset>
            <div class="grid">
                <div class="field full"><label for="mediaTerms">Scope and restrictions</label>
                    <textarea id="mediaTerms" name="terms"
                        placeholder="Specify approved media and uses. For retention, include who may keep copies and until when. For sharing or posting, name the recipients or platforms."></textarea>
                </div>
                <div class="field signature"><label for="mediaTop">Rope practitioner signature (typed)</label>
                    <input id="mediaTop" name="top" type="text" required>
                </div>
                <div class="field signature"><label for="mediaBottom">Rope recipient signature (typed)</label>
                    <input id="mediaBottom" name="bottom" type="text" required>
                </div>
            </div>
            <p class="note">Either participant may withdraw consent at any time. Tell the other participant immediately.
                Stop any affected recording, retention, sharing or posting, delete copies under your control and request
                removal of copies already shared. The withdrawal button revokes all permissions in this form. Changes to
                permissions or restrictions require fresh signatures.</p>
            <p id="mediaError" class="media-error" role="alert" hidden></p>
            <div class="media-actions">
                <button type="submit" class="primary">Sign and complete</button>
                <button type="button" id="withdrawMedia" hidden>Withdraw all permissions</button>
                <button type="button" id="closeMedia">Close</button>
            </div>
        </form>
    </dialog>
    <script>
        (() => {
            'use strict';
            const byId = id => document.getElementById(id);
            const key = 'shibari-consent-agreement-v1';
            const form = byId('agreement');
            const status = byId('saveStatus');
            const fields = [...form.querySelectorAll('input, textarea, select')];
            const dialog = byId('mediaDialog');
            const mediaForm = byId('mediaForm');
            const choices = [...mediaForm.querySelectorAll('[data-label]')];
            const terms = byId('mediaTerms');
            const signatures = [byId('mediaTop'), byId('mediaBottom')];
            const mediaFields = [...choices, terms, ...signatures];
            const contextFields = ['topName', 'bottomName', 'sessionDate', 'sessionTime', 'location', 'privacyNotes'].map(byId);
            const mediaStatus = byId('mediaStatus');
            const mediaMessage = byId('mediaMessage');
            const mediaError = byId('mediaError');
            const withdraw = byId('withdrawMedia');
            let signed = null;
            let withdrawnAt = null;

            const read = el => el.type === 'checkbox' ? el. : el.value;
            function restore(elements, values) {
                for (const el of elements) {
                    const value = values?.[el.name];
                    if (el.type === 'checkbox' && typeof value === 'boolean') el. = value;
                    if (el.type !== 'checkbox' && typeof value === 'string') {
                        if (!el.options || [...el.options].some(option => option.value === value)) el.value = value;
                    }
                }
            }
            const serialize = elements => Object.fromEntries(elements.map(el => [el.name, read(el)]));
            const snapshot = () => JSON.stringify([contextFields.map(read), serialize(mediaFields)]);
            const complete = () => Boolean(signed && signed.snapshot === snapshot() && !withdrawnAt);
            const  = () => choices.some(el => el.);
            const needsTerms = () => choices.slice(3).some(el => el.);
            const formatDate = iso => new Date(iso).toLocaleString();

            const now = new Date();
            const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
            for (const id of ['sessionDate', 'topSignDate', 'bottomSignDate']) byId(id).value = localDate;
            const defaults = serialize(fields);
            const mediaDefaults = serialize(mediaFields);

            try {
                const saved = JSON.parse(localStorage.getItem(key) || 'null');
                restore(fields, saved);
                restore(mediaFields, saved?.mediaConsent?.fields);
                signed = saved?.mediaConsent?.signed || null;
                withdrawnAt = saved?.mediaConsent?.withdrawnAt || null;
                if (signed && !complete()) invalidate();
            } catch {
                status.textContent = 'Could not restore saved fields';
            }

            function save() {
                const values = serialize(fields);
                values.mediaConsent = { fields: serialize(mediaFields), signed, withdrawnAt };
                try {
                    localStorage.setItem(key, JSON.stringify(values));
                    status.textContent = 'Saved locally';
                } catch {
                    status.textContent = 'Local saving unavailable';
                }
            }

            function invalidate() {
                signed = null;
                signatures.forEach(el => { el.value = ''; });
            }

            function render() {
                const done = complete();
                mediaStatus.dataset.state = done ? 'complete' : 'incomplete';
                mediaStatus.textContent = done ? 'Required · Completed'
                    : withdrawnAt ? 'Required · Withdrawn / Incomplete' : 'Required · Incomplete';
                mediaMessage.textContent = withdrawnAt
                    ? `Withdrawn on ${formatDate(withdrawnAt)}. Both participants must sign again to complete the addendum.`
                    : done ? `Completed on ${formatDate(signed.at)}.` : 'Waiting for both participants to sign.';
                withdraw.hidden = !done || !();
                terms.required = needsTerms();
                updatePrint();
            }

            function updatePrint() {
                const body = byId('mediaPrintBody');
                body.replaceChildren();
                function paragraph(value) {
                    const p = document.createElement('p');
                    p.textContent = value;
                    body.append(p);
                }
                if (!complete()) {
                    paragraph(withdrawnAt
                        ? `DRAFT — Consent withdrawn on ${formatDate(withdrawnAt)}. No media permissions are in effect.`
                        : 'DRAFT — Required addendum incomplete. No media permissions are in effect.');
                } else {
                    paragraph(`Completed on ${formatDate(signed.at)}.`);
                    const list = document.createElement('ul');
                    for (const choice of choices) {
                        const item = document.createElement('li');
                        item.textContent = `${choice.dataset.label}: ${choice. ? 'Allowed' : 'Declined'}`;
                        list.append(item);
                    }
                    body.append(list);
                    if (terms.value.trim()) paragraph(`Scope and restrictions: ${terms.value.trim()}`);
                    paragraph(`Rope practitioner signature: ${signatures[0].value.trim()}`);
                    paragraph(`Rope recipient signature: ${signatures[1].value.trim()}`);
                    paragraph('Either participant may withdraw consent at any time. On withdrawal, stop the affected uses, delete copies under your control and request removal of copies already shared.');
                }
                byId('mediaPrint').hidden = false;
            }

            function showError(message = '') {
                mediaError.textContent = message;
                mediaError.hidden = !message;
            }

            function openMedia(message = '') {
                showError(message);
                if (!dialog.open) dialog.showModal();
            }

            function handleMainEdit(event) {
                if (signed && contextFields.includes(event.target) && !complete()) invalidate();
                render();
                save();
            }
            form.addEventListener('input', handleMainEdit);
            form.addEventListener('change', handleMainEdit);

            function handleMediaEdit(event) {
                if (signed && !complete()) {
                    const editedSignature = signatures.includes(event.target) ? event.target.value : null;
                    invalidate();
                    if (editedSignature !== null) event.target.value = editedSignature;
                }
                showError();
                render();
                save();
            }
            mediaForm.addEventListener('input', handleMediaEdit);
            mediaForm.addEventListener('change', handleMediaEdit);
            byId('openMedia').addEventListener('click', () => openMedia());
            byId('closeMedia').addEventListener('click', () => dialog.close());

            mediaForm.addEventListener('submit', event => {
                event.preventDefault();
                if (needsTerms() && !terms.value.trim()) {
                    showError('Enter the retention details, recipients or platforms for the permissions selected.');
                    terms.focus();
                    return;
                }
                const emptySignature = signatures.find(el => !el.value.trim());
                if (emptySignature) {
                    showError('Both participants must enter their typed signatures.');
                    emptySignature.focus();
                    return;
                }
                withdrawnAt = null;
                signed = { at: new Date().toISOString(), snapshot: snapshot() };
                render();
                save();
                dialog.close();
            });

            withdraw.addEventListener('click', () => {
                if (!window.confirm('Withdraw all media permissions? Both participants will need to sign again.')) return;
                choices.forEach(el => { el. = false; });
                invalidate();
                withdrawnAt = new Date().toISOString();
                showError();
                render();
                save();
            });

            function finalize() {
                if (!complete()) {
                    openMedia('Complete the required addendum before finalizing the agreement.');
                    return;
                }
                save();
                window.print();
            }
            form.addEventListener('submit', event => {
                event.preventDefault();
                finalize();
            });
            byId('printBtn').addEventListener('click', finalize);
            window.addEventListener('beforeprint', updatePrint);
            byId('resetBtn').addEventListener('click', () => {
                if (!window.confirm('Reset all entries to the default agreement? This removes locally saved edits.')) return;
                restore(fields, defaults);
                restore(mediaFields, mediaDefaults);
                signed = null;
                withdrawnAt = null;
                showError();
                render();
                save();
            });
            render();
        })();
    </script>
</body>

</html>