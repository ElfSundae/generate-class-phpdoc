<?php

require __DIR__.'/../vendor/autoload.php';

use Elfsundae\Laravel\FacadePhpdocGenerator;

function existingClasses($classes)
{
    return array_filter($classes, 'class_exists');
}

if (class_exists(\Illuminate\Log\LogManager::class)) {
    echo FacadePhpdocGenerator::make(\Illuminate\Log\LogManager::class);

    echo FacadePhpdocGenerator::make([
        \Illuminate\Log\LogManager::class,
        \Illuminate\Log\Logger::class,
    ]);
}

echo FacadePhpdocGenerator::make(\Illuminate\Translation\Translator::class);

echo FacadePhpdocGenerator::make(existingClasses([
    \Illuminate\Auth\AuthManager::class,
    \Illuminate\Contracts\Auth\Factory::class,
    \Illuminate\Contracts\Auth\Guard::class,
    \Illuminate\Contracts\Auth\StatefulGuard::class,
]));
