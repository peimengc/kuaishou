<?php


namespace Peimengc\Kuaishou;


class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(Kuaishou::class);

        $this->app->alias(Kuaishou::class, 'kuaishou');
    }

    public function provides()
    {
        return [Kuaishou::class, 'kuaishou'];
    }
}