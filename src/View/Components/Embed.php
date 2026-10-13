<?php

namespace Forumulate\Laravel\View\Components;

use Illuminate\View\Component;

/**
 * Base for the <x-forumulate-comments /> family. No props are declared: every attribute the
 * template passes (org, space, theme, article-url, ...) stays in the attribute
 * bag and Forumulate::embed() turns it into escaped data-* attributes.
 */
abstract class Embed extends Component
{
    abstract protected function kind(): string;

    public function render()
    {
        return view('forumulate::embed', ['kind' => $this->kind()]);
    }
}
