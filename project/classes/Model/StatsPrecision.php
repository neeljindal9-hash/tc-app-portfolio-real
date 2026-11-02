<?php

class StatsPrecision {

    // precision and accuracy seperation, MAD-based certainty, error bounding estimation


    // measures how much a user tends to vary fom the truth, and how precise/consistent that bias is. Returns matrix representation of users and tasks
    public static function processRawUserData(array $user_data_array):array {
        $userXtask_bias = [];
        $userXtask_uncert = [];

        // these key dictionaries allow the compressed matrix array to be read as actual user_ids and task_ids - must be preserved
        $userKeys = [];
        $taskKeys = [];

        foreach ($user_data_array as $user_id => $user_bias_data) {
            if (!isset($userKeys[$user_id])) {
                $userKeys[$user_id] = count($userKeys);
                $userXtask_bias[] = [];
            }
            foreach($user_bias_data as $task_id => $user_task_all) {

                if (!isset($taskKeys[$task_id])) {
                    $taskKeys[$task_id] = count($taskKeys);
                }

                $sum = 0;
                $weights = 0;
                $count = 0;
                $biases = [];
                $weightsArray =[];
                foreach($user_task_all as $contribution) {
                    if ($contribution['reliability'] < 0.3) continue;
                    $sum += $contribution['reliability']*$contribution['bias'];
                    $weights += $contribution['reliability'];
                    $count++;
                    $biases[] = $contribution['bias'];
                    $weightsArray[] = $contribution['reliability'];
                }
                if ($count < 5) continue;

                $weightedAverageBias =$sum/$weights;
                $userXtask_bias[$userKeys[$user_id]][$taskKeys[$task_id]] = $weightedAverageBias;

                $averageRel = $weights/$count;
                $biasMAD = ReliabilityWeighting::weightedMad($biases, $weightsArray, 0.4, ReliabilityWeighting::weightedMean($biases, $weightsArray)); // bias mad is the same as user contribution set mad
                $computedUncertainty = 2*$biasMAD + .40*(2**(-1*$averageRel)) - .20;
                $userXtask_uncert[$userKeys[$user_id]][$taskKeys[$task_id]] = $computedUncertainty;

            }
        }

        // fill in empty slots as nulls
        for ($i = 0; $i < count($userKeys); $i++) {
            for ($j = 0; $j < count($taskKeys); $j++) {
                $userXtask_bias[$i][$j] ??= null;
                $userXtask_uncert[$i][$j] ??= null;
            }
        }

        return [
            'user_keys' => $userKeys,
            'task_keys' => $taskKeys,
            'biasMat' =>  $userXtask_bias,
            'uncertMat' =>  $userXtask_uncert
        ];


    }

    public static function getWeightGivenUncertainty(float $u): float { // $u is percent uncertainty from total range, not the value itself, weight must be less than 1
        return (9*exp(-5*$u)+1)/10; // floor 0.1
    }

}