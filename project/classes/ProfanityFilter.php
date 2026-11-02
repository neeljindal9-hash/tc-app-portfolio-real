<?php
# project/classes/ProfanityFilter.php
// Matthew - use this in static context only
class ProfanityFilter {

    public static function isClean(string $message): bool {
        $message = self::normalize($message);
        $message = self::keepOnlyLetters($message);
        return self::checkClean($message);
    }
    private static function keepOnlyLetters(string $text): string {
        $text = strtolower($text);
        return preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
    }
    private static function normalize(string $message): string {
        $message = strtolower($message);
        $decodeMap = [
            "*" => "a",
            "@" => "a",
            "$" => "s",
            "!" => "i",
            "|" => "i",
            "1" => "i",
            "0" => "o",
            "3" => "e",
            "4" => "a",
            "5" => "s",
            "7" => "t",
            "8" => "b",
            "2" => "z",
            "9" => "g",
        ];
        foreach ($decodeMap as $from => $to) {
            $message = str_replace($from, $to, $message);
        }
        return $message;
        
    }
    private static function buildPattern(string $word): string {
        $pattern = '';

        for ($i = 0; $i < strlen($word); $i++) {
            $pattern .= preg_quote($word[$i], '/') . '+';
        }

        return '/\b' . $pattern . '\b/i';
    }
    private static function checkClean(string $message): bool {
        $badWords = [
            // Core high-confidence profanity (minimal roots)
            "shit",
            "fuck",
            "bitch",
            "ass",
            "dick",
            "cunt",
            "piss",
            "crap",

            // Strong sexual / explicit roots
            "cock",
            "pussy",
            "cum",
            "clit",
            "penis",
            "vagina",
            "orgasm",
            "semen",
            "dildo",

            // Strong insults (non-slur versions)
            "bastard",
            "slut",
            "whore",
            "prick",
            "wanker",
            "twat",
            "douche",

            // Strong compound insult roots
            "fuckwit",
            "shithead",
            "dickhead",
            "asshole",

            // Violence / extreme insult words (keep minimal)
            "rape",
            "rapist",
            "pedo"
        ];
        foreach ($badWords as $bad) {
            $pattern = self::buildPattern($bad);
            if (preg_match($pattern, $message)) {
                return false;
            }
        }
        return true;
    }
}