<?php

class PointsSystem {

    // simple early-bird Bonus gradient
    public static function pointsForLobbyContribution(int $contributions): int {
        $values = [
            1 => 5,
            2 => 4,
            3 => 3
        ];

        return $values[$contributions] ?? 2;
    }

    public static function pointsForConversationTopic(): int {
        return 8;
    }

    public static function pointsForConversationMessage(): int {
        return 4;
    }
}