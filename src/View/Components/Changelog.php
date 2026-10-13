<?php

namespace Forumulate\Laravel\View\Components;

/** <x-forumulate-changelog /> */
class Changelog extends Embed
{
    protected function kind(): string
    {
        return 'changelog';
    }
}
