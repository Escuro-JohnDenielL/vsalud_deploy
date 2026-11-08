<?php

return [
    'defaults' => [
        'Villa I' => [
            ['label' => 'Non-air-conditioned', 'period' => 'AM', 'price' => 30000],
            ['label' => 'Non-air-conditioned', 'period' => 'PM', 'price' => 35000],
            ['label' => 'Air-conditioned', 'period' => 'AM', 'price' => 45000],
            ['label' => 'Air-conditioned', 'period' => 'PM', 'price' => 50000],
        ],
        'Villa II' => [
            ['label' => 'Non-air-conditioned', 'period' => 'AM', 'price' => 25000],
            ['label' => 'Non-air-conditioned', 'period' => 'PM', 'price' => 30000],
            ['label' => 'Air-conditioned', 'period' => 'AM', 'price' => 40000],
            ['label' => 'Air-conditioned', 'period' => 'PM', 'price' => 45000],
        ],
        'Elizabeth Hall' => [
            ['label' => '4-hour venue rental', 'period' => null, 'price' => 10000],
        ],
        'Sabrina Hall' => [
            ['label' => 'Base venue rental', 'period' => null, 'price' => 10000],
            ['label' => 'With sound system', 'period' => null, 'price' => 12000],
            ['label' => 'With styling', 'period' => null, 'price' => 20000],
        ],
    ],
];
