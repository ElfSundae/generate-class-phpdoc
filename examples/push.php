<?php

require __DIR__.'/../vendor/autoload.php';

use App\Notification\Pusher;
use Elfsundae\Laravel\FacadePhpdocGenerator;

echo FacadePhpdocGenerator::make(Pusher::class);

FacadePhpdocGenerator::make(Pusher::class)
    ->updateFacade(\App\Facades\Push::class);
