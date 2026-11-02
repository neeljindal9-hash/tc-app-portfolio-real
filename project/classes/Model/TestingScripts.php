
<?php

require_once __DIR__."/LearnedDetail.php";

function makeTestScenario(
    array $userFactors,
    array $taskFactors,
    array $missing = [],
    bool $varyWeights = true
): array {
    $latent = count($userFactors);
    $users = count($userFactors[0]);
    $tasks = count($taskFactors[0]);

    $raw = [];
    $weights = [];

    // Generate exact matrix multiplication.
    for ($i = 0; $i < $users; $i++) {
        for ($j = 0; $j < $tasks; $j++) {
            $value = 0;

            for ($k = 0; $k < $latent; $k++) {
                $value += (float)$userFactors[$k][$i]
                    * (float)$taskFactors[$k][$j];
            }

            $raw[$i][$j] = $value;

            // Vary confidence between 0.5 and 2.0.
            $weights[$i][$j] = $varyWeights
                ? [0.2, 0.4, 0.6, 0.8][($i + $j) % 4]
                : 1.0;
        }
    }

    foreach ($missing as [$i, $j]) {
        $raw[$i][$j] = null;
        $weights[$i][$j] = null;
    }

    return [
        'raw' => $raw,
        'weights' => $weights,
        'expectedUsers' => $userFactors,
        'expectedTasks' => $taskFactors,
        'dimensions' => [
            'users' => $users,
            'tasks' => $tasks,
            'latent' => $latent
        ]
    ];
}


// SCENARIO 1: Random matrix, no missing observations.
// 4 users x 6 tasks.
// Values between -1 and 1.

function randomScenario(): array {
    mt_srand(12345);

    $raw = [];
    $weights = [];

    for ($i = 0; $i < 4; $i++) {
        for ($j = 0; $j < 6; $j++) {
            $raw[$i][$j] = mt_rand(-1000, 1000) / 1000;
            $weights[$i][$j] = 1.0;
        }
    }

    return [
        'raw' => $raw,
        'weights' => $weights,
        'dimensions' => [
            'users' => 4,
            'tasks' => 6,
            'latent' => 2
        ]
    ];
}


// SCENARIO 2: Exact latent-2 matrix.
// 3 users x 5 tasks.
// Every entry can be reconstructed exactly.

$scenario2 = makeTestScenario(
    [
        [0.7, -0.2, 0.9],
        [-0.1, 0.7, 0.4]
    ],
    [
        [0.4, -0.2, 0.8, 0.4, -0.7],
        [0.3, 0.9, -0.1, 0.8, 0.5]
    ],
    [],
    false
);


// SCENARIO 3: Exact latent-3 matrix with missing observations.
// 4 users x 7 tasks.

$scenario3 = makeTestScenario(
    [
        [0.9, -0.2, 0.7, 0.4],
        [-0.1, 0.8, 0.3, -0.9],
        [0.5, 0.4, -0.9, 0.2]
    ],
    [
        [0.8, -0.3, 0.9, 0.2, -0.7, 0.4, 0.6],
        [-0.2, 0.9, 0.4, -0.8, 0.3, 0.7, -0.1],
        [0.6, -0.1, 0.5, 0.4, 0.9, -0.2, 0.8]
    ],
    [
        [0, 2],
        [1, 5],
        [2, 0],
        [3, 3]
    ]
);


// SCENARIO 4: Exact latent-3 matrix with varied weights.
// 5 users x 9 tasks.

$scenario4 = makeTestScenario(
    [
        [0.9, -0.2, 0.7, 0.4, -0.8],
        [-0.1, 0.8, -0.3, 0.9, 0.5],
        [0.5, -0.4, 0.9, 0.2, -0.7]
    ],
    [
        [0.8, -0.3, 0.9, 0.2, -0.7, 0.4, 0.6, -0.5, 0.1],
        [-0.2, 0.9, 0.4, -0.8, 0.3, 0.7, -0.1, 0.6, 0.5],
        [0.6, -0.1, 0.5, 0.4, -0.9, 0.2, 0.8, -0.3, 0.7]
    ],
    [
        [0, 2], [0, 6],
        [1, 4],
        [2, 0], [2, 7],
        [3, 3],
        [4, 1], [4, 8]
    ]
);


// SCENARIO 5: Exact latent-3 matrix, larger dimensions.
// 6 users x 12 tasks.

$scenario5 = makeTestScenario(
    [
        [0.9, -0.2, 0.7, 0.4, -0.8, 0.3],
        [-0.1, 0.8, -0.3, 0.9, 0.5, -0.7],
        [0.5, -0.4, 0.9, 0.2, -0.7, 0.6]
    ],
    [
        [0.8, -0.3, 0.9, 0.2, -0.7, 0.4, 0.6, -0.5, 0.1, 0.9, -0.3, 0.7],
        [-0.2, 0.9, 0.4, -0.8, 0.3, 0.7, -0.1, 0.6, 0.5, -0.2, 0.8, 0.4],
        [0.6, -0.1, 0.5, 0.4, -0.9, 0.2, 0.8, -0.3, 0.7, 0.5, -0.1, 0.9]
    ],
    [
        [0, 2], [0, 8],
        [1, 4], [1, 10],
        [2, 0], [2, 7],
        [3, 3], [3, 11],
        [4, 1], [4, 9],
        [5, 5], [5, 8]
    ]
);


$scenarios = [
    1 => randomScenario(),
    2 => $scenario2,
    3 => $scenario3,
    4 => $scenario4,
    5 => $scenario5
];


foreach ($scenarios as $number => $scenario) {
    error_log("========== TEST $number ==========");

    try {
        $result = LearnedDetail::recreateUserTaskDetailData(
            $scenario['raw'],
            $scenario['weights']
        );

        $reconstructed = LearnedDetail::multiplyUserTaskMatrices(
            $result['users'],
            $result['tasks']
        );

    } catch(Throwable $t) {
        error_log("failed testingScripts: ". $t->getMessage());
    }
}