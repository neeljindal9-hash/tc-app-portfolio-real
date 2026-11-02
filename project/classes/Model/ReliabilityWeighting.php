<?php

class ReliabilityWeighting {

    // only include basic exc/error checks, meant to wrapped by callers - if it throws, that's good, if it hides, that's bad
    public static $weights = 
        [
            "o-average" => [0.35, 0.55, 0.1],
            "s-average" => [0.20, 0.25, 0.55],
            "majority" => [0.6, 0.45, 0.1]
        ];

    
    public static function weightedAverageAndReliability(array $items, array $WRelFactors, float $range, string $rel_type, bool $strictOutliers = false, bool $zeroAdjusted = false): array {
        if ($range === 0 || count($items) === 0) {
            error_log("invalid weightingAverage call...");
            throw new RuntimeException("Invalid data in WeightedAverage... abandoning attempt");
        }

        $weightTotal = 0;
        $totalContributions = 0;
        $reliabilitySum = 0; // of squares
        $usedContributions = 0;

        $mean = self::mean(array_column($items, 'data'), false);
        $mad = self::mad($items, $mean);
        
        $proportionOfZeros = (count(array_keys(array_column($items, 'data'), 0))/count($items));
        if ($zeroAdjusted && $proportionOfZeros >= 0.75) {
            return ["current_value" => 0, "reliability" => $proportionOfZeros];
        }

        foreach ($items as $item) {
            $outlierPenalty = self::outlierPenalty($item, $range, $strictOutliers, $mean, $mad, $item['rel_1'], $WRelFactors[0], count($items));
            if ($outlierPenalty > 0.2 || $outlierPenalty === null) {
                continue;
            }

            $unadjustedWeight =(1 - $item['rel_1'])*$WRelFactors[0] + ($item['rel_2'])*$WRelFactors[1] + ($item['rel_3'])*$WRelFactors[2];
            $weight =  $unadjustedWeight - $outlierPenalty;
            if ($weight <= 0) {
                continue;
            }

            $totalContributions += $item['data']*$weight;
            $reliabilitySum += ($unadjustedWeight / (array_sum($WRelFactors))) ** 2; // used to estimate general reliability of population, not just average the weight it received
            //error_log("Q rel Sum += ($unadjustedWeight / ". (array_sum($WRelFactors)) . ") ** 2 = ". ($unadjustedWeight / (array_sum($WRelFactors))) ** 2);
            $usedContributions++;
            $weightTotal += $weight;
        }

        if ($weightTotal > 0 && $usedContributions > 0) {
            $value = $totalContributions / $weightTotal;
            $reliability = self::computeReliability($rel_type, sqrt($reliabilitySum/ $usedContributions), $mad, $range, $usedContributions);
        } else {
            $value = null;
            $reliability = 0;
        }
        

        return ["current_value" => $value, "reliability" => $reliability];
    }

    private static function computeReliability(string $type, float $relQAvg, float $mad, float $range, int $count): ?float {
        if ($count === 0) {
            return 0;
        }
        
        $lowSize = $count <= 2; // behaves differently when given very limited data, MAD is no longer relevant
        $G = max(1 - (2.6 * $mad / $range) ** (1.8 - 0.49*log($range,10)), 0); // cluster reliability
        $F = min(0.82*(log($count/14, 10))+1, 1); // sample size reliability

        if ($type === "size_based") {
            if ($lowSize) {
                return (2/5) * $relQAvg + (4/7) * $F;
            }
            return min((3/7) * $relQAvg + (4/7) * $F + (1/10) * $G, 1);
        } else if ($type === "cluster_based") {
            if ($lowSize) {
                return (2/5) * $relQAvg + ((3-2*$count)/6) * $F + (($count-1)/3) * $G;
            }
            return min((1/2) * $G + (1/3) * $relQAvg + (1/4.3) * $F, 1);
        } else if ($type === "agreement_based") {
            if ($lowSize) {
                return (2/5) * $relQAvg + (4/7) * $F;
            }
            return min((4/7) * $G + (4/11) * $relQAvg + (3/7) * $F, 1);
        } else {
            return 0;
        }
    }

    public static function mean(array $numbers, bool $zeroAdjusted): ?float {
        if (count($numbers) === 0) {
            return null;
        }
        if ($zeroAdjusted) {
            $amountOfZeros = count(array_keys($numbers, 0)) / count($numbers);
            if ($amountOfZeros >= 0.75) {
                return 0.000;
            }
        }
        return array_sum($numbers) / count($numbers);
    }

    public static function weightedMean(array $numbers, array $weights): ?float {
        if (count($numbers) === 0) {
            return null;
        }
        $sum = 0;
        $weightSum = 0;
        for ($i = 0; $i < count($numbers); $i++) {
            $sum += $numbers[$i]*$weights[$i];
            $weightSum +=$weights[$i];
        }
        if ($weightSum === 0) return null;
        return $sum / $weightSum;
    }

    // mean absolute deviation
    // doing median deviation instead makes it less clear when the data just has some conflicted, high-spread nature, leading to artificially lower values, so we keep mean absolute deviation
    public static function mad(array $items, float $mean): ?float {
        if (count($items) === 0) {
            return null;
        }
        $absolute_deviation_sum = 0;
        foreach ($items as $item) {
            $absolute_deviation_sum += abs($item['data'] - $mean);
        }
        return $absolute_deviation_sum / count($items);
    }

    public static function weightedMad(array $numbers, array $weights, float $weightStrength, float $weightedMean): ?float {
        if (count($numbers) === 0) {
            return null;
        }
        if ($weightStrength < 0 || $weightStrength > 1) {
            throw new RuntimeException("Invalid weight strength");
        }
        $absolute_deviation_sum = 0;
        $weightSum = 0;
        for ($i = 0; $i < count($numbers); $i++) {
            $weight = $weightStrength*($weights[$i]) + 1-$weightStrength;
            $absolute_deviation_sum += abs($numbers[$i] - $weightedMean)*$weight;
            $weightSum += $weight;
        }
        if ($weightSum === 0) return null;
        return $absolute_deviation_sum / $weightSum;
    }


    private static function outlierPenalty(array $item, float $range, bool $strictOutliers, float $mean, float $mad, float $r1, float $w1, int $numberOfItems): ?float {
        if ($numberOfItems <= 2) {
            return 0;
        } 
        $d = abs($item['data'] - $mean);
        $adjustedMad = ($mad * $numberOfItems - $d) / ($numberOfItems-1); // mad without contribution
        $raw_outlier_risk = log(1 + $d / max($adjustedMad, $range / 20), 1.45) * pow($d / $range, 1.3);
        if ($strictOutliers === true && $raw_outlier_risk >= 2.8) {
            return null;
        }
        $risk = min($raw_outlier_risk/3,1) * (0.5+$r1/2); // increases slow at first, then proportional, more agressively now
        return $risk * (0.5+$w1/2);
        // w1 is between 0.2-0.6ish
        // risk function is essentially linear to $d for high enough points, thus penalty is roughly proportional
    }

    public static function individualOutlierRiskValue(float $value, float $mean, float $mad, float $range, float $w1 ): float {
        $d = abs($value - $mean);
        $raw_outlier_risk = log(1 + $d / max($mad, $range / 20), 1.45) * pow($d / $range, 0.8);
        $outlier_value =  (0.5 + $w1/2) * $raw_outlier_risk;
        $change_value = ( 1 / (0.3 + exp( -6*sqrt($outlier_value) + 9 ))  - 0.2 )/9;
        //error_log("[mad: $mad, range: $range, d: $d, w: $w1] ==> change_value: " . $change_value*100 . "%");
        return $change_value;
    }




    public static function majorityAndReliability(array $items, array $WRelFactors, string $rel_type): array {
        if (count($items) === 0) {
            error_log("invalid weightingAverage call...");
            return ["current_value" => null, "reliability" => 0]; 
        }

        $weightTotal = 0;
        $totalContributions = 0;
        $reliabilitySum = 0; // of squares
        $usedContributions = 0;

        $mean = self::mean(array_column($items, 'data'), false);
        $mad = self::mad($items, $mean);
        

        foreach ($items as $item) {
            $outlierPenalty = self::outlierPenalty($item, 1, false, $mean, $mad, $item['rel_1'], $WRelFactors[0], count($items))/2;
            if ($outlierPenalty > 0.23 || $outlierPenalty === null) {
                continue;
            }

            $unadjustedWeight = (1 - $item['rel_1'])*$WRelFactors[0] + ($item['rel_2'])*$WRelFactors[1] + ($item['rel_3'])*$WRelFactors[2];
            $weight =  $unadjustedWeight - $outlierPenalty;
            if ($weight <= 0) {
                continue;
            }

            $totalContributions += $item['data']*$weight;
            $reliabilitySum += ($unadjustedWeight / (array_sum($WRelFactors))) ** 2;
            
            $usedContributions++;
            $weightTotal += $weight;
        }

        if ($weightTotal > 0 && $usedContributions > 0) {
            $value = $totalContributions / $weightTotal;
            if (abs($value - 0.5) > 0.2) {
                $value = round($value);
                $reliability = self::computeReliability($rel_type, sqrt($reliabilitySum/ $usedContributions), $mad, 1, $usedContributions);
            } else {
                $value = 0.5; // inconclusive
                $reliability = 0;
            }
            
        } else {
            $value = null;
            $reliability = 0;
        }
        

        return ["current_value" => $value, "reliability" => $reliability];
    }
}