<?php
// File: config.php


// return [
//     'stripe_secret_key'      => 'sk_test_51QjrnHEtuCL1PUfK5KYJEfWivuvBYbTgrsyYuaDXkzqLLa7zFvKqf1O4xkL9m3FsJ5DQNsG5c93mKrHL4kNsAl7n00kaKosrU2',
//     'stripe_publishable_key' => 'pk_test_51QjrnHEtuCL1PUfKjFfgLCfCc6F4R2OK2m0644e6f7B1lehbiIHr6AiKcMNZrAKzsyvITFQFUwbTpYC4HMSP8r2s00rkQB84yI',
//     'stripe_connect_client_id' => 'ca_RdEJ9Z5Qkd6yoYj0YZtaWvt9fChO9H1Z' //valid for 7 days only. Should create another one after that.
// ];

return [
    'stripe' => [
        'admin' => [
            'secret_key'      => 'sk_test_51QiLLrB0RUZQ9PNuOHX4xFIqEu1NGZKJk1eMi28SFjoZKdnrIA0mV7Ja0HjjTR3cBV39zqG8cHdBZySOFYyAg94m00620TSJcI',
            'publishable_key' => 'pk_test_51QiLLrB0RUZQ9PNuV3SKIBZEmr0G98yv7lJLXbRYdL9dOMxLfNk4girAf4uRiTUjC19v6hrCzpLOwkBwPeTWMl8H00FPh4qpWZ',
            // 'account_id'      => 'acct_1QthX6B2UpV2W2vM'  // Add your admin account ID here
        ],
        'borrower' => [
            'secret_key'      => 'sk_test_51QiMaSG1kNj7lm6wv18B22hOe8a2s2OgkpTQfKvUEmP8NLRHhP8cytPIVTLtHrO242R394CY8lq9jv9VCZ4gIGHS00QONvfBdg',
            'publishable_key' => 'pk_test_51QiMaSG1kNj7lm6wBfcYURUsRpp4XrUEmi5SUObBluNOkjauxHOFAKSMSRwcLO3BJdESMW5ExXWw3i4VIBwBiLtI005OfDlMWY',
            'account_id'      => 'acct_1QthX6B2UpV2W2vM'  // Add your borrower account ID here
        ],
        'lender' => [
            'secret_key'      => 'sk_test_51QiMb9IkaMbmrtoO2yXpCiW9qXw2Jpl1haOQFvavKsU2UzbsWvo3PsIzd0Qb6FcdkRcbIk0eGSQxMXlwmD9FgmPd00LervLtw7',
            'publishable_key' => 'pk_test_51QiMb9IkaMbmrtoOLQyvDTsbt1rLCeJ3HfmNNG5UaKAURX19x0Jvlzto8dwCBrhAUlNhCfckJQqwHxbM2WrGmibc00yNQ7HDoM',
            'account_id'      => 'acct_1Qtxi0BIueWe9lXs'  // Add your lender account ID here
        ]
    ],
    
    // You can add other configuration settings here
    'app' => [
        'name' => 'Credit Management System',
        'url'  => 'http://localhost',
        'env'  => 'development'
    ],
    
    // Database configuration could go here if needed
    'database' => [
        'host'     => 'localhost',
        'username' => 'root',
        'password' => '',
        'dbname'   => 'credit_management'
    ]

    // 'database' => [
    //     'host'     => 'localhost',
    //     'username' => 'safefund_swaema',
    //     'password' => 'Dissertation27$',
    //     'dbname'   => 'safefund_credit_management'
    // ]
];