<?php

class ServerLobbyRepository

{
    public static $dayStructureRef = null;
    public static $daysTillLobbyCycleEndRef = null;

    private static function daysTillLobbyCycleEnd(string $todaydate): ?int {
        if (self::$daysTillLobbyCycleEndRef === null) {
            $prevMarker = 1;
            foreach ((self::dayStructures()) as $date => $letter) {
                if ($prevMarker === 1) {
                    if (in_array($letter, ['A', 'G'])) {
                        self::$daysTillLobbyCycleEndRef[$date] = 1;
                        $prevMarker = 1;
                    } elseif (in_array($letter, ['B', 'C', 'D', 'E'])) {
                        self::$daysTillLobbyCycleEndRef[$date] = 2;
                        $prevMarker = 2;
                    } elseif (in_array($letter, ['F1'])) {
                        self::$daysTillLobbyCycleEndRef[$date] = 3;
                        $prevMarker = 3;
                    } else {
                        // default - for very special days
                        $prevMarker = 1;
                    }
                } else {
                    self::$daysTillLobbyCycleEndRef[$date] = $prevMarker -1;
                    $prevMarker--;
                }
            }
        }
        return self::$daysTillLobbyCycleEndRef[$todaydate] ?? null;
    }

    private static function getLastStartDate(): ?DateTimeImmutable {
        $prevDate = null;
        for ($i = 1; $i < 6; $i += 1) {
            $daysTillEnd = self::daysTillLobbyCycleEnd( (new DateTimeImmutable("-$i days"))->format("Y-m-d") );
            if ( $daysTillEnd === 1 && $prevDate !== null) {
                //error_log("last start date: -$prevDate days");
                return (new DateTimeImmutable("-$prevDate days"))->setTime(1, 30);
            }
            if ($daysTillEnd !== null) {
                $prevDate = $i;
            }
            
        }
        return null;
    }
    private static function dayStructures(): array
    {
        if (self::$dayStructureRef === null) {
            self::$dayStructureRef = json_decode(file_get_contents(__DIR__ . '/../../public/assets/schedule/year_schedule.json'), true);
        }
        return self::$dayStructureRef;
    }

    public static function convoNeedsActiveLobby(int $convo_id, DatabaseConnection $db): bool {
        return (!self::dailyLobbyExists($convo_id, "daily_class_lobby", $db));
    }
    public static function createDailyLobby(int $convo_id, DatabaseConnection $db): bool
    // creates lobby component by the name of daily_class_lobby
    {
        $date = date('Y-m-d');
        
        try {

            if (!$db->beginTransaction()) {
                return false;
            }

            $end_date = TimeManager::toSQLUTC(self::getEndDateForToday()); // assumption: the start date is now()
            if ($end_date === null) {
                throw new RuntimeException("ERROR-No Lobby should be created today.");
            }
            $desc = json_encode(['name' => 'daily_class_lobby']);

            $db->query("INSERT INTO lobby_components (convo_id, description, type, end_date) VALUES (?, ?, ?, ?)", [$convo_id, $desc, "class", $end_date]);
            $comp_id = $db->lastInsertId();
            if ($comp_id === false) {
                throw new RuntimeException("Insert lobby component failed.");
            }

            $items = [

                // type, group_id, item_descriptor, corrob_mode, is_int_data
                ['class', $comp_id, json_encode(['data-subgroup-id' => 0, 'label' => "Brief-in-a-word"]), 'merge', 0],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 0, 'label' => "Brief-general"]), 'summary', 0],

                ['class', $comp_id, json_encode(['data-subgroup-id' => 1, 'range' => 100,'label' => "Lecture"]), 'o-average', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 1,'range' => 100, 'label' => "Examination"]), 'o-average', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 1,'range' => 100, 'label' => "Homework"]), 'o-average', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 1,'range' => 100, 'label' => "Classwork"]), 'o-average', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 1,'range' => 100, 'label' => "Freetime"]), 'o-average', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 1,'range' => 100, 'label' => "Other"]), 'o-average', 1],

                ['class', $comp_id, json_encode(['data-subgroup-id' => 2, 'range' => 10, 'label' => "Intensity"]), 's-average', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 3, 'range' => 10, 'label' => "Cold-call Gauge"]), 's-average', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 4, 'range' => 10, 'label' => "Snooze-o-meter"]), 's-average', 1],

                ['class', $comp_id, json_encode(['data-subgroup-id' => 5, 'label' => "Substitute-existed"]), 'majority', 1],
                ['class', $comp_id, json_encode(['data-subgroup-id' => 5,'range' => 10, 'label' => "Substitute-ranking"]), 's-average', 1],


            ];

            $placeholders = [];
            $params = [];

            foreach ($items as [$type, $componentId, $name, $mode, $onlyInt]) {
                $placeholders[] = "(?,?,?,?, ?,?,?,?)";

                $params[] = $type;
                $params[] = $date;
                $params[] = $componentId;
                $params[] = $name;

                $params[] = $mode;
                $params[] = 1;                 // up_to_date = true
                $params[] = $convo_id;
                $params[] = $onlyInt;
            }

            $sql = "
                INSERT INTO lobby_items
                    (type, date, group_id, item_descriptor,
                    corrob_mode, up_to_date, convo_id, is_int_data)
                VALUES" . implode(",\n", $placeholders);
            
            $db->query($sql, $params);
            //error_log("Convo $convo_id daily lobby set up for $date.");
            
            
            $db->commit();
        

        } catch(Throwable $t) {
            $db->rollBack();
            error_log("ALERT: Convo $convo_id daily lobby FAILED;" . $t->getMessage());
            return false;
        }
        return true;


    }

    // checks if lobby exists that ends tomorrow or the next day or the next to see if something is still active - assumes max cycle of 3 days
    private static function dailyLobbyExists(int $convo_id, string $name_of_component, DatabaseConnection $db): bool {
        // if the current daily lobby ends TODAY, that means its expired
        $earliestEnd = (new DateTimeImmutable("+1 day"))->setTime(0,0,0); // an end date must be at or after this day to be current

        $currentEndDatesRows = $db->fetchAll("SELECT end_date, description FROM lobby_components WHERE convo_id = ? AND type = ?", [$convo_id, "class"]);
        $size = sizeof($currentEndDatesRows);

        foreach ($currentEndDatesRows as $itemRow) {
            $desc = json_decode($itemRow['description'], true);
            
            if (is_object($desc) && (($desc->name ?? null) !== $name_of_component)) {
                continue;
            }
            $endDate = TimeManager::toDateTimeCA1($itemRow['end_date']);
            if ($earliestEnd <= $endDate) {
                return true; // already there
            }
        }

        error_log("No class components were active.");

        return false;

    }

    public static function removeOldLobbyComponentsByType(string $type, DatabaseConnection $db): int {
        $removeLobbiesBeforeDate = TimeManager::toSQLUTC(new DateTimeImmutable("-10 days")); // 2 week elimination period
        $stmt = $db->query("DELETE FROM lobby_components WHERE type = ? AND end_date <= ?", [$type, $removeLobbiesBeforeDate]);
        return $stmt->rowCount();
    }


    public static function getEndDateForToday(): ?DateTimeImmutable {
        
        $todayDate = (new DateTimeImmutable())->format("Y-m-d");
        $daysTillCycleEnds = self::daysTillLobbyCycleEnd($todayDate);
        //error_log("Day end + for today cycle: " . $daysTillCycleEnds);


        if ($daysTillCycleEnds === null) {
            return null;
        }
        return (new DateTimeImmutable("+$daysTillCycleEnds days"));
    
    }

    public static function recomputeClassScores(DatabaseConnection $db): bool {
        // class score = average period score* (1 + number of periods/3) + class_bonuses (add later)
        try {
            if (!$db->beginTransaction()) {
                return false;
            }

            $convo_state_info = $db->fetchAll(
                "SELECT 
                    lobby_state + plaza_state AS virtual_state,
                    ID,
                    rankings
                    FROM conversations
                "
            );
            
            $done = true;
            foreach ($convo_state_info as $row) {
                $row['rankings'] = json_decode($row['rankings'] ?? '{}', true) ?? [];
                if (($row['virtual_state'] ?? -2) !== ($row['rankings']['virtual_state_after_last'] ?? -1)) {
                    $done = false;
                    break;
                }
            }
            if ($done == true) {
                $db->commit();
                return true;
            }

            $prev_states = array_column($convo_state_info, 'virtual_state', 'ID');
            $prev_rankings_data = array_column($convo_state_info, 'rankings', 'ID');


            $user_points_data = $db->fetchAll(
                "SELECT
                    uc.convo_id,
                    ucl.period,
                    SUM(uc.points) AS points
                FROM users_classes ucl
                JOIN classes c ON ucl.class_id = c.ID
                JOIN users_conversations uc 
                    ON uc.convo_id = c.convo_id
                    AND uc.user_id = ucl.user_id
                GROUP BY
                    uc.convo_id,
                    ucl.period
            ");
            // no non classes conversation (independent conversation) rows are created
            // only users in the class are taken, not the conversation (modbots can join convos but not classes)

            
            $user_points_data_formatted = [];
            foreach($user_points_data as $row) {
                $user_points_data_formatted[$row['convo_id']][$row['period']] = $row['points'];
            }

            $ranking_array= [];

            // computation
            foreach($user_points_data_formatted as $key => &$row) {
                $numberOfPeriods = sizeof($row);
                $sumPoints = array_sum($row);
                $total = ($sumPoints/$numberOfPeriods) * (1 + $numberOfPeriods/3);
                $row['total'] = $total;
                

                $ranking_array[$key] = $total;
                
            }
            unset($row);
            asort($ranking_array);

            $maxPlace = sizeof($ranking_array) + 15;

            foreach(array_keys($ranking_array) as $pos => $cid) {
                $user_points_data_formatted[$cid]['rank'] = $maxPlace - $pos;
            }
            
            foreach($user_points_data_formatted as $cid => &$row) {
                $sameTotal = isset($prev_rankings_data[$cid]['total'])
                    && is_numeric($prev_rankings_data[$cid]['total'])
                    && abs($row['total'] - (float) $prev_rankings_data[$cid]['total']) < 0.000001;

                $sameRank = isset($prev_rankings_data[$cid]['rank'])
                    && (int) $row['rank'] === (int) $prev_rankings_data[$cid]['rank'];

                if ($sameTotal && $sameRank) {
                    unset($user_points_data_formatted[$cid]);
                } else {
                    $row['virtual_state_after_last'] = $prev_states[$cid]+1;
                }
            }


            
            $numberOfClasses = count($user_points_data_formatted);
            if ($numberOfClasses === 0) {
                $db->commit();
                return true;
            }
            //error_log("Now updating $numberOfClasses class rank data...");

            $newValues = []; // store new lobby values: 0: ID, 1: updated value
            foreach($user_points_data_formatted as $key => $itemDataArray) {
                $newValues[] = [$key, json_encode($itemDataArray)];

            }


            // update database: consruct SQL string; update rankings by ID case with (WHEN ID THEN new value) pairs
            $cases = implode(' ', array_fill(0, count($newValues), 'WHEN ? THEN ?'));
            $convoPlaceholders = implode(',', array_fill(0, count(array_unique(array_column($newValues,0))), '?'));
            
            $params = array_merge(array_merge(...$newValues), array_column($newValues, 0));

            // use similar When Then structure for updating conversation info

            $db->query(
                "UPDATE conversations
                SET rankings = CASE ID 
                    $cases
                END,
                 lobby_state = lobby_state +1
                WHERE ID IN ($convoPlaceholders)",
                $params
            );
            return $db->commit();
        } catch (Throwable $t) {
            $db->rollBack();
            error_log("Caught error in ServerLobbyRepository recomputeClassScores: " . $t->getMessage());
            return false;
        }
    }

    public static function recomputeReliabilitiesGlobal(DatabaseConnection $db):bool {
        try {
            if (!$db->beginTransaction()) {
                return false;
            }

            $validTimeDate = TimeManager::toSQLUTCDayOnly(self::getLastStartDate());
            //error_log("timeStart: $validTimeDate (check with DB)");
            
            $items = $db->fetchAll('SELECT li.current_value, li.type, li.ID, li.item_descriptor, li.convo_id, li.corrob_mode, ld.user_id, COALESCE(ld.data_string, ld.data_int) as data FROM lobby_data ld JOIN lobby_items li ON ld.lobby_ref_id = li.ID WHERE li.date >= ? AND li.is_int_data = 1', [$validTimeDate]);

            $additItemData = [];
            // set up metadata
            foreach ($items as &$item) {
                $itemId = $item['ID'];
                $range = json_decode($item['item_descriptor'], true)['range'];
                $corrob = $item['corrob_mode'];
                if (!is_array($additItemData[$itemId] ?? null)) {
                    $additItemData[$itemId] = [
                        'count' => 0,
                        'values' => [],
                        'sum' => 0,
                        'range' => $range,
                        'mode' => $corrob
                    ];
                }
                $additItemData[$itemId]['count']++;
                $additItemData[$itemId]['values'][] = $item['data'];
                $additItemData[$itemId]['sum'] += $item['data'];

            }
            unset($item);

            // process metadata
            foreach ($additItemData as &$lobbyItem) {
                if ($lobbyItem['count'] <= 4) {
                    $lobbyItem['r1-elig'] = false;
                    continue;
                }
                $mean = ReliabilityWeighting::mean($lobbyItem['values'], $lobbyItem['mode'] !== "s-average"); // zero adjustment: ON (when its not s-average)
                $mad = ReliabilityWeighting::mad($lobbyItem['values'], $mean);
                $lobbyItem['mean'] = $mean;
                $lobbyItem['mad'] = $mad;
                $lobbyItem['r1-elig'] = true;

            }
            unset($lobbyItem);

            $users = [];
            // by users
            foreach ($items as $item) {
                if (!in_array($item['corrob_mode'], ['o-average', 's-average', 'majority'])) {
                    error_log("In serverLobby recomputeReliabilitie - invalid data c-type recieved: ". $item['corrob_mode']);
                    continue; // invalid data
                }
                //error_log($item['user_id'] . " / " . $item['data']);

                $user_id = $item['user_id'];
                if (!is_array($users[$user_id] ?? null)) {
                    $users[$user_id] = [
                        'ID' => $user_id,
                        'r1T' => 0,
                        
                        'r2V' => 0,
                        'r2W' => 0.001,

                        'r3V' => 0,

                        'count' => 0,
                        'active_convos' => []
                    ];

                }
                $data = $item['data'];

                $descriptor = json_decode($item['item_descriptor'], true);
                if (!is_array($descriptor) || !isset($descriptor['range'])) {
                    error_log("Invalid item descriptor for item " . $item['ID']);
                    continue;
                }

                $range = $descriptor['range'];

                if (!is_numeric($range) || $range <= 0) {
                    error_log("Invalid range for lobby item " . $item['ID']);
                    continue;
                }

                $lobbyItemData = $additItemData[$item['ID']];

                [$r1W, $r2W, $r3W] = ReliabilityWeighting::$weights[$item['corrob_mode']];

                $cValues = json_decode($item['current_value'], true);
                if (!is_array($cValues) || !is_numeric($cValues['reliability'] ??= 0) || !is_numeric($cValues['current_value'] ?? null)) {
                    error_log("Invalid item descriptor for item " . $item['ID']);
                    continue;
                }
                ['current_value' => $value, 'reliability' => $rel] = $cValues;
                $rel = $rel ** 1.4; // curve to be safer
                
                if ($lobbyItemData['r1-elig'] === true) {
                    $users[$user_id]['r1T'] += $rel*(ReliabilityWeighting::individualOutlierRiskValue($data, $lobbyItemData['mean'], $lobbyItemData['mad'], $range, $r1W));
                }
                if (!in_array($item['convo_id'], $users[$user_id]['active_convos'])) {
                    $users[$user_id]['active_convos'][] = $item['convo_id'];
                }
                
                $users[$user_id]['r2V'] += ($rel *$r2W * (abs($value - $data)/$range));
                //error_log("adding to r2V: " . "($rel *$r2W * (abs($value - $data)/$range))) = " . ($rel *$r2W * (abs($value - $data)/$range)));
                $users[$user_id]['r2W'] += ($rel *$r2W);
                

                $users[$user_id]['r3V'] += $r3W;

    
                $users[$user_id]['count']++;
            }

            if (count($users) < 1) {
                $db->commit();
                return true;
            }

            // format newValues data per user
            $newValues = [[],[],[]];
            foreach ($users as &$user) {

                $user['r2AWD']  = ( $user['r2W'] /  ($user['count']) ) * (    min(1,($user['count'])/4)      ) / 2.5;
                $user['r2T'] = exp(-12 *  $user['r2V']/($user['r2W']) );
                $user['r3T'] = min($user['r3V'] / (count($user['active_convos']) * 2.9),1); // 2.9 max numeric participation total - SUBJECT TO CHANGE

                $newValues[0][] = [$user['ID'], $user['r1T']];
                $newValues[1][] = [$user['ID'], $user['r2T']*$user['r2AWD'], $user['r2AWD']+1];
                $newValues[2][] = [$user['ID'], $user['r3T']];

            }
            unset($user);

            $cases1 = implode(' ', array_fill(0, count($users), 'WHEN ? THEN LEAST(GREATEST(rel_1 + ?, 0), 1)'));
            $cases2 = implode(' ', array_fill(0, count($users), 'WHEN ? THEN (rel_2 + ?)/?'));
            $cases3 = implode(' ', array_fill(0, count($users), 'WHEN ? THEN (6*rel_3 + ?)/7'));
            
            
            $params = array_merge(...$newValues[0], ...$newValues[1], ...$newValues[2]);

            $db->fetch(
                "UPDATE user_stats

                SET rel_1 = CASE user_id
                    $cases1
                    ELSE rel_1
                END,

                rel_2 = CASE user_id
                    $cases2
                    ELSE rel_2
                END,

                rel_3 = CASE user_id
                    $cases3
                    ELSE 0.86*rel_3
                END

            ", $params);


        
            $db->commit();
            error_log("success in serverLobby reliabilities calculations");
            return true;

            
        } catch(Throwable $t) {
            $db->rollBack();
            error_log("Caught error in ServerLobbyRepository recomputeReliabilitiesGlobal: " . $t->getMessage());
            return false;
        }
    }


    public static function recomputeLobbiesGlobal(DatabaseConnection $db): bool {
        try {
            if (!$db->beginTransaction()) {
                return false;
            }
            $lobby_data = $db->fetchAll(
                "SELECT COALESCE(ld.data_string, ld.data_int) AS data, ld.lobby_ref_id AS item_ref,
                li.corrob_mode, li.convo_id, li.item_descriptor,
                us.rel_1, us.rel_2, us.rel_3
                FROM lobby_data ld
                JOIN lobby_items li ON li.ID = ld.lobby_ref_id
                LEFT JOIN user_stats us ON us.user_id = ld.user_id
                WHERE li.up_to_date = false
                "
            );
            if (count($lobby_data) === 0) {
                $db->commit();
                return true;
            }
            $groupedRows = []; // store the user-inputted data for each item: item_ref/ID -> array of data points
            foreach ($lobby_data as $row) {
                $groupedRows[$row['item_ref']][] = $row;
            }
            $numberOfLobbyItems = count($groupedRows);

            // computation
            error_log("Now updating $numberOfLobbyItems Items...");
            $newValues = []; // store new lobby values: 0: ID, 1: updated value
            $convoIds = []; // store conversation IDs to update lobby_state
            foreach($groupedRows as $itemDataArray) {
                $newValues[] = self::recomputeData($itemDataArray);
                $convoIds[] = $itemDataArray[0]['convo_id'];
            }


            // update database: consruct SQL string; update current_value by ID case with (WHEN ID THEN new value) pairs
            $cases = implode(' ', array_fill(0, count($newValues), 'WHEN ? THEN ?'));
            $idPlaceholders = implode(',', array_fill(0, count($newValues), '?'));
            $convoPlaceholders = implode(',', array_fill(0, count(array_unique($convoIds)), '?'));
            
            $params = array_merge(array_merge(...$newValues), array_column($newValues, 0));

            $db->query(
                "UPDATE lobby_items
                SET current_value = CASE ID 
                    $cases
                END,
                up_to_date = true
                WHERE ID IN ($idPlaceholders)",
                $params
            );

            $db->query(
                "UPDATE conversations
                SET lobby_state = lobby_state +1
                WHERE ID IN ($convoPlaceholders)",
                array_unique($convoIds)
            );
            return $db->commit();
        } catch (Throwable $t) {
            $db->rollBack();
            error_log("Caught error in ServerLobbyRepository recomputeLobbiesGlobal: " . $t->getMessage());
            return false;
        }
    }

    private static function recomputeData($items): array {
        
        $corrob_mode = $items[0]['corrob_mode'];
        $ref_id = $items[0]['item_ref'];
        $info = json_decode($items[0]['item_descriptor'], true);
        //need weights from $id
        $itemWeights = ReliabilityWeighting::$weights[$corrob_mode];
        if ($corrob_mode === "o-average" ) {
            $new_value = ReliabilityWeighting::weightedAverageAndReliability($items, $itemWeights, $info['range'] ?? 100, "cluster_based", true, true);
        } else if ( $corrob_mode === "s-average") {
            $new_value = ReliabilityWeighting::weightedAverageAndReliability($items, $itemWeights, $info['range'] ?? 10, "size_based", false, false);
        } else if($corrob_mode === "majority") {
            $new_value = ReliabilityWeighting::majorityAndReliability($items, $itemWeights, "agreement_based");
        } else {
            $new_value = ["current_value" => implode(', ', array_unique(array_column($items, 'data')))];
        }
        
        return [$ref_id, json_encode($new_value)];
    }


    // heavy duty method - use sparingly
    public static function setUpDetailModel(DatabaseConnection $db): bool {
        try {
            if (!$db->beginTransaction()) {
                throw new RuntimeException('already in transaction');
            }

            $lobby_data = $db->fetchAll(
                "SELECT COALESCE(ld.data_string, ld.data_int) AS data, ld.lobby_ref_id AS item_ref,
                ld.user_id, li.item_descriptor, li.current_value, li.corrob_mode
                FROM lobby_data ld
                JOIN lobby_items li ON li.ID = ld.lobby_ref_id
                WHERE li.is_int_data = true"
            );

            $user_bias = [];

            foreach ($lobby_data as $contribution) {
                $descriptor = json_decode($contribution['item_descriptor'], true);
                $current = json_decode($contribution['current_value'], true);

                $task_id = $descriptor['data-subgroup-id'] ?? null;

                if (!$task_id || $task_id == 1 || $contribution['corrob_mode'] === 'majority') continue;

                $user_bias[$contribution['user_id']][$task_id][] = [
                    'bias' => ((float)$contribution['data'] - (float)$current['current_value']) / (float)$descriptor['range'],
                    'reliability' => (float)$current['reliability']
                ];
            }

            $weightedUserStats = StatsPrecision::processRawUserData($user_bias);
            $userKeys = $weightedUserStats['user_keys'];
            $taskKeys = $weightedUserStats['task_keys'];

            ['users' => $userMat, 'tasks' => $taskMat] = LearnedDetail::recreateUserTaskDetailData($weightedUserStats['biasMat'], $weightedUserStats['uncertMat']);

            $userCases = array_map(
                fn($userId) => "WHEN $userId THEN ". $db->quote(json_encode(array_column($userMat, $userKeys[$userId]))),
                array_keys($userKeys)
            );
            $userCases = implode(" ", $userCases);

            $taskRows = array_map(
                fn($taskId) => [
                    'task_id' => (int)$taskId,
                    'task_vector' => $db->quote(json_encode(array_column($taskMat, $taskKeys[$taskId])))
                ],
                array_keys($taskKeys)
            );

            // Insert new task vectors or replace existing ones.
            if (!empty($taskRows)) {
                $values = array_map(
                    fn($row) => "(" .
                        (int)$row['task_id'] . ", " .
                        $row['task_vector'] .
                    ")",
                    $taskRows
                );

                $db->query(
                    "INSERT INTO tasks (task_id, task_vector)
                    VALUES " . implode(", ", $values) . "
                    ON DUPLICATE KEY UPDATE
                        task_vector = VALUES(task_vector)"
                );
            }

            if (!empty($userCases)) {
                $db->query(
                    "UPDATE user_stats 
                    SET detail_vector = 
                        CASE user_id
                        $userCases
                        ELSE detail_vector
                    END;
                    "
                );
            }
            

            $db->commit();
            return true;
        } catch (Throwable $t) {
            $db->rollBack();
            error_log("Caught in setUpDetailModel" . $t->getMessage());
            return false;
        }

    }

    public static function resetAllPoints(DatabaseConnection $db) {
        $db->query("UPDATE users_conversations SET old_points = new_points + old_points, new_points = 0");
    }

}