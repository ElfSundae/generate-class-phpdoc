<?php

require __DIR__.'/../vendor/autoload.php';

use Elfsundae\Laravel\GenerateFacadePhpdocs;

echo GenerateFacadePhpdocs::for(\Illuminate\Log\Logger::class)
    ->filter(function (ReflectionMethod $method) {
        return strpos($method->getName(), '__') !== 0;
    });

echo GenerateFacadePhpdocs::for([
    \Illuminate\Database\DatabaseManager::class,
    \Illuminate\Database\Connection::class
]);
