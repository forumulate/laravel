<?php

namespace Forumulate\Laravel;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ForumulateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/forumulate.php', 'forumulate');

        $this->app->singleton(Forumulate::class, function ($app) {
            $config = $app['config']->get('forumulate', []);

            return new Forumulate(
                $config,
                $app->make(Http::class),
                $app['cache']->store($config['cache_store'] ?? null),
            );
        });
        $this->app->alias(Forumulate::class, 'forumulate');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'forumulate');

        // php artisan vendor:publish --tag=forumulate-config
        $this->publishes([
            __DIR__ . '/../config/forumulate.php' => config_path('forumulate.php'),
        ], 'forumulate-config');

        Blade::component('forumulate-comments', View\Components\Comments::class);
        Blade::component('forumulate-widget', View\Components\Widget::class);
        Blade::component('forumulate-changelog', View\Components\Changelog::class);
        Blade::component('forumulate-feed', View\Components\Feed::class);
        Blade::component('forumulate-search', View\Components\Search::class);
    }
}
