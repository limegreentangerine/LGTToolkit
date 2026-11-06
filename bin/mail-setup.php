<?php


$env = (isset($_ENV['CONCRETE5_ENV'])) ? $_ENV['CONCRETE5_ENV'] : ((isset($_SERVER['CONCRETE5_ENV'])) ? $_SERVER['CONCRETE5_ENV'] : false);
$applicationConfigDir = __DIR__ . '/../public/application/config';
$configFile = ($env) ? $applicationConfigDir . DIRECTORY_SEPARATOR . $env . '.concrete.php' : $applicationConfigDir . '/concrete.php';

if (!is_dir($applicationConfigDir)) {
    echo "Error: {$applicationConfigDir} does not exist\n";
    exit(1);
}

if (!file_exists($configFile)) {
    echo "Error: {$configFile} does not exist\n";
    exit(1);
}

$configArr = include $configFile;

$configArr['mail'] = [
    'method' => 'smtp',
    'methods' => [
        'smtp' => [
            'server'                    => 'mail.authsmtp.com',
            'port'                      => '25',
            'username'                  => 'ac64715',
            'password'                  => 'aynjtuv6dvbzrt',
            'encryption'                => '',
            'messages_per_connection'   => null,
        ],
    ],
];

if (file_put_contents($configFile, '<?php' . PHP_EOL . 'return ' . var_export($configArr, true) . ';' . PHP_EOL) === false) {
    echo "Error: unable to write to {$configFile}\n";
    exit(1);
}

echo "Mail configuration written to {$configFile}\n";
