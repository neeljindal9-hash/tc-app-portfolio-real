<?php

class LearnedDetail {

    // matrix factorization, iterative machine learning, predicting bias


    // section 1: applying the existing vectors and testings
    public static function computeBias(array $user_vector, array $item_vector): float {
        $product= 0;
        for ($i = 0; $i < 3; $i++) {
            $product += $user_vector[$i]*$item_vector[$i];
        }

        $errorBound = 0;
        for ($i = 3; $i < 6; $i++) {
            $errorBound += $user_vector[$i]*$item_vector[$i];
        }
        return $product/(1 + abs($errorBound));
    }



    // section 2: refining the vectors
    public static function recreateUserTaskDetailData(array $userXtask, array $userXtaskCty): array { // compresses first array, then second array

        [$userMat1, $taskMat1] = self::compressUserTaskMatrix($userXtask, $userXtaskCty, 0.3, false);
        $absoluteResidualMatrix = self::absoluteDifference( self::multiplyUserTaskMatrices($userMat1, $taskMat1), $userXtask);
        $precisionMat = self::addMatrices($userXtaskCty,$absoluteResidualMatrix);

        [$userMat2, $taskMat2] = self::compressUserTaskMatrix($precisionMat, null, 0.3, true);

        $userMat = array_merge($userMat1, $userMat2);
        $taskMat = array_merge($taskMat1, $taskMat2);

        return [
            'users' => $userMat,
            'tasks' => $taskMat
        ];
    }

    private static function compressUserTaskMatrix(array $userXtask, ?array $weights, float $learningConstant, bool $penalizeOverEst):array {
        // latent dimensions is 3
        error_log(print_r($userXtask, true));
        

        $userCount = sizeof($userXtask);
        $taskCount = sizeof($userXtask[0]);
        error_log("U$userCount and T$taskCount");
        $userMat = [];
        $taskMat = [];


        // create randomized starting
        for ($k = 0; $k < 3; $k++) {
            $userMat[$k] = [];
            $taskMat[$k] = [];

            for ($i = 0; $i < $userCount; $i++) {
                $userMat[$k][$i] = (mt_rand(0, 1000) / 1000 - 0.5)*0.5;
            }

            for ($j = 0; $j < $taskCount; $j++) {
                $taskMat[$k][$j] = (mt_rand(0, 1000) / 1000 - 0.5)*0.5;
            }
        }


        for ($iteration = 0; $iteration < 1500; $iteration++) {
            if ($iteration % 100 === 0) {
                error_log("Error: i$iteration: ". self::matrixCompressionError($userXtask, self::multiplyUserTaskMatrices($userMat, $taskMat), $weights));
            }
            self::iterateMatrixCompression($userXtask, $userMat, $taskMat, $weights, $learningConstant, $penalizeOverEst);
            $learningConstant *= 0.999;
            
        }
        error_log("END ERROR: ". self::matrixCompressionError($userXtask, self::multiplyUserTaskMatrices($userMat, $taskMat), $weights));
        //error_log("User Matrix: ". print_r($userMat, true));
        //error_log("Task Matrix: ". print_r($taskMat, true));

        error_log("before: " . print_r($userXtask, true));
        error_log("after: " . print_r(self::multiplyUserTaskMatrices($userMat, $taskMat), true));
        return [$userMat, $taskMat];

    }

    private static function iterateMatrixCompression(array $userXtask, array &$U, array &$V, ?array $cWeights, float $lc, bool $penalizeOverEst) {
        // every user has 3 dimensions, every task has 3 dimensions -> dot product. Essentially weighting user "skills", seeing which are active.
        error_log("recieved weights: ". print_r($cWeights, true));
        error_log("recieved matrix: ". print_r($userXtask, true));
        error_log("___________");
        $U_new = [[],[],[]];
        $V_new = [[],[],[]];

        // for every user 'skill' vector
        for($i = 0; $i < sizeof($U); $i++) {
            for ($row = 0; $row < sizeof($U[$i]); $row++) {
                
                $d = 0;
                $observed= 0;
                for($task =0; $task < sizeof($userXtask[0]); $task++) {
                    if (($cWeights && $cWeights[$row][$task] === null) 
                        || $userXtask[$row][$task]  === null) continue;
                    $compEntry = $U[0][$row]*$V[0][$task] + $U[1][$row]*$V[1][$task] + $U[2][$row]*$V[2][$task]; // estimate
                    // partial derivative
                    $coeff = 1;
                    if ($penalizeOverEst && $compEntry > $userXtask[$row][$task]) {
                        $coeff = 6;
                    }
                    $d += $coeff*($cWeights ? StatsPrecision::getWeightGivenUncertainty($cWeights[$row][$task]) : 1)
                    *($compEntry - $userXtask[$row][$task])*$V[$i][$task];


                    $observed++;
                }
                if ($observed === 0) {$U_new[$i][$row] = $U[$i][$row]; continue;}

                $U_new[$i][$row] =  max(  min($U[$i][$row] - $lc*$d/$observed,1) ,  -1);
               
            }
        }


        // repeat for task vectors, using old data still though
        for ($i = 0; $i < sizeof($V); $i++) {
            for ($task = 0; $task < sizeof($V[$i]); $task++) {
                

                $d = 0;
                $observed = 0;
                for ($row = 0; $row < sizeof($userXtask); $row++) {
                    if (($cWeights && $cWeights[$row][$task] === null) || $userXtask[$row][$task]  === null) continue;
                    $compEntry = $U[0][$row] * $V[0][$task] + $U[1][$row] * $V[1][$task] + $U[2][$row] * $V[2][$task];
                    $coeff = 1;
                    if ($penalizeOverEst && $compEntry > $userXtask[$row][$task]) {
                        $coeff = 6;
                    }
                    $d += $coeff*($cWeights ?  StatsPrecision::getWeightGivenUncertainty($cWeights[$row][$task]) : 1) 
                    * ($compEntry - $userXtask[$row][$task]) *$U[$i][$row];
                    $observed++;
                }
                if ($observed === 0) {$V_new[$i][$task] = $V[$i][$task]; continue;}
               
                $V_new[$i][$task] = max(  min($V[$i][$task] - $lc*$d/$observed,1) ,  -1);
            }
        }

        $U = $U_new;
        $V = $V_new;

    }

    private static function matrixCompressionError(array $raw, array $compressed, ?array $weights = null): float {
        $error = 0;
        $hasWeights = $weights !== null;

        if ($hasWeights) {
            for ($i = 0; $i < sizeof($raw); $i++) {
                for ($j = 0; $j < sizeof($raw[$i]); $j++) {
                    if ($raw[$i][$j] === null) continue;
                    $error += (StatsPrecision::getWeightGivenUncertainty($weights[$i][$j]))*(($raw[$i][$j] - $compressed[$i][$j]) ** 2);
                }
            }
        } else {
            for ($i = 0; $i < sizeof($raw); $i++) {
                for ($j = 0; $j < sizeof($raw[$i]); $j++) {
                    if ($raw[$i][$j] === null) continue;
                    $error += ($raw[$i][$j] - $compressed[$i][$j]) ** 2;
                }
            }
        }
        
        return 0.5*$error;
    }



    // section 3: stock
    public static function multiplyUserTaskMatrices(array $userVect, array $taskVect): array {
        $userCount = sizeof($userVect[0]);
        $taskCount = sizeof($taskVect[0]);
        $latentDimensions = sizeof($userVect);

        $result = [];

        for ($user = 0; $user < $userCount; $user++) {
            $result[$user] = [];

            for ($task = 0; $task < $taskCount; $task++) {
                $sum = 0;

                for ($k = 0; $k < $latentDimensions; $k++) {
                    $sum += $userVect[$k][$user] * $taskVect[$k][$task];
                }

                $result[$user][$task] = $sum;
            }
        }

        return $result;
    }

    public static function absoluteDifference(array $matrixA, array $matrixB): array {
        $result = [];
        foreach ($matrixA as $i => $row) {
            foreach ($row as $j => $value) {
                if ($value === null || $matrixB[$i][$j] === null) continue;
                $result[$i][$j] = abs($value - $matrixB[$i][$j]);
            }
        }
        return $result;
    }

    public static function addMatrices(array $matrixA, array $matrixB): array {
        $result = [];

        foreach ($matrixA as $i => $row) {
            foreach ($row as $j => $value) {
                if ($value === null || $matrixB[$i][$j] === null) continue;
                $result[$i][$j] = $value + $matrixB[$i][$j];
            }
        }

        return $result;
    }

    

    
}