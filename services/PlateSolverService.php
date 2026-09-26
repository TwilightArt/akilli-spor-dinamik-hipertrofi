<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Realistic Plate Combination Solver
 * Matches barbell / dumbbell handles and available weight plates in symmetric pairs
 */
class PlateSolverService
{
    /**
     * Solve plate combination for a given target weight
     *
     * @param float $targetWeight Total target weight in kg
     * @param bool $isBarbell True for barbell, false for single dumbbell
     * @param array $availablePlates Inventory e.g. ["20kg" => 2, "10kg" => 4, "5kg" => 4, "2.5kg" => 4, "1.25kg" => 4]
     * @param float $barWeight Bar weight in kg (barbell default 20kg, dumbbell handle 2.5kg)
     * @return array ['feasible_weight' => float, 'recipe' => string, 'side_plates' => array, 'remainder_kg' => float]
     */
    public function solve(
        float $targetWeight,
        bool $isBarbell,
        array $availablePlates,
        float $barWeight = 20.0
    ): array {
        $barName = $isBarbell ? "Bar ({$barWeight} kg)" : "Dambıl Barı ({$barWeight} kg)";

        if ($targetWeight <= $barWeight) {
            return [
                'feasible_weight' => $barWeight,
                'recipe'          => "Yalnızca Boş {$barName}",
                'side_plates'     => [],
                'remainder_kg'    => 0.0
            ];
        }

        $neededLoad = $targetWeight - $barWeight;
        $sideNeeded = $neededLoad / 2.0;

        // Parse plates and convert to sorted float array (descending)
        // e.g. "5kg" => 4 translates to weight: 5.0, pairs: 2
        $parsedInventory = [];
        foreach ($availablePlates as $plateKey => $qty) {
            $numQty = (int)$qty;
            if ($numQty < 2) {
                continue; // Need at least a pair (one for each side)
            }
            // Extract numeric weight
            $weightVal = (float)str_ireplace('kg', '', (string)$plateKey);
            if ($weightVal > 0.0) {
                $parsedInventory[] = [
                    'weight'    => $weightVal,
                    'key'       => $plateKey,
                    'pairCount' => (int)floor($numQty / 2)
                ];
            }
        }

        // Sort descending by weight
        usort($parsedInventory, fn($a, $b) => $b['weight'] <=> $a['weight']);

        // Greedy match for sideNeeded
        $sidePlates = [];
        $currentSideWeight = 0.0;
        $tolerance = 0.001;

        foreach ($parsedInventory as $plate) {
            $weight = $plate['weight'];
            $pairsAvail = $plate['pairCount'];

            for ($p = 0; $p < $pairsAvail; $p++) {
                if (($currentSideWeight + $weight) <= ($sideNeeded + $tolerance)) {
                    $currentSideWeight += $weight;
                    $sidePlates[$plate['key']] = ($sidePlates[$plate['key']] ?? 0) + 1;
                }
            }
        }

        $feasibleTotal = round($barWeight + ($currentSideWeight * 2), 2);
        $remainder = round($targetWeight - $feasibleTotal, 2);

        // Build human readable recipe
        if (empty($sidePlates)) {
            $recipe = "Boş {$barName}";
        } else {
            $platePieces = [];
            foreach ($sidePlates as $plateKey => $cnt) {
                $platePieces[] = "{$cnt}x{$plateKey}";
            }
            $recipe = "{$barName} + Her iki yana: " . implode(' + ', $platePieces);
        }

        return [
            'feasible_weight' => $feasibleTotal,
            'recipe'          => $recipe,
            'side_plates'     => $sidePlates,
            'remainder_kg'    => $remainder
        ];
    }
}
