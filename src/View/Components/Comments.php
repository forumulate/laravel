<?php

namespace Forumulate\Laravel\View\Components;

/** <x-forumulate-comments /> */
class Comments extends Embed
{
    protected function kind(): string
    {
        return 'comments';
    }
}
