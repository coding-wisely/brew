<?php

namespace CodingWisely\LaravelBrew;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CodingWisely\LaravelBrew\Commands\LaravelBrewCommand;

class LaravelBrewServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-brew')
            ->hasConfigFile()
            ->hasCommand(LaravelBrewCommand::class);
    }
}
