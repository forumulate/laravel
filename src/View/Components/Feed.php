<?php

namespace Forumulate\Laravel\View\Components;

/** <x-forumulate-feed /> */
class Feed extends Embed
{
    protected function kind(): string
    {
        return 'feed';
    }
}
