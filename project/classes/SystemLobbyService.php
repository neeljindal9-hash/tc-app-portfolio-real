<?php


class SystemLobbyService {
    
    public static function handleDailyLobbiesCreation(DatabaseConnection $db): bool {
        $start_time = hrtime(true) / 1e6; 
        $conversationIDs = $db->fetchAll("SELECT ID FROM conversations");
        $numberOfConvos = count($conversationIDs);
        error_log("Total $numberOfConvos conversations. Handling their lobbies now...");
        $lobbies_created = 0;
        $lobbies_failed = 0;
        $lobbies_removed = 0;

        try {
            
            if (ServerLobbyRepository::getEndDateForToday() === null) {
                $elapsed = hrtime(true) / 1e6 - $start_time;
                $status = ServerLobbyRepository::recomputeReliabilitiesGlobal($db);
                error_log("Recomputed reliabilities: $status in $elapsed ms");

                error_log("No lobbies to be created today.");
            }
            else {
                // before the conclusion of the lobby
                
                $status = ServerLobbyRepository::recomputeReliabilitiesGlobal($db);
                error_log("Recomputed reliabilities: $status");

                
                $lobbies_removed = ServerLobbyRepository::removeOldLobbyComponentsByType("class", $db);
                foreach ($conversationIDs as $convo_id_row) {
                    $convo_id = $convo_id_row['ID'];
                    if (ServerLobbyRepository::convoNeedsActiveLobby($convo_id, $db)) {
                        
                        if (ServerLobbyRepository::createDailyLobby($convo_id, $db)) {
                            $lobbies_created++;
                        } else {
                            $lobbies_failed++;
                        }

                    }
                }
            }
            
        } catch(Throwable $t) {
            $elapsed = hrtime(true) / 1e6 - $start_time;
           
            error_log("CRITICAL - handleDailyLobbies FAILED in $elapsed ms (Didn't Complete); Removed $lobbies_removed old lobbies. Created $lobbies_created. Failed $lobbies_failed. error- ". $t->getMessage());
            return false;
        }
        $elapsed = hrtime(true) / 1e6 - $start_time;
       
        error_log("NOTICE: Completion in SystemLobbyService handleDailyLobbies() in $elapsed (total) ms! Removed $lobbies_removed old lobbies, Creating $lobbies_created lobbies, Failed $lobbies_failed.");
        return true;
    }

    public static function handleOtherDailyTasks(DatabaseConnection $db): bool {
        // rreset points, etc.
        try {
            ServerLobbyRepository::resetAllPoints($db);
            
            return true;
        } catch(Exception $er) {
            error_log("Other daily tasks failed in SystemLobbyService... We caught: ". $er->getMessage());
            return false;

        }
    }


    public static function triggerRecomputeAllLobbies(DatabaseConnection $db, int $time_counter): bool {
        $start_time = hrtime(true) / 1e6; 
        error_log("RecomputeLobbiesGlobal called.");
        
        $status =  ServerLobbyRepository::recomputeLobbiesGlobal($db);
        if ($status !== true) {
            error_log("RecomputeLobbiesGlobal Failed and Abandoned...");
        } else {
            $elapsed = hrtime(true) / 1e6 - $start_time;
            error_log("Notice: Success in recomputeLobbiesGlobal, in $elapsed ms!");
        }
        
        if ($time_counter === 0) {
            $status =  ServerLobbyRepository::recomputeClassScores($db);
            if ($status !== true) {
                error_log("RecomputeLobbiesClassScores Failed and Abandoned...");
            } else {
                $elapsed = hrtime(true) / 1e6 - $start_time;
                error_log("Notice: Success in recomputeClassScores, in $elapsed ms!");
            }
        }
        
        

        return $status;



    }

}