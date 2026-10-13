<?php

namespace Forumulate\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\HtmlString embed(string $kind, array $options = [])
 * @method static array items(string $kind, int $limit = 10, ?string $space = null)
 * @method static array|null item(string $kind, string $slug, ?string $space = null)
 * @method static string markdown(string $kind, string $slug, ?string $space = null)
 * @method static \Illuminate\Support\HtmlString markdownHtml(string $kind, string $slug, ?string $space = null)
 *
 * @see \Forumulate\Laravel\Forumulate
 */
class Forumulate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Forumulate\Laravel\Forumulate::class;
    }
}
