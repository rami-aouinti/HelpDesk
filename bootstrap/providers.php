<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use NunoMaduro\PhpInsights\Application\Adapters\Laravel\InsightsServiceProvider;

$providers = [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
];

if (class_exists(InsightsServiceProvider::class)) {
    $providers[] = InsightsServiceProvider::class;
}

return $providers;
