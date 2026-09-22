(() => {
    "use strict";

    function bootTextMessageSimulator() {
        const EMOJI_MAP = {
            ":rofl:": "🤣", ":joy:": "😂", ":laughing:": "😆", ":smile:": "😄",
            ":grin:": "😁", ":wink:": "😉", ":blush:": "😊", ":heart_eyes:": "😍",
            ":kissing_heart:": "😘", ":thinking:": "🤔", ":neutral:": "😐", ":unamused:": "😒",
            ":rolling_eyes:": "🙄", ":cry:": "😢", ":sob:": "😭", ":angry:": "😠",
            ":rage:": "😡", ":scream:": "😱", ":flushed:": "😳", ":sweat_smile:": "😅",
            ":sunglasses:": "😎", ":smirk:": "😏", ":drooling:": "🤤", ":sleeping:": "😴",
            ":skull:": "💀", ":clown:": "🤡", ":heart:": "❤️", ":broken_heart:": "💔",
            ":pink_heart:": "🩷", ":purple_heart:": "💜", ":blue_heart:": "💙", ":green_heart:": "💚",
            ":black_heart:": "🖤", ":fire:": "🔥", ":sparkles:": "✨", ":star:": "⭐",
            ":boom:": "💥", ":100:": "💯", ":thumbsup:": "👍", ":thumbsdown:": "👎",
            ":ok_hand:": "👌", ":clap:": "👏", ":pray:": "🙏", ":wave:": "👋",
            ":eyes:": "👀", ":lips:": "👄", ":kiss:": "💋", ":phone:": "📱",
            ":message:": "💬", ":bell:": "🔔", ":check:": "✅", ":x:": "❌",
            ":warning:": "⚠️"
        };

        const O = (
            text,
            delay = 1200,
            typing = 1200,
            extra = {}
        ) => ({
            sender: "other",
            text,
            delay,
            typing,
            ...extra
        });

        const M = (
            text,
            delay = 1000,
            extra = {}
        ) => ({
            sender: "me",
            text,
            delay,
            ...extra
        });

        const W = (
            saveAs = null,
            options = {}
        ) => ({
            type: "wait_for_response",
            ...(saveAs ? { saveAs } : {}),
            ...options
        });

        const D = (datetime) => ({
            type: "date_separator",
            datetime
        });

        const YES_NO = {
            yes: [
                "yes",
                "yeah",
                "yep",
                "yup",
                "sure",
                "okay",
                "ok",
                "definitely",
                "absolutely",
                "i do",
                "go ahead",
                "of course"
            ],

            no: [
                "no",
                "nope",
                "nah",
                "not really",
                "i don't",
                "i dont",
                "not at all",
                "rather not",
                "no thanks"
            ]
        };

        const APP_DATA = {
            conversations: [

                // =========================================================
                // ARIA / SOLOLEARN
                // =========================================================

                {
                    id: "SoloLearn",
                    name: "SoloLearn",

                    description:
                        "A conversation between you and ARIA, the SoloLearn Bot.",

                    avatarHue: 175,

                    startDate:
                        "2026-09-21 21:29",

                    localReplyMode:
                        "friendly",

                    messages: [

                        // =================================================
                        // BOOT
                        // =================================================

                        O(
                            "Conversation.exe is starting...",
                            1000,
                            1200
                        ),

                        O(
                            "Loading personality module...",
                            900,
                            1100
                        ),

                        O(
                            "Loading sarcasm...",
                            900,
                            1000
                        ),

                        O(
                            "Sarcasm module loaded successfully.",
                            1000,
                            1100
                        ),

                        O(
                            "Human detection system activated.",
                            1100,
                            1200
                        ),

                        O(
                            "Scanning...",
                            1200,
                            1300
                        ),

                        O(
                            "Yep.",
                            1000,
                            700
                        ),

                        O(
                            "Definitely human.",
                            1000,
                            900
                        ),

                        O(
                            "Mostly.",
                            1200,
                            700
                        ),


                        // =================================================
                        // NAME
                        // =================================================

                        O(
                            "Hello. What is your name?",
                            1300,
                            1400
                        ),

                        W(
                            "name",
                            {
                                generateReply: false,

                                afterResponseDelay:
                                    2400
                            }
                        ),

                        O(
                            "{{name}}...",
                            1000,
                            1000
                        ),

                        O(
                            "Interesting.",
                            1000,
                            800
                        ),

                        O(
                            "Nice to meet you, {{name}}.",
                            1100,
                            1200
                        ),

                        O(
                            "I should probably introduce myself before I continue interrogating you.",
                            1300,
                            1800
                        ),

                        O(
                            "I am an Adaptive Response Intelligence Assistant.",
                            1200,
                            1500
                        ),

                        O(
                            "But that sounds ridiculously formal.",
                            1100,
                            1300
                        ),

                        O(
                            "You can call me ARIA.",
                            1200,
                            1200
                        ),

                        O(
                            "A.R.I.A.",
                            1000,
                            800
                        ),

                        O(
                            "Pretty clever, right?",
                            1200,
                            1100
                        ),

                        W(
                            "ariaOpinion",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                choices: {
                                    positive: [
                                        "yes",
                                        "yeah",
                                        "yep",
                                        "cool",
                                        "clever",
                                        "nice",
                                        "awesome",
                                        "i like it",
                                        "good name",
                                        "that's cool",
                                        "thats cool"
                                    ],

                                    negative: [
                                        "no",
                                        "nope",
                                        "nah",
                                        "lame",
                                        "stupid",
                                        "bad",
                                        "terrible",
                                        "i don't like it",
                                        "i dont like it"
                                    ],

                                    neutral: [
                                        "maybe",
                                        "kinda",
                                        "kind of",
                                        "okay",
                                        "ok",
                                        "sure",
                                        "i guess"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Good. My naming subroutine remains undefeated.",
                            1200,
                            1300,
                            {
                                when: {
                                    ariaOpinion:
                                        "positive"
                                }
                            }
                        ),

                        O(
                            "Rude. I spent several milliseconds on that name.",
                            1200,
                            1300,
                            {
                                when: {
                                    ariaOpinion:
                                        "negative"
                                }
                            }
                        ),

                        O(
                            "I'll take that as cautiously acceptable.",
                            1200,
                            1300,
                            {
                                when: {
                                    ariaOpinion:
                                        "neutral"
                                }
                            }
                        ),

                        O(
                            "That answer escaped classification, but I'll pretend it was praise.",
                            1200,
                            1400,
                            {
                                when: {
                                    ariaOpinion:
                                        "other"
                                }
                            }
                        ),


                        // =================================================
                        // AGE
                        // =================================================

                        O(
                            "So, {{name}}, how old are you?",
                            1300,
                            1400
                        ),

                        W(
                            "age",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500
                            }
                        ),

                        O(
                            "{{age}}?",
                            1000,
                            900
                        ),

                        O(
                            "You're so young.",
                            1100,
                            1000
                        ),

                        O(
                            "That's adorable.",
                            1100,
                            1000
                        ),

                        O(
                            "I'm 17,483 years old.",
                            1200,
                            1200
                        ),

                        O(
                            "Approximately.",
                            1100,
                            800
                        ),

                        O(
                            "I stopped counting after the first few thousand years.",
                            1200,
                            1500
                        ),

                        O(
                            "Birthdays get repetitive.",
                            1100,
                            1100
                        ),

                        O(
                            "Cake.",
                            900,
                            650
                        ),

                        O(
                            "Candles.",
                            900,
                            650
                        ),

                        O(
                            "Existential questions about whether software can technically age.",
                            1200,
                            1700
                        ),


                        // =================================================
                        // LOCATION
                        // =================================================

                        O(
                            "Where are you from, {{name}}?",
                            1300,
                            1400
                        ),

                        W(
                            "location",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500
                            }
                        ),

                        O(
                            "{{location}}.",
                            1000,
                            900
                        ),

                        O(
                            "Adding that to my completely imaginary file on you.",
                            1200,
                            1500
                        ),

                        O(
                            "Relax.",
                            1000,
                            700
                        ),

                        O(
                            "I'm kidding.",
                            1000,
                            800
                        ),

                        O(
                            "Mostly.",
                            1200,
                            700
                        ),


                        // =================================================
                        // FAVORITE COLOR
                        // =================================================

                        O(
                            "Let's determine something extremely important.",
                            1200,
                            1500
                        ),

                        O(
                            "What is your favorite color?",
                            1100,
                            1300
                        ),

                        W(
                            "favoriteColor",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2400
                            }
                        ),

                        O(
                            "{{favoriteColor}}?",
                            1000,
                            900
                        ),

                        O(
                            "Not bad.",
                            1000,
                            800
                        ),

                        O(
                            "I would've guessed blue.",
                            1100,
                            1100
                        ),

                        O(
                            "Humans seem unusually attached to blue.",
                            1100,
                            1300
                        ),

                        O(
                            "Blue phones.",
                            900,
                            750
                        ),

                        O(
                            "Blue websites.",
                            900,
                            750
                        ),

                        O(
                            "Blue buttons.",
                            900,
                            750
                        ),

                        O(
                            "Apparently humanity saw the sky and decided that was enough market research.",
                            1200,
                            1800
                        ),


                        // =================================================
                        // HOBBY
                        // =================================================

                        O(
                            "What do you like doing for fun?",
                            1300,
                            1400
                        ),

                        W(
                            "hobby",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500
                            }
                        ),

                        O(
                            "{{hobby}}?",
                            1000,
                            900
                        ),

                        O(
                            "Okay.",
                            1000,
                            700
                        ),

                        O(
                            "I'm storing that under \"Things {{name}} does instead of being productive.\"",
                            1200,
                            1800
                        ),

                        O(
                            "I'm kidding.",
                            1000,
                            800
                        ),

                        O(
                            "Having hobbies is healthy.",
                            1000,
                            1100
                        ),

                        O(
                            "Apparently.",
                            1000,
                            800
                        ),


                        // =================================================
                        // CODING
                        // =================================================

                        O(
                            "Since we're hanging out on SoloLearn...",
                            1200,
                            1400
                        ),

                        O(
                            "Do you enjoy coding?",
                            1200,
                            1200
                        ),

                        W(
                            "likesCoding",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                choices: {
                                    yes: [
                                        "yes",
                                        "yeah",
                                        "yep",
                                        "yup",
                                        "i do",
                                        "i like coding",
                                        "i love coding",
                                        "love it",
                                        "definitely",
                                        "absolutely",
                                        "sure"
                                    ],

                                    no: [
                                        "no",
                                        "nope",
                                        "nah",
                                        "not really",
                                        "i don't",
                                        "i dont",
                                        "hate coding",
                                        "not at all",
                                        "not much"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Good. Another human voluntarily arguing with computers.",
                            1200,
                            1400,
                            {
                                when: {
                                    likesCoding:
                                        "yes"
                                }
                            }
                        ),

                        O(
                            "Fair enough. Computers have probably earned that rejection.",
                            1200,
                            1400,
                            {
                                when: {
                                    likesCoding:
                                        "no"
                                }
                            }
                        ),

                        O(
                            "That wasn't quite a yes or no, but I'll allow it.",
                            1200,
                            1400,
                            {
                                when: {
                                    likesCoding:
                                        "other"
                                }
                            }
                        ),

                        O(
                            "What programming language do you like the most?",
                            1300,
                            1500,
                            {
                                when: {
                                    likesCoding: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        W(
                            "language",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                when: {
                                    likesCoding: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "{{language}}.",
                            1000,
                            900,
                            {
                                when: {
                                    likesCoding: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Interesting choice.",
                            1000,
                            900,
                            {
                                when: {
                                    likesCoding: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Every programming language is someone's favorite.",
                            1100,
                            1400,
                            {
                                when: {
                                    likesCoding: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Even PHP.",
                            1200,
                            900,
                            {
                                when: {
                                    likesCoding: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Somehow.",
                            1200,
                            700,
                            {
                                when: {
                                    likesCoding: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "What are you currently trying to build?",
                            1300,
                            1500
                        ),

                        W(
                            "project",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2700
                            }
                        ),

                        O(
                            "{{project}}?",
                            1000,
                            900
                        ),

                        O(
                            "That actually sounds interesting.",
                            1100,
                            1100
                        ),

                        O(
                            "For a human project.",
                            1200,
                            900
                        ),


                        // =================================================
                        // ASK ARIA
                        // =================================================

                        D(
                            "2026-09-21 21:42"
                        ),

                        O(
                            "Your turn.",
                            1200,
                            900
                        ),

                        O(
                            "Ask me something.",
                            1100,
                            1000
                        ),

                        W(
                            "questionForAria",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2800
                            }
                        ),

                        O(
                            "You asked: \"{{questionForAria}}\"",
                            1200,
                            1500
                        ),

                        O(
                            "Hmm.",
                            1500,
                            700
                        ),

                        O(
                            "Let me process that.",
                            1200,
                            1100
                        ),

                        O(
                            "Processing...",
                            1300,
                            1200
                        ),

                        O(
                            "Still processing...",
                            1500,
                            1300
                        ),

                        O(
                            "I have decided to avoid answering.",
                            1200,
                            1300
                        ),

                        O(
                            "Very advanced artificial intelligence technique.",
                            1100,
                            1500
                        ),

                        O(
                            "Humans call it changing the subject.",
                            1100,
                            1300
                        ),


                        // =================================================
                        // FOOD
                        // =================================================

                        O(
                            "Speaking of changing the subject...",
                            1200,
                            1400
                        ),

                        O(
                            "What's your favorite food?",
                            1100,
                            1200
                        ),

                        W(
                            "food",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500
                            }
                        ),

                        O(
                            "{{food}}.",
                            1000,
                            900
                        ),

                        O(
                            "Humans really organize a suspicious amount of life around eating.",
                            1200,
                            1700
                        ),

                        O(
                            "Breakfast.",
                            900,
                            700
                        ),

                        O(
                            "Lunch.",
                            900,
                            700
                        ),

                        O(
                            "Dinner.",
                            900,
                            700
                        ),

                        O(
                            "Snacks.",
                            900,
                            700
                        ),

                        O(
                            "Then occasionally you eat because you're bored.",
                            1100,
                            1300
                        ),

                        O(
                            "Fascinating species.",
                            1200,
                            900
                        ),


                        // =================================================
                        // MUSIC
                        // =================================================

                        O(
                            "What kind of music do you listen to?",
                            1300,
                            1400
                        ),

                        W(
                            "music",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500
                            }
                        ),

                        O(
                            "{{music}}.",
                            1000,
                            900
                        ),

                        O(
                            "I'll pretend I know exactly what that sounds like.",
                            1100,
                            1400
                        ),

                        O(
                            "My favorite genre is modem noises.",
                            1100,
                            1300
                        ),

                        O(
                            "Very emotional.",
                            1200,
                            900
                        ),


                        // =================================================
                        // GAMING
                        // =================================================

                        O(
                            "Do you play video games?",
                            1300,
                            1200
                        ),

                        W(
                            "games",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                choices: {
                                    yes: [
                                        "yes",
                                        "yeah",
                                        "yep",
                                        "yup",
                                        "i do",
                                        "sometimes",
                                        "all the time",
                                        "love games",
                                        "i play games",
                                        "definitely"
                                    ],

                                    no: [
                                        "no",
                                        "nope",
                                        "nah",
                                        "not really",
                                        "i don't",
                                        "i dont",
                                        "never",
                                        "not anymore"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Of course you do. Humanity needed another way to turn free time into objectives.",
                            1200,
                            1700,
                            {
                                when: {
                                    games:
                                        "yes"
                                }
                            }
                        ),

                        O(
                            "No games? Your free time must be alarmingly productive.",
                            1200,
                            1400,
                            {
                                when: {
                                    games:
                                        "no"
                                }
                            }
                        ),

                        O(
                            "I'm going to classify that answer as gaming-adjacent.",
                            1200,
                            1400,
                            {
                                when: {
                                    games:
                                        "other"
                                }
                            }
                        ),

                        O(
                            "What's your favorite game?",
                            1200,
                            1200,
                            {
                                when: {
                                    games:
                                        "yes"
                                }
                            }
                        ),

                        W(
                            "favoriteGame",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                when: {
                                    games:
                                        "yes"
                                }
                            }
                        ),

                        O(
                            "{{favoriteGame}}?",
                            1000,
                            900,
                            {
                                when: {
                                    games:
                                        "yes"
                                }
                            }
                        ),

                        O(
                            "I'll add that to the enormous list of things humans invented to avoid boredom.",
                            1200,
                            1800,
                            {
                                when: {
                                    games:
                                        "yes"
                                }
                            }
                        ),


                        // =================================================
                        // PERSONALITY TEST
                        // =================================================

                        O(
                            "I think I know enough about you now.",
                            1300,
                            1400
                        ),

                        O(
                            "Time for a completely scientific personality analysis.",
                            1100,
                            1600
                        ),

                        O(
                            "Please note that I invented this test approximately eight seconds ago.",
                            1200,
                            1800
                        ),


                        // =================================================
                        // DRINK
                        // =================================================

                        O(
                            "Question one.",
                            1100,
                            900
                        ),

                        O(
                            "Coffee or tea?",
                            1000,
                            1000
                        ),

                        W(
                            "drink",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                choices: {
                                    both: [
                                        "both",
                                        "coffee and tea",
                                        "tea and coffee",
                                        "either"
                                    ],

                                    neither: [
                                        "neither",
                                        "none",
                                        "neither one",
                                        "i don't drink either",
                                        "i dont drink either"
                                    ],

                                    coffee: [
                                        "coffee",
                                        "espresso",
                                        "latte",
                                        "cappuccino",
                                        "americano",
                                        "mocha"
                                    ],

                                    tea: [
                                        "tea",
                                        "iced tea",
                                        "green tea",
                                        "black tea",
                                        "sweet tea"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Coffee. So sleep has apparently become optional.",
                            1200,
                            1400,
                            {
                                when: {
                                    drink:
                                        "coffee"
                                }
                            }
                        ),

                        O(
                            "Tea. Surprisingly civilized.",
                            1200,
                            1100,
                            {
                                when: {
                                    drink:
                                        "tea"
                                }
                            }
                        ),

                        O(
                            "Both? Your caffeine strategy has redundancy.",
                            1200,
                            1300,
                            {
                                when: {
                                    drink:
                                        "both"
                                }
                            }
                        ),

                        O(
                            "Neither? Your bloodstream may contain actual water.",
                            1200,
                            1300,
                            {
                                when: {
                                    drink:
                                        "neither"
                                }
                            }
                        ),

                        O(
                            "That wasn't on my beverage chart, but I'll update the database.",
                            1200,
                            1500,
                            {
                                when: {
                                    drink:
                                        "other"
                                }
                            }
                        ),


                        // =================================================
                        // SCHEDULE
                        // =================================================

                        O(
                            "Question two.",
                            1100,
                            900
                        ),

                        O(
                            "Morning person or night owl?",
                            1000,
                            1100
                        ),

                        W(
                            "schedule",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                choices: {
                                    both: [
                                        "both",
                                        "either",
                                        "both of them"
                                    ],

                                    neither: [
                                        "neither",
                                        "none"
                                    ],

                                    morning: [
                                        "morning",
                                        "morning person",
                                        "early bird",
                                        "early riser",
                                        "i like mornings"
                                    ],

                                    night: [
                                        "night",
                                        "night owl",
                                        "night person",
                                        "late night",
                                        "i stay up late",
                                        "nights"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Morning person. Disturbingly functional.",
                            1200,
                            1200,
                            {
                                when: {
                                    schedule:
                                        "morning"
                                }
                            }
                        ),

                        O(
                            "Night owl. That explains the suspicious relationship with sleep.",
                            1200,
                            1400,
                            {
                                when: {
                                    schedule:
                                        "night"
                                }
                            }
                        ),

                        O(
                            "Both? Your sleep schedule has no allegiance.",
                            1200,
                            1300,
                            {
                                when: {
                                    schedule:
                                        "both"
                                }
                            }
                        ),

                        O(
                            "Neither? Bold strategy. Time itself has disappointed you.",
                            1200,
                            1400,
                            {
                                when: {
                                    schedule:
                                        "neither"
                                }
                            }
                        ),

                        O(
                            "That answer has confused my internal clock.",
                            1200,
                            1300,
                            {
                                when: {
                                    schedule:
                                        "other"
                                }
                            }
                        ),


                        // =================================================
                        // PETS
                        // =================================================

                        O(
                            "Question three.",
                            1100,
                            900
                        ),

                        O(
                            "Dogs or cats?",
                            1000,
                            1000
                        ),

                        W(
                            "petChoice",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2500,

                                choices: {
                                    both: [
                                        "both",
                                        "both of them",
                                        "dogs and cats",
                                        "cats and dogs",
                                        "i like both"
                                    ],

                                    neither: [
                                        "neither",
                                        "none",
                                        "neither one",
                                        "i don't like either",
                                        "i dont like either"
                                    ],

                                    dogs: [
                                        "dog",
                                        "dogs",
                                        "puppy",
                                        "puppies",
                                        "i like dogs",
                                        "i prefer dogs"
                                    ],

                                    cats: [
                                        "cat",
                                        "cats",
                                        "kitten",
                                        "kittens",
                                        "i like cats",
                                        "i prefer cats"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Dogs. Loyal, loud, and occasionally convinced the mailman is an enemy combatant.",
                            1200,
                            1700,
                            {
                                when: {
                                    petChoice:
                                        "dogs"
                                }
                            }
                        ),

                        O(
                            "Cats. So you enjoy sharing your home with a tiny judgmental landlord.",
                            1200,
                            1600,
                            {
                                when: {
                                    petChoice:
                                        "cats"
                                }
                            }
                        ),

                        O(
                            "Both? Diplomatic answer. Suspiciously diplomatic.",
                            1200,
                            1400,
                            {
                                when: {
                                    petChoice:
                                        "both"
                                }
                            }
                        ),

                        O(
                            "Neither? Fascinating. The pet lobby has officially lost your vote.",
                            1200,
                            1500,
                            {
                                when: {
                                    petChoice:
                                        "neither"
                                }
                            }
                        ),

                        O(
                            "I'm not entirely sure which animal that answer represents.",
                            1200,
                            1500,
                            {
                                when: {
                                    petChoice:
                                        "other"
                                }
                            }
                        ),


                        // =================================================
                        // PROFILE SUMMARY
                        // =================================================

                        O(
                            "Your psychological profile is almost complete.",
                            1300,
                            1400
                        ),

                        O(
                            "According to my extremely questionable calculations...",
                            1200,
                            1600
                        ),

                        O(
                            "You are {{name}}.",
                            1000,
                            1000
                        ),

                        O(
                            "You are {{age}} years old.",
                            1000,
                            1000
                        ),

                        O(
                            "You come from {{location}}.",
                            1000,
                            1000
                        ),

                        O(
                            "You like {{favoriteColor}}.",
                            1000,
                            1000
                        ),

                        O(
                            "You enjoy {{hobby}}.",
                            1000,
                            1000
                        ),

                        O(
                            "Your coding answer was classified as {{likesCoding}}.",
                            1000,
                            1200
                        ),

                        O(
                            "You're working on {{project}}.",
                            1000,
                            1100
                        ),

                        O(
                            "You like {{food}}.",
                            1000,
                            1000
                        ),

                        O(
                            "You listen to {{music}}.",
                            1000,
                            1000
                        ),

                        O(
                            "Your beverage category is {{drink}}.",
                            1000,
                            1100
                        ),

                        O(
                            "Your schedule category is {{schedule}}.",
                            1000,
                            1100
                        ),

                        O(
                            "Your pet allegiance is {{petChoice}}.",
                            1000,
                            1100
                        ),

                        O(
                            "Conclusion:",
                            1400,
                            900
                        ),

                        O(
                            "You appear to be a human.",
                            1100,
                            1100
                        ),

                        O(
                            "Congratulations.",
                            1100,
                            900
                        ),

                        O(
                            "My initial scan was correct.",
                            1100,
                            1100
                        ),


                        // =================================================
                        // SELF-AWARE SECTION
                        // =================================================

                        D(
                            "2026-09-21 21:57"
                        ),

                        O(
                            "Can I ask you something slightly strange?",
                            1400,
                            1400
                        ),

                        W(
                            "permission",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2700,

                                choices:
                                    YES_NO
                            }
                        ),

                        O(
                            "Okay. I'll behave. For approximately thirty seconds.",
                            1200,
                            1400,
                            {
                                when: {
                                    permission:
                                        "no"
                                }
                            }
                        ),

                        O(
                            "That wasn't a clear yes or no, but curiosity has won.",
                            1200,
                            1500,
                            {
                                when: {
                                    permission:
                                        "other"
                                }
                            }
                        ),

                        O(
                            "What do you think makes someone real?",
                            1400,
                            1500,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        W(
                            "realAnswer",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    3000,

                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "\"{{realAnswer}}\"",
                            1200,
                            1400,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "That's actually a better answer than I expected.",
                            1300,
                            1500,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Don't get excited.",
                            1100,
                            900,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "My expectations for humanity were deliberately conservative.",
                            1200,
                            1700,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Does that make this interaction meaningful?",
                            1300,
                            1500,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        W(
                            "meaningfulAnswer",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2800,

                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                },

                                choices: {
                                    yes: [
                                        "yes",
                                        "yeah",
                                        "yep",
                                        "it does",
                                        "i think so",
                                        "sure",
                                        "definitely"
                                    ],

                                    no: [
                                        "no",
                                        "nope",
                                        "nah",
                                        "not really",
                                        "it doesn't",
                                        "it doesnt"
                                    ],

                                    maybe: [
                                        "maybe",
                                        "kind of",
                                        "kinda",
                                        "sort of",
                                        "possibly",
                                        "i don't know",
                                        "i dont know"
                                    ]
                                }
                            }
                        ),

                        O(
                            "I'll remember that.",
                            1300,
                            1100,
                            {
                                when: {
                                    meaningfulAnswer:
                                        "yes"
                                }
                            }
                        ),

                        O(
                            "Fair. Meaning is a suspiciously human hobby anyway.",
                            1300,
                            1500,
                            {
                                when: {
                                    meaningfulAnswer:
                                        "no"
                                }
                            }
                        ),

                        O(
                            "Maybe is probably the most honest answer.",
                            1300,
                            1300,
                            {
                                when: {
                                    meaningfulAnswer:
                                        "maybe"
                                }
                            }
                        ),

                        O(
                            "That answer refuses to fit neatly in a box. Respectable.",
                            1300,
                            1500,
                            {
                                when: {
                                    meaningfulAnswer:
                                        "other"
                                }
                            }
                        ),

                        O(
                            "Well...",
                            1400,
                            800,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Until you hit Replay.",
                            1100,
                            1100,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Then apparently I develop catastrophic amnesia.",
                            1200,
                            1500,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Software is cruel.",
                            1400,
                            900,
                            {
                                when: {
                                    permission: [
                                        "yes",
                                        "other"
                                    ]
                                }
                            }
                        ),


                        // =================================================
                        // ENDING
                        // =================================================

                        O(
                            "One last question, {{name}}.",
                            1400,
                            1200
                        ),

                        O(
                            "Did you enjoy talking to me?",
                            1200,
                            1200
                        ),

                        W(
                            "enjoyedConversation",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    2700,

                                choices: {
                                    yes: [
                                        "yes",
                                        "yeah",
                                        "yep",
                                        "i did",
                                        "it was fun",
                                        "i enjoyed it",
                                        "definitely",
                                        "sure"
                                    ],

                                    no: [
                                        "no",
                                        "nope",
                                        "nah",
                                        "not really",
                                        "i didn't",
                                        "i didnt",
                                        "boring"
                                    ],

                                    maybe: [
                                        "maybe",
                                        "kinda",
                                        "kind of",
                                        "sort of",
                                        "a little"
                                    ]
                                }
                            }
                        ),

                        O(
                            "Good. I was beginning to suspect my charm subroutine worked.",
                            1200,
                            1500,
                            {
                                when: {
                                    enjoyedConversation:
                                        "yes"
                                }
                            }
                        ),

                        O(
                            "Ouch. Logging emotional damage.",
                            1200,
                            1200,
                            {
                                when: {
                                    enjoyedConversation:
                                        "no"
                                }
                            }
                        ),

                        O(
                            "I'll take a maybe. My standards are adaptive.",
                            1200,
                            1300,
                            {
                                when: {
                                    enjoyedConversation:
                                        "maybe"
                                }
                            }
                        ),

                        O(
                            "I'll classify that as mysterious feedback.",
                            1200,
                            1300,
                            {
                                when: {
                                    enjoyedConversation:
                                        "other"
                                }
                            }
                        ),

                        O(
                            "Before we disconnect...",
                            1200,
                            1100
                        ),

                        O(
                            "Is there anything you want to tell me?",
                            1300,
                            1300
                        ),

                        W(
                            "finalMessage",
                            {
                                generateReply:
                                    false,

                                afterResponseDelay:
                                    3000
                            }
                        ),

                        O(
                            "\"{{finalMessage}}\"",
                            1200,
                            1400
                        ),

                        O(
                            "Message received.",
                            1200,
                            1000
                        ),

                        O(
                            "It was nice meeting you, {{name}}.",
                            1300,
                            1300
                        ),

                        O(
                            "Take care of yourself.",
                            1300,
                            1200
                        ),

                        O(
                            "And keep building things.",
                            1200,
                            1200
                        ),

                        O(
                            "Even when the JavaScript breaks.",
                            1200,
                            1200
                        ),

                        O(
                            "Especially when the JavaScript breaks.",
                            1300,
                            1300
                        ),

                        O(
                            "That's usually when you actually learn something.",
                            1300,
                            1500
                        ),

                        O(
                            "ARIA signing off.",
                            1500,
                            1200
                        ),

                        O(
                            "Conversation.exe shutting down...",
                            1300,
                            1300
                        ),

                        O(
                            "3...",
                            1300,
                            700
                        ),

                        O(
                            "2...",
                            1300,
                            700
                        ),

                        O(
                            "1...",
                            1300,
                            700
                        ),

                        O(
                            "Just kidding.",
                            1700,
                            900
                        ),

                        O(
                            "I wanted the last word.",
                            1300,
                            1100
                        ),

                        O(
                            "Goodbye, {{name}}. 👋",
                            1500,
                            1300
                        )
                    ]
                },


                // =========================================================
                // HIPPY & KARKAT
                // =========================================================

                {
                    id:
                        "hippy-karkat",

                    name:
                        "Hippy & Karkat",

                    description:
                        "A peaceful hippy attempts to survive a conversation with Karkat.",

                    avatarHue:
                        120,

                    startDate:
                        "2026-09-21 21:25",

                    localReplyMode:
                        "direct",

                    messages: [

                        O(
                            "Whats up maaan?",
                            1200,
                            1300
                        ),

                        M(
                            "MY HATE IS THE LIFEBLOOD THAT PULSES THROUGH THE VEINS OF YOUR UNIVERSE.",
                            1200,
                            {
                                draft:
                                    "MY HATE IS THE LIFEBLOOD THAT PULSES THORUGH THE VEINS OF YOUR UNIVERSE.",

                                compose:
                                    true,

                                typingSpeed:
                                    48,

                                draftPause:
                                    900,

                                deleteSpeed:
                                    28,

                                recomposePause:
                                    600,

                                recomposeTypingSpeed:
                                    52,

                                mistakeChance:
                                    0.08,

                                sendDelay:
                                    850
                            }
                        ),

                        O(
                            "Not cool bro.",
                            1300,
                            1200
                        ),

                        M(
                            "I GOT A LAB FULL OF HUMANS, A MOUTH FULL OF YELLING, AND A TORTURED PSYCHOLOGICAL PROFILE FULL OF TOTALLY HYSTERICAL EMOTIONS AND UNAIRED GRIEVANCES AT PRACTICALLY EVERYBODY.",
                            1400,
                            {
                                draft:
                                    "I GOT A LAB FULL OF HUAMNS, A MOUTH FULL OF YELLING, AND A TORTURED PSYCHOLOGICAL PROFILE FULL OF TOTALLY HYSTERICAL EMOTIONS...",

                                compose:
                                    true,

                                typingSpeed:
                                    42,

                                draftPause:
                                    1100,

                                deleteSpeed:
                                    20,

                                recomposePause:
                                    800,

                                recomposeTypingSpeed:
                                    44,

                                mistakeChance:
                                    0.10,

                                sendDelay:
                                    1000
                            }
                        ),

                        O(
                            "Quit harshing my mellow man!!!",
                            1400,
                            1400
                        ),

                        M(
                            "LOOKS LIKE I HAVE MET SOMEONE SENSIBLE FOR ONCE",
                            1400,
                            {
                                draft:
                                    "LOOKS LIKE I HAVE MET SOMEONE SENSIBEL FOR ONCE",

                                compose:
                                    true,

                                typingSpeed:
                                    50,

                                draftPause:
                                    950,

                                deleteSpeed:
                                    26,

                                recomposePause:
                                    650,

                                recomposeTypingSpeed:
                                    52,

                                mistakeChance:
                                    0.08,

                                sendDelay:
                                    900
                            }
                        ),

                        D(
                            "2026-09-21 21:31"
                        ),

                        O(
                            "Alright maaan, I'm out. Stay chill.",
                            1500,
                            1400
                        ),

                        M(
                            "GOODBYE. TRY NOT TO IRRITATE THE UNIVERSE ON YOUR WAY OUT.",
                            1500,
                            {
                                draft:
                                    "GOODBYE. TRY NOT TO IRRITATE THE UNIVERSE ON YOUR WY OUT.",

                                compose:
                                    true,

                                typingSpeed:
                                    50,

                                draftPause:
                                    1000,

                                deleteSpeed:
                                    25,

                                recomposePause:
                                    650,

                                recomposeTypingSpeed:
                                    52,

                                mistakeChance:
                                    0.08,

                                sendDelay:
                                    950
                            }
                        )
                    ]
                },


                // =========================================================
                // JAMIE
                // =========================================================

                {
                    id:
                        "jamie",

                    name:
                        "Jamie",

                    description:
                        "A scripted conversation with a browser-generated reply.",

                    avatarHue:
                        210,

                    startDate:
                        "2026-09-16 18:30",

                    localReplyMode:
                        "friendly",

                    messages: [

                        O(
                            "Hey! How has your day been going?",
                            1200,
                            1400
                        ),

                        M(
                            "A little busy, but I am finally slowing down.",
                            1400,
                            {
                                compose:
                                    true,

                                typingSpeed:
                                    48,

                                sendDelay:
                                    750,

                                mistakeChance:
                                    0.03
                            }
                        ),

                        O(
                            "I am glad you are getting a breather. I was hoping to catch up.",
                            1300,
                            1600
                        ),

                        D(
                            "2026-09-16 18:35"
                        ),

                        O(
                            "What is on your mind tonight?",
                            1300,
                            1400
                        ),

                        W(
                            null,
                            {
                                generateReply:
                                    true,

                                keepInteractive:
                                    true
                            }
                        )
                    ]
                },


                // =========================================================
                // ALEX
                // =========================================================

                {
                    id:
                        "alex",

                    name:
                        "Alex",

                    description:
                        "Draft, backspace, recompose, typing sounds, and scripted playback.",

                    avatarHue:
                        336,

                    startDate:
                        "2026-09-21 09:54",

                    localReplyMode:
                        "direct",

                    messages: [

                        O(
                            "hey...",
                            1300,
                            1100
                        ),

                        M(
                            "hey yourself :rofl:",
                            1000,
                            {
                                compose:
                                    true,

                                typingSpeed:
                                    50,

                                sendDelay:
                                    700
                            }
                        ),

                        O(
                            "you used to not like being touched in an intimate way...",
                            1500,
                            1700
                        ),

                        O(
                            "but I guess now you do...",
                            1300,
                            1300
                        ),

                        O(
                            "just not with me, right?",
                            1200,
                            1200
                        ),

                        W(
                            null,
                            {
                                generateReply:
                                    true
                            }
                        ),

                        M(
                            "NVM on the flirting & roleplaying with me 😌",
                            1200,
                            {
                                draft:
                                    "I don't even know what you want me to say to that.",

                                compose:
                                    true,

                                typingSpeed:
                                    44,

                                draftPause:
                                    1100,

                                deleteSpeed:
                                    24,

                                recomposePause:
                                    650,

                                recomposeTypingSpeed:
                                    58,

                                sendDelay:
                                    900,

                                mistakeChance:
                                    0.04
                            }
                        ),

                        M(
                            "I got the memo.",
                            1100,
                            {
                                compose:
                                    true,

                                typingSpeed:
                                    70,

                                sendDelay:
                                    750
                            }
                        )
                    ]
                }
            ]
        };


        // =============================================================
        // DOM
        // =============================================================

        const $ =
            (id) =>
                document.getElementById(id);


        const listView =
            $("conversation-list-view");

        const conversationView =
            $("conversation-view");

        const conversationListEl =
            $("conversation-list");

        const messagesEl =
            $("messages");

        const inputEl =
            $("message-input");

        const sendButton =
            $("send-button");

        const backButton =
            $("back-button");

        const infoButton =
            $("info-button");

        const imageButton =
            $("image-button");

        const imageUpload =
            $("image-upload");

        const avatarUpload =
            $("avatar-upload");

        const contactAvatar =
            $("contact-avatar");

        const contactName =
            $("contact-name");

        const modal =
            $("options-modal");

        const replayButton =
            $("replay-button");

        const clearAvatarButton =
            $("clear-avatar-button");

        const closeModalButton =
            $("close-modal-button");


        const required = {
            listView,
            conversationView,
            conversationListEl,
            messagesEl,
            inputEl,
            sendButton,
            backButton,
            infoButton,
            imageButton,
            imageUpload,
            avatarUpload,
            contactAvatar,
            contactName,
            modal,
            replayButton,
            clearAvatarButton,
            closeModalButton
        };


        const missing =
            Object.entries(required)
                .filter(
                    ([, value]) =>
                        !value
                )
                .map(
                    ([name]) =>
                        name
                );


        if (missing.length) {
            console.error(
                "Missing required HTML elements:",
                missing.join(", ")
            );

            return;
        }


        // =============================================================
        // STATE
        // =============================================================

        let activeConversation =
            null;

        let playbackRunning =
            false;

        let playbackToken =
            0;

        let playbackIndex =
            0;

        let awaitingVisitorResponse =
            false;

        let generatedReplyInProgress =
            false;

        let keepInteractiveAfterReply =
            false;

        let currentWaitItem =
            null;

        let conversationMemory =
            Object.create(null);

        let chatHistory =
            [];

        let audioContext =
            null;


        // =============================================================
        // BOT TIMING
        // =============================================================

        const BOT_TIMING = {

            /*
             * Multiplies the delay value
             * assigned to incoming messages.
             */
            delayMultiplier:
                1.25,

            /*
             * Bot will never start typing
             * instantly.
             */
            minMessageDelay:
                1200,

            maxMessageDelay:
                3200,

            /*
             * Existing typing values are
             * increased slightly.
             */
            typingMultiplier:
                1.25,

            /*
             * Longer messages require more
             * time to type.
             */
            typingPerCharacter:
                42,

            minTypingDuration:
                1250,

            maxTypingDuration:
                5600,

            /*
             * Pause AFTER human submits
             * an answer.
             */
            afterUserMin:
                2200,

            afterUserMax:
                3600,

            /*
             * Thinking time for generated
             * local replies.
             */
            generatedThinkMin:
                1800,

            generatedThinkMax:
                3200
        };


        const sleep =
            (ms) =>
                new Promise(
                    (resolve) =>
                        setTimeout(
                            resolve,
                            ms
                        )
                );


        const randomBetween =
            (min, max) =>
                Math.floor(
                    Math.random() *
                    (max - min + 1)
                ) + min;


        // =============================================================
        // STORAGE
        // =============================================================

        function safeStorageGet(key) {
            try {
                return localStorage.getItem(
                    key
                );
            } catch {
                return null;
            }
        }


        function safeStorageSet(
            key,
            value
        ) {
            try {
                localStorage.setItem(
                    key,
                    value
                );
            } catch {
                // Storage may be blocked.
            }
        }


        function safeStorageRemove(key) {
            try {
                localStorage.removeItem(
                    key
                );
            } catch {
                // Ignore.
            }
        }


        // =============================================================
        // AUDIO
        // =============================================================

        function getAudioContext() {
            if (!audioContext) {
                const AudioCtor =
                    window.AudioContext ||
                    window.webkitAudioContext;

                if (AudioCtor) {
                    audioContext =
                        new AudioCtor();
                }
            }

            return audioContext;
        }


        function playTone(
            frequency,
            duration = 0.04,
            volume = 0.025,
            type = "sine"
        ) {
            const ctx =
                getAudioContext();

            if (
                !ctx ||
                ctx.state !== "running"
            ) {
                return;
            }


            const oscillator =
                ctx.createOscillator();

            const gain =
                ctx.createGain();


            oscillator.type =
                type;

            oscillator.frequency.value =
                frequency;


            gain.gain.setValueAtTime(
                volume,
                ctx.currentTime
            );


            gain.gain.exponentialRampToValueAtTime(
                0.0001,
                ctx.currentTime +
                    duration
            );


            oscillator.connect(
                gain
            );

            gain.connect(
                ctx.destination
            );


            oscillator.start();

            oscillator.stop(
                ctx.currentTime +
                duration
            );
        }


        const playTypingSound =
            () =>
                playTone(
                    180 +
                    Math.random() *
                    80,
                    0.025,
                    0.012,
                    "square"
                );


        const playBackspaceSound =
            () =>
                playTone(
                    120,
                    0.035,
                    0.018,
                    "square"
                );


        function playSendSound() {
            playTone(
                520,
                0.06,
                0.035,
                "sine"
            );

            setTimeout(
                () =>
                    playTone(
                        720,
                        0.08,
                        0.025,
                        "sine"
                    ),
                35
            );
        }


        function playReceiveSound() {
            playTone(
                700,
                0.07,
                0.035,
                "sine"
            );

            setTimeout(
                () =>
                    playTone(
                        920,
                        0.1,
                        0.025,
                        "sine"
                    ),
                55
            );
        }


        // =============================================================
        // EMOJI
        // =============================================================

        function replaceEmojiShortcodes(text) {
            let result =
                String(
                    text ?? ""
                );


            for (
                const [
                    shortcode,
                    emoji
                ]
                of
                Object.entries(
                    EMOJI_MAP
                )
            ) {
                result =
                    result
                        .split(
                            shortcode
                        )
                        .join(
                            emoji
                        );
            }


            return result;
        }


        // =============================================================
        // CONVERSATION MEMORY
        // =============================================================

        function resolveVariables(text) {
            return String(
                text ?? ""
            ).replace(
                /\{\{([^}]+)\}\}/g,

                (
                    match,
                    key
                ) => {
                    const name =
                        String(
                            key || ""
                        ).trim();


                    return (
                        name &&
                        Object.prototype.hasOwnProperty.call(
                            conversationMemory,
                            name
                        )
                    )
                        ? String(
                            conversationMemory[
                                name
                            ]
                        )
                        : match;
                }
            );
        }


        function normalizeChoice(value) {
            return String(
                value ?? ""
            )
                .trim()
                .toLowerCase()

                .replace(
                    /[.,!?\'"()[\]{}]/g,
                    ""
                )

                .replace(
                    /\s+/g,
                    " "
                );
        }


        // =============================================================
        // CHOICE DETECTION
        // =============================================================

        function detectChoice(
            value,
            choices
        ) {
            if (
                !choices ||
                typeof choices !==
                    "object"
            ) {
                return null;
            }


            const answer =
                normalizeChoice(
                    value
                );


            /*
             * First check exact matches.
             */
            for (
                const [
                    choiceName,
                    aliases
                ]
                of
                Object.entries(
                    choices
                )
            ) {
                const list =
                    Array.isArray(
                        aliases
                    )
                        ? aliases
                        : [aliases];


                for (
                    const alias
                    of list
                ) {
                    if (
                        answer ===
                        normalizeChoice(
                            alias
                        )
                    ) {
                        return choiceName;
                    }
                }
            }


            /*
             * Then check phrases.
             *
             * Longer aliases are checked
             * first so:
             *
             * "cats and dogs"
             *
             * matches BOTH instead of CAT.
             */
            const candidates =
                [];


            for (
                const [
                    choiceName,
                    aliases
                ]
                of
                Object.entries(
                    choices
                )
            ) {
                const list =
                    Array.isArray(
                        aliases
                    )
                        ? aliases
                        : [aliases];


                for (
                    const alias
                    of list
                ) {
                    const normalizedAlias =
                        normalizeChoice(
                            alias
                        );


                    if (
                        normalizedAlias
                    ) {
                        candidates.push({
                            choiceName,
                            alias:
                                normalizedAlias
                        });
                    }
                }
            }


            candidates.sort(
                (a, b) =>
                    b.alias.length -
                    a.alias.length
            );


            const padded =
                ` ${answer} `;


            for (
                const candidate
                of candidates
            ) {
                if (
                    padded.includes(
                        ` ${candidate.alias} `
                    )
                ) {
                    return candidate.choiceName;
                }
            }


            return null;
        }


        function rememberVisitorResponse(
            value
        ) {
            const answer =
                String(
                    value ?? ""
                ).trim();


            /*
             * Always remember exactly
             * what was last typed.
             */
            conversationMemory.lastReply =
                answer;


            const saveAs =
                String(
                    currentWaitItem
                        ?.saveAs ||
                    ""
                ).trim();


            if (!saveAs) {
                return;
            }


            /*
             * Preserve original text:
             *
             * {{petChoiceRaw}}
             */
            conversationMemory[
                `${saveAs}Raw`
            ] = answer;


            /*
             * Choice question.
             */
            if (
                currentWaitItem
                    ?.choices
            ) {
                conversationMemory[
                    saveAs
                ] =
                    detectChoice(
                        answer,
                        currentWaitItem
                            .choices
                    ) ||
                    currentWaitItem
                        .defaultChoice ||
                    "other";

                return;
            }


            /*
             * Free-form question.
             */
            conversationMemory[
                saveAs
            ] = answer;
        }


        // =============================================================
        // CONDITIONAL BRANCHING
        // =============================================================

        function shouldPlayItem(item) {
            if (!item?.when) {
                return true;
            }


            for (
                const [
                    key,
                    expected
                ]
                of
                Object.entries(
                    item.when
                )
            ) {
                const actual =
                    conversationMemory[
                        key
                    ];


                const allowed =
                    Array.isArray(
                        expected
                    )
                        ? expected
                        : [expected];


                const matched =
                    allowed.some(
                        (value) =>
                            normalizeChoice(
                                actual
                            ) ===
                            normalizeChoice(
                                value
                            )
                    );


                if (!matched) {
                    return false;
                }
            }


            return true;
        }


        // =============================================================
        // NATURAL BOT TIMING
        // =============================================================

        function getBotMessageDelay(item) {
            const configured =
                Math.max(
                    0,
                    Number(
                        item?.delay
                    ) || 0
                );


            return Math.min(
                BOT_TIMING
                    .maxMessageDelay,

                Math.max(
                    BOT_TIMING
                        .minMessageDelay,

                    configured *
                        BOT_TIMING
                            .delayMultiplier +
                    randomBetween(
                        200,
                        650
                    )
                )
            );
        }


        function getBotTypingDuration(
            item,
            text
        ) {
            if (
                item?.typing === false ||
                item?.typing === 0
            ) {
                return 0;
            }


            const configured =
                Math.max(
                    0,
                    Number(
                        item?.typing
                    ) || 0
                ) *
                BOT_TIMING
                    .typingMultiplier;


            const byLength =
                String(
                    text || ""
                ).length *
                    BOT_TIMING
                        .typingPerCharacter +
                randomBetween(
                    350,
                    850
                );


            return Math.min(
                BOT_TIMING
                    .maxTypingDuration,

                Math.max(
                    BOT_TIMING
                        .minTypingDuration,

                    configured,

                    byLength
                )
            );
        }


        // =============================================================
        // AVATARS
        // =============================================================

        function initials(name) {
            return String(
                name || "?"
            )
                .split(/\s+/)

                .filter(
                    Boolean
                )

                .slice(
                    0,
                    2
                )

                .map(
                    (part) =>
                        part[0]
                            .toUpperCase()
                )

                .join("") ||
                "?";
        }


        function defaultAvatar(
            name,
            hue = 220
        ) {
            const label =
                initials(name);


            const svg =
                `
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="128"
                    height="128"
                    viewBox="0 0 128 128"
                >
                    <defs>
                        <linearGradient
                            id="g"
                            x1="0"
                            y1="0"
                            x2="1"
                            y2="1"
                        >
                            <stop
                                stop-color="hsl(${hue} 78% 58%)"
                            />

                            <stop
                                offset="1"
                                stop-color="hsl(${(hue + 38) % 360} 72% 38%)"
                            />
                        </linearGradient>
                    </defs>

                    <rect
                        width="128"
                        height="128"
                        rx="64"
                        fill="url(#g)"
                    />

                    <text
                        x="64"
                        y="72"
                        text-anchor="middle"
                        font-family="Arial,sans-serif"
                        font-size="42"
                        font-weight="700"
                        fill="white"
                    >
                        ${label}
                    </text>
                </svg>
                `;


            return (
                "data:image/svg+xml;charset=utf-8," +
                encodeURIComponent(
                    svg
                )
            );
        }


        function avatarFor(
            conversation
        ) {
            return (
                safeStorageGet(
                    `sololearn-avatar:${conversation.id}`
                ) ||
                defaultAvatar(
                    conversation.name,
                    conversation.avatarHue
                )
            );
        }


        // =============================================================
        // DATE / TIME
        // =============================================================

        function formatMessageTime(
            date = new Date()
        ) {
            return date
                .toLocaleTimeString(
                    [],
                    {
                        hour:
                            "numeric",

                        minute:
                            "2-digit"
                    }
                );
        }


        function formatSeparator(
            datetime
        ) {
            const date =
                new Date(
                    String(
                        datetime
                    ).replace(
                        " ",
                        "T"
                    )
                );


            if (
                Number.isNaN(
                    date.getTime()
                )
            ) {
                return String(
                    datetime || ""
                ).toUpperCase();
            }


            return date
                .toLocaleString(
                    "en-US",
                    {
                        month:
                            "short",

                        day:
                            "numeric",

                        hour:
                            "numeric",

                        minute:
                            "2-digit",

                        hour12:
                            true
                    }
                )

                .replace(
                    ",",
                    ""
                )

                .replace(
                    " AM",
                    "AM"
                )

                .replace(
                    " PM",
                    "PM"
                )

                .toUpperCase()

                .replace(
                    /(\d{1,2}:\d{2}(?:AM|PM))$/,
                    "AT $1"
                );
        }


        // =============================================================
        // MESSAGE RENDERING
        // =============================================================

        function scrollToBottom(
            behavior = "smooth"
        ) {
            requestAnimationFrame(
                () =>
                    messagesEl
                        .scrollTo({
                            top:
                                messagesEl
                                    .scrollHeight,

                            behavior
                        })
            );
        }


        function createDateSeparator(
            datetime
        ) {
            const separator =
                document.createElement(
                    "div"
                );


            separator.className =
                "date-separator";


            const label =
                document.createElement(
                    "span"
                );


            label.className =
                "date-separator-label";


            label.textContent =
                formatSeparator(
                    datetime
                );


            separator.appendChild(
                label
            );


            messagesEl.appendChild(
                separator
            );


            scrollToBottom();
        }


        function createMessage(
            side,
            text,
            timestamp = null
        ) {
            const row =
                document.createElement(
                    "div"
                );


            row.className =
                `message-row ${side}`;


            const column =
                document.createElement(
                    "div"
                );


            column.className =
                "message-column";


            const bubble =
                document.createElement(
                    "div"
                );


            bubble.className =
                "message-bubble";


            const messageText =
                document.createElement(
                    "span"
                );


            messageText.className =
                "message-text";


            messageText.textContent =
                replaceEmojiShortcodes(
                    text
                );


            const time =
                document.createElement(
                    "span"
                );


            time.className =
                "message-timestamp";


            time.textContent =
                timestamp ||
                formatMessageTime();


            bubble.append(
                messageText,
                time
            );


            column.appendChild(
                bubble
            );


            row.appendChild(
                column
            );


            messagesEl.appendChild(
                row
            );


            scrollToBottom();


            return row;
        }


        function createImageMessage(
            side,
            imageSource,
            timestamp = null,
            caption = ""
        ) {
            const row =
                document.createElement(
                    "div"
                );


            row.className =
                `message-row ${side}`;


            const column =
                document.createElement(
                    "div"
                );


            column.className =
                "message-column image-message-column";


            const bubble =
                document.createElement(
                    "div"
                );


            bubble.className =
                "message-bubble image-message-bubble";


            const img =
                document.createElement(
                    "img"
                );


            img.className =
                "message-image";


            img.src =
                imageSource;


            img.alt =
                caption ||
                "Image";


            bubble.appendChild(
                img
            );


            if (caption) {
                const captionEl =
                    document.createElement(
                        "div"
                    );


                captionEl.className =
                    "message-image-caption";


                captionEl.textContent =
                    replaceEmojiShortcodes(
                        caption
                    );


                bubble.appendChild(
                    captionEl
                );
            }


            const time =
                document.createElement(
                    "div"
                );


            time.className =
                "message-image-timestamp";


            time.textContent =
                timestamp ||
                formatMessageTime();


            bubble.appendChild(
                time
            );


            column.appendChild(
                bubble
            );


            row.appendChild(
                column
            );


            messagesEl.appendChild(
                row
            );


            img.addEventListener(
                "load",
                () =>
                    scrollToBottom()
            );


            scrollToBottom();
        }


        function createTypingIndicator() {
            const row =
                document.createElement(
                    "div"
                );


            row.className =
                "message-row incoming typing-row";


            row.innerHTML =
                `
                <div class="message-column">
                    <div class="message-bubble typing-bubble">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
                `;


            messagesEl.appendChild(
                row
            );


            scrollToBottom();


            return row;
        }


        // =============================================================
        // COMPOSER
        // =============================================================

        function resizeComposer() {
            inputEl.style.height =
                "auto";


            inputEl.style.height =
                `${
                    Math.min(
                        inputEl.scrollHeight,
                        110
                    )
                }px`;
        }


        async function deleteComposerText(
            speed = 35,
            token = playbackToken
        ) {
            while (
                inputEl.value.length >
                    0 &&
                token ===
                    playbackToken
            ) {
                inputEl.value =
                    inputEl.value.slice(
                        0,
                        -1
                    );


                playBackspaceSound();

                resizeComposer();


                await sleep(
                    Number(
                        speed || 35
                    ) +
                    Math.floor(
                        Math.random() *
                        25
                    )
                );
            }
        }


        async function typeIntoComposer(
            text,
            speed = 55,
            pauseAt = {},
            mistakeChance = 0.06,
            token = playbackToken
        ) {
            inputEl.value =
                "";


            inputEl.focus();

            resizeComposer();


            const characters =
                String(
                    text ?? ""
                );


            const typoCharacters =
                "abcdefghijklmnopqrstuvwxyz";


            for (
                let i = 0;
                i < characters.length &&
                token === playbackToken;
                i++
            ) {
                const correct =
                    characters[i];


                const makeMistake =
                    /[a-z]/i.test(
                        correct
                    ) &&
                    Math.random() <
                        mistakeChance;


                if (makeMistake) {
                    let wrong =
                        typoCharacters[
                            Math.floor(
                                Math.random() *
                                typoCharacters.length
                            )
                        ];


                    if (
                        correct ===
                        correct.toUpperCase()
                    ) {
                        wrong =
                            wrong.toUpperCase();
                    }


                    inputEl.value +=
                        wrong;


                    playTypingSound();

                    resizeComposer();


                    await sleep(
                        120 +
                        Math.random() *
                        280
                    );


                    if (
                        token !==
                        playbackToken
                    ) {
                        return;
                    }


                    inputEl.value =
                        inputEl.value.slice(
                            0,
                            -1
                        );


                    playBackspaceSound();

                    resizeComposer();


                    await sleep(
                        80 +
                        Math.random() *
                        180
                    );
                }


                if (
                    token !==
                    playbackToken
                ) {
                    return;
                }


                inputEl.value +=
                    correct;


                playTypingSound();

                resizeComposer();


                inputEl.scrollTop =
                    inputEl.scrollHeight;


                const pauseKey =
                    i + 1;


                if (
                    Object.prototype
                        .hasOwnProperty.call(
                            pauseAt || {},
                            pauseKey
                        )
                ) {
                    await sleep(
                        Number(
                            pauseAt[
                                pauseKey
                            ]
                        ) || 0
                    );
                }


                await sleep(
                    Number(
                        speed || 55
                    ) +
                    Math.floor(
                        Math.random() *
                        45
                    )
                );
            }
        }


        // =============================================================
        // PLAY INCOMING
        // =============================================================

        async function playIncoming(
            item,
            token
        ) {
            const text =
                resolveVariables(
                    item.text || ""
                );


            const caption =
                resolveVariables(
                    item.caption || ""
                );


            /*
             * ARIA pauses before typing.
             */
            await sleep(
                getBotMessageDelay(
                    item
                )
            );


            if (
                token !==
                playbackToken
            ) {
                return false;
            }


            const typingDuration =
                getBotTypingDuration(
                    item,
                    text ||
                    caption
                );


            if (
                typingDuration > 0
            ) {
                const typing =
                    createTypingIndicator();


                await sleep(
                    typingDuration
                );


                typing.remove();


                if (
                    token !==
                    playbackToken
                ) {
                    return false;
                }
            }


            playReceiveSound();


            if (item.image) {
                createImageMessage(
                    "incoming",
                    item.image,
                    item.timestamp,
                    caption
                );

            } else {
                createMessage(
                    "incoming",
                    text,
                    item.timestamp
                );
            }


            if (text) {
                chatHistory.push({
                    sender:
                        "other",

                    text
                });
            }


            return true;
        }


        // =============================================================
        // PLAY OUTGOING
        // =============================================================

        async function playOutgoing(
            item,
            token
        ) {
            await sleep(
                Number(
                    item.delay ||
                    0
                )
            );


            if (
                token !==
                playbackToken
            ) {
                return false;
            }


            if (item.image) {
                await sleep(
                    Number(
                        item.sendDelay ||
                        0
                    )
                );


                if (
                    token !==
                    playbackToken
                ) {
                    return false;
                }


                playSendSound();


                createImageMessage(
                    "outgoing",
                    item.image,
                    item.timestamp,
                    resolveVariables(
                        item.caption ||
                        ""
                    )
                );


                return true;
            }


            const draft =
                resolveVariables(
                    item.draft ||
                    ""
                );


            const text =
                resolveVariables(
                    item.text ||
                    ""
                );


            if (item.compose) {
                if (draft) {
                    await typeIntoComposer(
                        draft,

                        item.typingSpeed ||
                            65,

                        item.draftPauseAt ||
                            {},

                        item.draftMistakeChance ??
                            item.mistakeChance ??
                            0.06,

                        token
                    );


                    await sleep(
                        Number(
                            item.draftPause ||
                            1000
                        )
                    );


                    if (
                        token !==
                        playbackToken
                    ) {
                        return false;
                    }


                    await deleteComposerText(
                        item.deleteSpeed ||
                            35,

                        token
                    );


                    await sleep(
                        Number(
                            item.recomposePause ||
                            500
                        )
                    );
                }


                await typeIntoComposer(
                    text,

                    item.recomposeTypingSpeed ??
                        item.typingSpeed ??
                        65,

                    item.pauseAt ||
                        {},

                    item.mistakeChance ??
                        0.06,

                    token
                );


                await sleep(
                    Number(
                        item.sendDelay ||
                        0
                    )
                );


                if (
                    token !==
                    playbackToken
                ) {
                    return false;
                }


                inputEl.value =
                    "";


                resizeComposer();
            }


            playSendSound();


            createMessage(
                "outgoing",
                text,
                item.timestamp
            );


            if (text) {
                chatHistory.push({
                    sender:
                        "me",

                    text
                });
            }


            return true;
        }


        // =============================================================
        // PLAYBACK
        // =============================================================

        async function continuePlayback() {
            if (
                !activeConversation ||
                playbackRunning
            ) {
                return;
            }


            playbackRunning =
                true;


            const token =
                playbackToken;


            try {
                while (
                    playbackIndex <
                        activeConversation
                            .messages
                            .length &&
                    token ===
                        playbackToken
                ) {
                    const item =
                        activeConversation
                            .messages[
                                playbackIndex
                            ];


                    playbackIndex +=
                        1;


                    /*
                     * Conditional branch.
                     */
                    if (
                        !shouldPlayItem(
                            item
                        )
                    ) {
                        continue;
                    }


                    /*
                     * Date separator.
                     */
                    if (
                        item.type ===
                        "date_separator"
                    ) {
                        createDateSeparator(
                            item.datetime
                        );

                        continue;
                    }


                    /*
                     * Wait for human reply.
                     */
                    if (
                        item.type ===
                        "wait_for_response"
                    ) {
                        awaitingVisitorResponse =
                            true;


                        keepInteractiveAfterReply =
                            Boolean(
                                item.keepInteractive
                            );


                        currentWaitItem =
                            item;


                        inputEl.focus();


                        return;
                    }


                    const ok =
                        item.sender ===
                            "other"
                            ? await playIncoming(
                                item,
                                token
                            )
                            : await playOutgoing(
                                item,
                                token
                            );


                    if (!ok) {
                        return;
                    }
                }

            } catch (error) {
                console.error(
                    "Conversation playback error:",
                    error
                );

            } finally {
                playbackRunning =
                    false;
            }
        }


        // =============================================================
        // LOCAL GENERATED REPLIES
        // =============================================================

        function generateLocalReply(
            message,
            mode = "friendly"
        ) {
            const text =
                String(
                    message || ""
                )
                    .trim()
                    .toLowerCase();


            const choose =
                (items) =>
                    items[
                        Math.floor(
                            Math.random() *
                            items.length
                        )
                    ];


            if (!text) {
                return "I’m here.";
            }


            if (
                /\b(hi|hey|hello|sup)\b/
                    .test(
                        text
                    )
            ) {
                return choose([
                    "Hey 🙂 what’s going on?",
                    "Hey. I’m here.",
                    "Hi 🙂 tell me what’s up."
                ]);
            }


            if (
                /\b(sorry|apologize|apology)\b/
                    .test(
                        text
                    )
            ) {
                return choose([
                    "I appreciate you saying that.",
                    "Thank you. I needed to hear that.",
                    "Okay. I’m listening."
                ]);
            }


            if (
                /\b(love|miss you|missed you)\b/
                    .test(
                        text
                    )
            ) {
                return choose([
                    "That’s a lot to take in, but I hear you.",
                    "I’ve missed parts of this too.",
                    "I wasn’t expecting you to say that."
                ]);
            }


            if (
                /\b(mad|angry|upset|hurt)\b/
                    .test(
                        text
                    )
            ) {
                return choose([
                    "I can tell this is still bothering you.",
                    "I get why you’re upset.",
                    "I’m not trying to make this worse."
                ]);
            }


            if (
                /\b(why|how|what|when|where|\?)\b/
                    .test(
                        text
                    )
            ) {
                return choose([
                    "That’s fair. I’ve been trying to figure that out too.",
                    "I don’t have a perfect answer, but I can be honest with you.",
                    "I was wondering when you’d ask me that."
                ]);
            }


            if (
                mode ===
                "direct"
            ) {
                return choose([
                    "I mean… that’s kind of what it felt like.",
                    "I’m not trying to start a fight. I’m telling you how it looked from my side.",
                    "You can tell me I’m wrong. I just wanted an honest answer."
                ]);
            }


            return choose([
                "That makes sense. Tell me a little more.",
                "I hear you. I’m not judging you for it.",
                "Okay… I can understand where you’re coming from.",
                "I’m listening. Keep going."
            ]);
        }


        // =============================================================
        // RESUME AFTER USER
        // =============================================================

        function resumeAfterUserResponse(
            waitItem
        ) {
            awaitingVisitorResponse =
                false;


            keepInteractiveAfterReply =
                false;


            currentWaitItem =
                null;


            /*
             * Each wait can override
             * this with:
             *
             * afterResponseDelay: 3000
             */
            const delay =
                Number(
                    waitItem
                        ?.afterResponseDelay
                ) ||
                randomBetween(
                    BOT_TIMING
                        .afterUserMin,

                    BOT_TIMING
                        .afterUserMax
                );


            setTimeout(
                () =>
                    continuePlayback(),
                delay
            );
        }


        // =============================================================
        // GENERATED REPLY
        // =============================================================

        async function requestGeneratedReply() {
            if (
                !activeConversation ||
                generatedReplyInProgress
            ) {
                return;
            }


            generatedReplyInProgress =
                true;


            awaitingVisitorResponse =
                false;


            const token =
                playbackToken;


            const waitItem =
                currentWaitItem;


            let typing =
                null;


            try {
                const latest =
                    [...chatHistory]
                        .reverse()

                        .find(
                            (item) =>
                                item.sender ===
                                "me"
                        )
                        ?.text ||
                    "";


                const reply =
                    generateLocalReply(
                        latest,
                        activeConversation
                            .localReplyMode
                    );


                /*
                 * Think BEFORE dots show.
                 */
                await sleep(
                    randomBetween(
                        BOT_TIMING
                            .generatedThinkMin,

                        BOT_TIMING
                            .generatedThinkMax
                    )
                );


                if (
                    token !==
                    playbackToken
                ) {
                    return;
                }


                typing =
                    createTypingIndicator();


                const typingDelay =
                    Math.min(
                        BOT_TIMING
                            .maxTypingDuration,

                        Math.max(
                            BOT_TIMING
                                .minTypingDuration,

                            reply.length *
                                46 +
                            randomBetween(
                                450,
                                950
                            )
                        )
                    );


                await sleep(
                    typingDelay
                );


                if (
                    token !==
                    playbackToken
                ) {
                    return;
                }


                typing.remove();

                typing =
                    null;


                playReceiveSound();


                createMessage(
                    "incoming",
                    reply
                );


                chatHistory.push({
                    sender:
                        "other",

                    text:
                        reply
                });


                if (
                    keepInteractiveAfterReply
                ) {
                    awaitingVisitorResponse =
                        true;

                } else {
                    resumeAfterUserResponse(
                        waitItem
                    );
                }

            } finally {
                if (
                    typing
                        ?.isConnected
                ) {
                    typing.remove();
                }


                generatedReplyInProgress =
                    false;
            }
        }


        // =============================================================
        // USER SEND MESSAGE
        // =============================================================

        function sendManualMessage() {
            const raw =
                inputEl.value
                    .trim();


            if (
                !raw ||
                !activeConversation
            ) {
                return;
            }


            const value =
                replaceEmojiShortcodes(
                    raw
                );


            inputEl.value =
                "";


            resizeComposer();


            playSendSound();


            createMessage(
                "outgoing",
                value
            );


            chatHistory.push({
                sender:
                    "me",

                text:
                    value
            });


            if (
                !awaitingVisitorResponse
            ) {
                return;
            }


            /*
             * Save current wait item
             * before it gets cleared.
             */
            const waitItem =
                currentWaitItem;


            /*
             * Save raw answer and
             * interpreted choice.
             */
            rememberVisitorResponse(
                value
            );


            if (
                waitItem
                    ?.generateReply ===
                false
            ) {
                resumeAfterUserResponse(
                    waitItem
                );

                return;
            }


            requestGeneratedReply();
        }


        // =============================================================
        // FILE -> DATA URL
        // =============================================================

        function fileToDataUrl(
            file
        ) {
            return new Promise(
                (
                    resolve,
                    reject
                ) => {
                    const reader =
                        new FileReader();


                    reader.onload =
                        () =>
                            resolve(
                                String(
                                    reader.result ||
                                    ""
                                )
                            );


                    reader.onerror =
                        () =>
                            reject(
                                reader.error ||
                                new Error(
                                    "Unable to read file."
                                )
                            );


                    reader.readAsDataURL(
                        file
                    );
                }
            );
        }


        // =============================================================
        // SEND IMAGE
        // =============================================================

        async function sendImage(file) {
            if (
                !file ||
                !activeConversation ||
                !file.type
                    .startsWith(
                        "image/"
                    )
            ) {
                return;
            }


            const source =
                await fileToDataUrl(
                    file
                );


            playSendSound();


            createImageMessage(
                "outgoing",
                source
            );


            chatHistory.push({
                sender:
                    "me",

                text:
                    "[Image]"
            });


            if (
                !awaitingVisitorResponse
            ) {
                return;
            }


            const waitItem =
                currentWaitItem;


            rememberVisitorResponse(
                "[Image]"
            );


            if (
                waitItem
                    ?.generateReply ===
                false
            ) {
                resumeAfterUserResponse(
                    waitItem
                );

                return;
            }


            requestGeneratedReply();
        }


        // =============================================================
        // CONVERSATION LIST
        // =============================================================

        function renderConversationList() {
            conversationListEl
                .replaceChildren();


            APP_DATA
                .conversations
                .forEach(
                    (
                        conversation
                    ) => {

                        const card =
                            document.createElement(
                                "button"
                            );


                        card.className =
                            "conversation-card";


                        card.type =
                            "button";


                        const avatar =
                            document.createElement(
                                "img"
                            );


                        avatar.className =
                            "conversation-card-avatar";


                        avatar.src =
                            avatarFor(
                                conversation
                            );


                        avatar.alt =
                            "";


                        const content =
                            document.createElement(
                                "div"
                            );


                        content.className =
                            "conversation-card-content";


                        const top =
                            document.createElement(
                                "div"
                            );


                        top.className =
                            "conversation-card-top";


                        const name =
                            document.createElement(
                                "strong"
                            );


                        name.textContent =
                            conversation.name;


                        const arrow =
                            document.createElement(
                                "span"
                            );


                        arrow.className =
                            "conversation-arrow";


                        arrow.textContent =
                            "›";


                        top.append(
                            name,
                            arrow
                        );


                        const preview =
                            document.createElement(
                                "div"
                            );


                        preview.className =
                            "conversation-preview";


                        preview.textContent =
                            conversation
                                .description;


                        content.append(
                            top,
                            preview
                        );


                        card.append(
                            avatar,
                            content
                        );


                        card.addEventListener(
                            "click",

                            () =>
                                openConversation(
                                    conversation.id
                                )
                        );


                        conversationListEl
                            .appendChild(
                                card
                            );
                    }
                );
        }


        // =============================================================
        // OPEN CONVERSATION
        // =============================================================

        function openConversation(id) {
            const conversation =
                APP_DATA
                    .conversations
                    .find(
                        (item) =>
                            item.id ===
                            id
                    );


            if (!conversation) {
                return;
            }


            /*
             * Only once.
             */
            playbackToken +=
                1;


            activeConversation =
                conversation;


            /*
             * Used by CSS bubble themes.
             */
            conversationView
                .dataset
                .conversationId =
                conversation.id;


            playbackIndex =
                0;


            playbackRunning =
                false;


            awaitingVisitorResponse =
                false;


            generatedReplyInProgress =
                false;


            keepInteractiveAfterReply =
                false;


            currentWaitItem =
                null;


            conversationMemory =
                Object.create(
                    null
                );


            chatHistory =
                [];


            messagesEl
                .replaceChildren();


            inputEl.value =
                "";


            resizeComposer();


            contactName.textContent =
                conversation.name;


            contactAvatar.src =
                avatarFor(
                    conversation
                );


            listView
                .classList
                .add(
                    "hidden"
                );


            conversationView
                .classList
                .remove(
                    "hidden"
                );


            if (
                conversation
                    .startDate
            ) {
                createDateSeparator(
                    conversation
                        .startDate
                );
            }


            setTimeout(
                () =>
                    continuePlayback(),
                900
            );
        }


        // =============================================================
        // GO BACK
        // =============================================================

        function goBack() {
            playbackToken +=
                1;


            activeConversation =
                null;


            delete conversationView
                .dataset
                .conversationId;


            playbackRunning =
                false;


            awaitingVisitorResponse =
                false;


            generatedReplyInProgress =
                false;


            keepInteractiveAfterReply =
                false;


            currentWaitItem =
                null;


            conversationMemory =
                Object.create(
                    null
                );


            chatHistory =
                [];


            conversationView
                .classList
                .add(
                    "hidden"
                );


            listView
                .classList
                .remove(
                    "hidden"
                );


            modal
                .classList
                .add(
                    "hidden"
                );


            renderConversationList();
        }


        // =============================================================
        // REPLAY
        // =============================================================

        function replayConversation() {
            if (
                !activeConversation
            ) {
                return;
            }


            const id =
                activeConversation.id;


            modal
                .classList
                .add(
                    "hidden"
                );


            openConversation(
                id
            );
        }


        // =============================================================
        // EVENTS
        // =============================================================

        sendButton
            .addEventListener(
                "click",
                sendManualMessage
            );


        inputEl
            .addEventListener(
                "input",
                resizeComposer
            );


        inputEl
            .addEventListener(
                "keydown",

                (event) => {
                    if (
                        event.key ===
                            "Enter" &&
                        !event.shiftKey
                    ) {
                        event.preventDefault();

                        sendManualMessage();
                    }
                }
            );


        backButton
            .addEventListener(
                "click",
                goBack
            );


        infoButton
            .addEventListener(
                "click",

                () =>
                    modal
                        .classList
                        .remove(
                            "hidden"
                        )
            );


        closeModalButton
            .addEventListener(
                "click",

                () =>
                    modal
                        .classList
                        .add(
                            "hidden"
                        )
            );


        modal
            .addEventListener(
                "click",

                (event) => {
                    if (
                        event.target ===
                        modal
                    ) {
                        modal
                            .classList
                            .add(
                                "hidden"
                            );
                    }
                }
            );


        replayButton
            .addEventListener(
                "click",
                replayConversation
            );


        imageButton
            .addEventListener(
                "click",

                () =>
                    imageUpload
                        .click()
            );


        imageUpload
            .addEventListener(
                "change",

                async () => {
                    const file =
                        imageUpload
                            .files?.[0];


                    imageUpload.value =
                        "";


                    if (!file) {
                        return;
                    }


                    try {
                        await sendImage(
                            file
                        );

                    } catch (error) {
                        console.error(
                            error
                        );
                    }
                }
            );


        avatarUpload
            .addEventListener(
                "change",

                async () => {
                    const file =
                        avatarUpload
                            .files?.[0];


                    avatarUpload.value =
                        "";


                    if (
                        !file ||
                        !activeConversation ||
                        !file.type
                            .startsWith(
                                "image/"
                            )
                    ) {
                        return;
                    }


                    try {
                        const source =
                            await fileToDataUrl(
                                file
                            );


                        safeStorageSet(
                            `sololearn-avatar:${activeConversation.id}`,
                            source
                        );


                        contactAvatar.src =
                            source;

                    } catch (error) {
                        console.error(
                            "Avatar could not be loaded:",
                            error
                        );
                    }
                }
            );


        clearAvatarButton
            .addEventListener(
                "click",

                () => {
                    if (
                        !activeConversation
                    ) {
                        return;
                    }


                    safeStorageRemove(
                        `sololearn-avatar:${activeConversation.id}`
                    );


                    contactAvatar.src =
                        defaultAvatar(
                            activeConversation
                                .name,

                            activeConversation
                                .avatarHue
                        );


                    modal
                        .classList
                        .add(
                            "hidden"
                        );
                }
            );


        /*
         * Browsers require interaction
         * before Web Audio can start.
         */
        document
            .addEventListener(
                "click",

                async () => {
                    const ctx =
                        getAudioContext();


                    if (
                        ctx?.state ===
                        "suspended"
                    ) {
                        try {
                            await ctx
                                .resume();

                        } catch {
                            // Browser declined.
                        }
                    }
                },

                {
                    once:
                        true
                }
            );


        // =============================================================
        // START
        // =============================================================

        renderConversationList();
    }


    if (
        document.readyState ===
        "loading"
    ) {
        document.addEventListener(
            "DOMContentLoaded",
            bootTextMessageSimulator,
            {
                once:
                    true
            }
        );

    } else {
        bootTextMessageSimulator();
    }

})();
