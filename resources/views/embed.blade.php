{{-- Forumulate::embed() escapes every value with e(); its HtmlString is printed as-is. --}}
{{ app(\Forumulate\Laravel\Forumulate::class)->embed($kind, $attributes->getAttributes()) }}
