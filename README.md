# forumulate/laravel

Forumulate for Laravel: Blade components for comments, the docs search / ask-AI
box and the "What's new" changelog bell, plus cached server-side reads of your
public blog posts, forum topics, changelog releases and docs pages so they can
be rendered into the HTML for SEO.

## Install

```sh
composer require forumulate/laravel
php artisan vendor:publish --tag=forumulate-config   # optional: config/forumulate.php
```

```dotenv
FORUMULATE_ORG=acme              # organization slug or id
FORUMULATE_SPACE=community       # default space; components accept space="..."
# FORUMULATE_BASE_URL=https://forumulate.com   # or your custom domain
# FORUMULATE_MODE=elements       # emit <forumulate-*> custom elements
# FORUMULATE_CACHE_TTL=300
# FORUMULATE_CACHE_STORE=redis
```

The service provider and the `Forumulate` facade are auto-discovered.

## Blade components

```blade
<x-forumulate-comments />
<x-forumulate-comments space="blog-comments" theme="dark" :topic="$post->forumulate_topic" />
<x-forumulate-widget mode="ask" placeholder="Ask the docs" />
<x-forumulate-changelog space="changelog" />
```

Every attribute becomes an escaped `data-*` attribute on the embed script (or
an attribute of the custom element in `elements` mode; `use-elements="true"`
switches one component).

## Server-rendered content

```blade
@foreach (Forumulate::items('blog', limit: 5) as $post)
  <a href="{{ $post['url'] }}">{{ $post['title'] }}</a>
@endforeach

{{ Forumulate::markdownHtml('blog', $slug) }}
```

Kinds: `blog`, `forum`, `changelog`, `docs`. Also `Forumulate::item()` (JSON)
and `Forumulate::markdown()` (plain text). Reads use Laravel's `Http` client and
`Cache`; only the public read API is used, no token. If Forumulate is
unreachable or a space is private, lists are empty and the page still renders.
`markdownHtml` escapes raw HTML and drops unsafe links (forum posts are written
by visitors).

## License

MIT

---

## About this repository

This repository is a public mirror, refreshed on each Forumulate release
(one commit per release). Pull requests are welcome; accepted changes are
applied upstream and arrive here with the next release.

- API reference: https://forumulate.com/reference
- Guides: https://forumulate.com/guides
- Community and support: https://forumulate.com/community
- Source: https://github.com/forumulate/laravel
