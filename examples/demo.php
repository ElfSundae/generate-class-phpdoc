<?php

require __DIR__.'/../vendor/autoload.php';

use Elfsundae\Laravel\GenerateFacadePhpdoc;

echo GenerateFacadePhpdoc::for(\Illuminate\Log\LogManager::class);

echo GenerateFacadePhpdoc::for([
    \Illuminate\Log\LogManager::class,
    \Illuminate\Log\Logger::class,
]);

echo GenerateFacadePhpdoc::for(\Illuminate\Translation\Translator::class);

echo GenerateFacadePhpdoc::for([
    \Illuminate\Auth\AuthManager::class,
    \Illuminate\Contracts\Auth\Factory::class,
    \Illuminate\Contracts\Auth\Guard::class,
    \Illuminate\Contracts\Auth\StatefulGuard::class,
]);
