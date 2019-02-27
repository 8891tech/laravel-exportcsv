<?php
namespace T8891\ExportCsv;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('api', ExportCsvIfClientAccept::class);
        $router->pushMiddlewareToGroup('web', ExportCsvIfClientAccept::class);
    }
}
