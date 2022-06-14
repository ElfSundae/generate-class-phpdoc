<?php

require __DIR__.'/../vendor/autoload.php';

use Elfsundae\Laravel\FacadePhpdocGenerator;

echo FacadePhpdocGenerator::make('Illuminate\View\Factory');
