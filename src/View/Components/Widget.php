<?php

namespace Forumulate\Laravel\View\Components;

/** <x-forumulate-widget /> */
class Widget extends Embed
{
    protected function kind(): string
    {
        return 'widget';
    }
}
