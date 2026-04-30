<?php

return [

    'default' => 'groq',

    'providers' => [

        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
            'models' => [
                'text' => [
                    'default' => 'llama-3.1-8b-instant',
                    'cheapest' => 'llama-3.1-8b-instant',
                    'smartest' => 'llama-3.3-70b-versatile',
                ],
            ],
        ],

    ],

];
