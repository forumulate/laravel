<?php

namespace Forumulate\Laravel\View\Components;

/** <x-forumulate-search /> */
class Search extends Embed
{
    protected function kind(): string
    {
        return 'search';
    }
}
