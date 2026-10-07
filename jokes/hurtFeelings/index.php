<?php
declare(strict_types=1);

$cssVersion = filemtime(__DIR__ . '/css/styles.css');
$jsVersion = filemtime(__DIR__ . '/js/app.js');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hurt Feelings Report</title>
    <link rel="stylesheet" href="css/styles.css?v=<?= $cssVersion ?>">
    <script src="js/app.js?v=<?= $jsVersion ?>" defer></script>
</head>
<body>
<main class="page">
    <div class="toolbar">
        <span id="saveStatus" role="status" aria-live="polite">Draft saves in this browser.</span>
        <button type="button" id="clearDraft">Clear draft</button>
        <button type="button" onclick="window.print()">Print report</button>
    </div>

    <form id="reportForm" autocomplete="on">
        <header class="document-header">
            <h1>HURT FEELINGS REPORT <span>• For All About Me 111</span></h1>
            <p class="subtitle">For the documentation of emotional disturbances, bruised egos, and other matters of the heart.</p>
            <div class="privacy-title">DATA REQUIRED BY THE PRIVACY ACT OF 1974</div>
            <div class="legal-copy">
                <p><strong>AUTHORITY:</strong> Common sense, kind communication, and the right to have feelings.</p>
                <p><strong>PRINCIPAL PURPOSE:</strong> To record what happened and identify a reasonable path toward repair.</p>
                <p><strong>ROUTINE USES:</strong> For personal reflection and, if appropriate, a respectful conversation.</p>
                <p><strong>DISCLOSURE:</strong> Sharing is voluntary. You may leave any field blank.</p>
            </div>
        </header>

        <section class="report-section">
            <h2>PART I — ADMINISTRATIVE DATA</h2>
            <div class="grid administrative">
                <label class="cell">A. WHINER'S NAME <i>(Last, First, MI)</i><input name="whiner_name" aria-label="Whiner's name"></label>
                <label class="cell">B. TITLE<input name="reporter_title" aria-label="Reporter's title"></label>
                <label class="cell">C. CASE OR FILE NUMBER<input name="case_number" aria-label="Case or file number"></label>
                <label class="cell">D. DATE OF REPORT<input name="report_date" type="date" aria-label="Date of report"></label>
                <label class="cell span-2">E. ORGANIZATION<input name="organization" aria-label="Organization"></label>
                <label class="cell span-2">F. NAME AND TITLE OF PERSON FILLING OUT THIS FORM<input name="completed_by" aria-label="Name and title of person filling out the report"></label>
            </div>
        </section>

        <section class="report-section">
            <h2>PART II — INCIDENT REPORT</h2>
            <div class="grid incident">
                <label class="cell">A. DATE FEELINGS WERE HURT<input name="incident_date" type="date" aria-label="Date feelings were hurt"></label>
                <label class="cell">B. TIME OF HURTFULNESS<input name="incident_time" type="time" aria-label="Time of incident"></label>
                <label class="cell">C. LOCATION OF HURTFUL INCIDENT<input name="incident_location" aria-label="Location of incident"></label>
                <label class="cell">D. NAME OF SUPPORT PERSON<input name="support_person" aria-label="Name of support person"></label>
                <label class="cell span-2">E. NAME OF PERSON WHO HURT YOUR FEELINGS<input name="person_who_hurt" aria-label="Name of person who hurt your feelings"></label>
                <label class="cell">F. TITLE<input name="person_title" aria-label="Person's title"></label>
                <label class="cell">G. ORGANIZATION<input name="person_organization" aria-label="Person's organization"></label>
            </div>
        </section>

        <section class="report-section">
            <h2>E. INJURY <i>(Mark all that apply)</i></h2>
            <div class="grid injury">
                <fieldset class="cell question">
                    <legend>1. INTO WHICH EAR WERE THE WORDS OF HURTFULNESS SPOKEN?</legend>
                    <div class="choices">
                        <label><input type="checkbox" name="words_direction[]" value="Left"> LEFT</label>
                        <label><input type="checkbox" name="words_direction[]" value="Right"> RIGHT</label>
                        <label><input type="checkbox" name="words_direction[]" value="Both"> BOTH</label>
                    </div>
                </fieldset>
                <fieldset class="cell question">
                    <legend>2. IS THERE PERMANENT FEELING DAMAGE?</legend>
                    <div class="choices">
                        <label><input type="radio" name="permanent_damage" value="Yes"> YES</label>
                        <label><input type="radio" name="permanent_damage" value="No"> NO</label>
                        <label><input type="radio" name="permanent_damage" value="Maybe"> MAYBE</label>
                    </div>
                </fieldset>
                <fieldset class="cell question">
                    <legend>3. DID YOU REQUIRE A “TISSUE” FOR TEARS?</legend>
                    <div class="choices">
                        <label><input type="radio" name="tears" value="Yes"> YES</label>
                        <label><input type="radio" name="tears" value="No"> NO</label>
                        <label><input type="radio" name="tears" value="Multiple"> MULTIPLE</label>
                    </div>
                </fieldset>
                <fieldset class="cell question">
                    <legend>4. HAS THIS INCIDENT RESULTED IN A TRAUMATIC SELF-ESTEEM INJURY?</legend>
                    <div class="choices">
                        <label><input type="radio" name="self_esteem_injury" value="Yes"> YES</label>
                        <label><input type="radio" name="self_esteem_injury" value="No"> NO</label>
                        <label><input type="radio" name="self_esteem_injury" value="Maybe"> MAYBE</label>
                    </div>
                </fieldset>
            </div>
        </section>

        <section class="report-section">
            <h2>F. REASON FOR FILING THIS REPORT <i>(Mark all that apply)</i></h2>
            <div class="grid reasons">
                <label class="reason"><input type="checkbox" name="reasons[]" value="Thin-skinned"> I am thin-skinned</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Pastor should fix problems"> The pastor needs to fix my problems</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Message preached at me"> The message was preached at me</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Wimp"> I am a wimp</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Feelings easily hurt"> My feelings are easily hurt</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="No love in message"> There was no love in the message</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Pansy"> I am a pansy</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Didn't sign up"> I didn't sign up for this</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Children treated unfairly"> Someone failed to see how wonderful my children are</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Latest idea not embraced"> Everyone did not embrace my latest idea</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Can't have it my way"> I was told that I can't have it my way</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Not enough black robes"> There are not enough black robes</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Want my mommy"> I want my mommy</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Someone sat in my seat"> Someone sat in my seat</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Free food disappointment"> I did not get a large enough helping of free food</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Didn't get what I feel entitled to"> I did not get all to which I feel entitled</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="Church temperature"> The church is too hot or cold</label>
                <label class="reason"><input type="checkbox" name="reasons[]" value="All of the above"> All of the above and more</label>
            </div>
            <label class="cell details-cell">OTHER REASON<input name="reason_details" aria-label="Other reason"></label>
        </section>

        <section class="report-section narrative-section">
            <h2>G. NARRATIVE <i>(Tell us in your own words how your feelings were hurt.)</i><span class="caution">CAUTION — No tears on the form.</span></h2>
            <textarea class="narrative" name="narrative" aria-label="Narrative describing how your feelings were hurt"></textarea>
        </section>

        <section class="report-section">
            <h2>PART III — AUTHENTICATION</h2>
            <div class="grid authentication">
                <label class="cell">a. PRINTED NAME OF SUPPORT PERSON<input name="support_printed_name" aria-label="Printed name of support person"></label>
                <label class="cell">b. SIGNATURE<input name="support_signature" aria-label="Support person signature"></label>
                <label class="cell">c. PRINTED NAME OF WHINER<input name="whiner_printed_name" aria-label="Printed name of whiner"></label>
                <label class="cell">d. SIGNATURE<input name="whiner_signature" aria-label="Whiner signature"></label>
            </div>
            <div class="declaration">
                I understand that feelings are real, even when people see a situation differently. I have described this incident as honestly as I can and am open to a respectful conversation about what happened.
            </div>
            <div class="fine-print">
                <p><strong>Notice:</strong> This form is for reflection and communication, not an official complaint or a substitute for appropriate help. Share it only with people you trust.</p>
                <p>This report does not determine intent or assign blame. A good next step may be to explain the impact, listen to each other, and agree on how to handle similar situations in the future.</p>
            </div>
        </section>
    </form>
</main>
</body>
</html>
