<?php
declare(strict_types=1);

class SessionRepository {

    private static int $max = 400;

    /**
     * Brings an existing session to the front (head) of the queue.
     * Use this on every request so active users aren't evicted.
     */
    public static function useID(array &$sessions, string $id): void {
        if (!isset($sessions[$id])) {
            return; // Session doesn't exist
        }
        if (($sessions['head'] ?? null) === $id) {
            return; // Already at the head, do nothing
        }

        // Pop it out of its current position, then push to head
        self::remove($sessions, $id, keepData: true);
        self::add($sessions, $id, isExisting: true);
    }

    /**
     * Safely removes a node and splices the 'front' and 'behind' pointers 
     * of its neighbors together.
     */
    public static function remove(array &$sessions, string $id, ?array &$session_data_fr = null, bool $keepData = false): void {
        if (!isset($sessions[$id])) {
            return;
        }

        $newerID = $sessions[$id]['front'] ?? null;
        $olderID = $sessions[$id]['behind'] ?? null;

        // 1. Fix the node in front of it (newer)
        if ($newerID !== null) {
            $sessions[$newerID]['behind'] = $olderID;
        } else {
            // If there's no newer node, this was the head.
            $sessions['head'] = $olderID;
        }

        // 2. Fix the node behind it (older)
        if ($olderID !== null) {
            $sessions[$olderID]['front'] = $newerID;
        } else {
            // If there's no older node, this was the tail.
            $sessions['tail'] = $newerID;
        }

        // 3. Delete from memory (unless we are just moving it)
        if (!$keepData) {
            unset($sessions[$id]);
            if ($session_data_fr !== null) {
                unset($session_data_fr[$id]);
            }
        }
    }

    /**
     * Adds a new session to the head. Evicts the tail if max capacity is reached.
     */
    public static function add(array &$sessions, string $id, ?array &$session_data_fr = null, bool $isExisting = false): void {
        
        if (!$isExisting && isset($sessions[$id])) { // is existing is a local hint for a different kind of implementation, key may still exist
            error_log("CRITICAL - duplicate ID attempted to be added in Session_data_repository");
            return;
        }

        // If the array is completely empty or head is null
        if (empty($sessions["head"])) {
            $sessions["head"] = $id;
            $sessions["tail"] = $id;
            $sessions[$id]['front'] = null;
            $sessions[$id]['behind'] = null;
            return;
        }

        // Eviction: Cut from the tail if we exceed max.
        // Subtract 2 to account for the 'head' and 'tail' string keys.
        if (!$isExisting && (count($sessions) - 2) >= self::$max) {
            if (!empty($sessions['tail'])) {
                self::remove($sessions, $sessions['tail'], $session_data_fr);
            }
        }

        // Wire up the new head
        $incumbentHeadID = $sessions['head'];

        $sessions[$id]['front'] = null;           // Head has nothing in front of it
        $sessions[$id]['behind'] = $incumbentHeadID; // Behind the new head is the old head
        
        if ($incumbentHeadID !== null && isset($sessions[$incumbentHeadID])) {
            $sessions[$incumbentHeadID]['front'] = $id;  // Old head's front points to new head
        }
        
        $sessions['head'] = $id;                     // Declare new head
    }

    /**
     * Perfect for session regeneration. Swaps an old ID for a new one in-place.
     */
    public static function replace(array &$sessions, string $old_id, string $new_id): void {
        if (!isset($sessions[$old_id])) {
            return;
        }

        // Copy the data payload and pointers
        $sessions[$new_id] = $sessions[$old_id];
        
        $newerID = $sessions[$old_id]['front'] ?? null;
        $olderID = $sessions[$old_id]['behind'] ?? null;

        // Update the adjacent 'newer' node to point to the new ID
        if ($newerID !== null) {
            $sessions[$newerID]['behind'] = $new_id;
        } else {
            $sessions['head'] = $new_id; // It was the head
        }

        // Update the adjacent 'older' node to point to the new ID
        if ($olderID !== null) {
            $sessions[$olderID]['front'] = $new_id;
        } else {
            $sessions['tail'] = $new_id; // It was the tail
        }

        // Destroy the old key
        unset($sessions[$old_id]);
    }
}