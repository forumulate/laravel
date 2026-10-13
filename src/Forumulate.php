<?php

namespace Forumulate\Laravel;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Embed markup plus cached reads from Forumulate's PUBLIC read API
 * (/public/orgs/:org/spaces/:space/...). No token is ever sent. A failed read
 * (network, a private space's 404) yields an empty list / null, cached for 30s,
 * so a page that lists posts still renders when Forumulate is unreachable.
 */
class Forumulate
{
    /** kind => [list path, list key, item path, reader prefix] */
    public const KINDS = [
        'blog' => ['/blog', 'articles', '/blog/t/%s', 'b'],
        'forum' => ['/forum', 'topics', '/forum/t/%s', 'f'],
        'changelog' => ['/changelog', 'releases', '/changelog/t/%s', 'c'],
        'docs' => ['', null, '/pages/%s', 'p'],
    ];

    public const EMBEDS = [
        'comments' => ['comments.js', 'forumulate-comments'],
        'widget' => ['widget.js', 'forumulate-widget'],
        'changelog' => ['changelog.js', 'forumulate-changelog'],
        'feed' => [null, 'forumulate-feed'],
        'search' => [null, 'forumulate-search'],
    ];

    public function __construct(
        private array $config,
        private Http $http,
        private Cache $cache,
    ) {
    }

    public function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? 'https://forumulate.com'), '/');
    }

    public function apiUrl(): string
    {
        return rtrim((string) (($this->config['api_url'] ?? null) ?: $this->baseUrl() . '/api'), '/');
    }

    /**
     * Markup for one embed. Every value is attribute-escaped with e().
     *
     * @param array<string, mixed> $options attribute => value (kebab or snake case)
     */
    public function embed(string $kind, array $options = []): HtmlString
    {
        if (! isset(self::EMBEDS[$kind])) {
            throw new InvalidArgumentException("forumulate: unknown embed {$kind}");
        }
        [$script, $element] = self::EMBEDS[$kind];
        $org = $this->pull($options, 'org') ?: ($this->config['org'] ?? null);
        $space = $this->pull($options, 'space') ?: ($this->config['space'] ?? null);
        if (! $org || ! $space) {
            throw new InvalidArgumentException('forumulate: set FORUMULATE_ORG and FORUMULATE_SPACE (or pass org/space)');
        }
        $use = $this->pull($options, 'use-elements');
        $useElements = $use === null ? ($this->config['mode'] ?? 'script') === 'elements' : filter_var($use, FILTER_VALIDATE_BOOLEAN);
        $attrs = ['org' => $org, 'space' => $space, 'api' => $this->apiUrl()] + $options;

        if ($useElements || $script === null) {
            return new HtmlString(sprintf(
                '<%1$s%2$s></%1$s><script type="module" src="%3$s" async></script>',
                $element,
                $this->attrs($attrs, ''),
                e($this->config['elements_src'] ?? ''),
            ));
        }

        // The scripts mount into their parent node, so each gets its own box.
        return new HtmlString(sprintf(
            '<div class="forumulate-%s"><script src="%s"%s async></script></div>',
            $kind,
            e($this->apiUrl() . '/embed/' . $script),
            $this->attrs($attrs, 'data-'),
        ));
    }

    /** Blog posts, forum topics, changelog releases or docs pages, each with a `url`. */
    public function items(string $kind, int $limit = 10, ?string $space = null): array
    {
        [$path, $key] = $this->spec($kind);
        if ($kind === 'docs') {
            $data = $this->fetch($this->spaceUrl($space));
            $rows = [];
            foreach (($data['collections'] ?? []) as $collection) {
                foreach (($collection['pages'] ?? []) as $page) {
                    if (empty($page['hideFromNav'])) {
                        $rows[] = $page;
                    }
                }
            }
            $rows = array_slice($rows, 0, $limit);
        } else {
            $data = $this->fetch($this->spaceUrl($space) . $path . '?limit=' . $limit);
            $rows = is_array($data) ? ($data[$key] ?? []) : [];
        }

        return array_map(function (array $row) use ($kind, $space) {
            $row['url'] = ($row['externalUrl'] ?? null) ?: $this->readerUrl($kind, $space, $row['slug'] ?? $row['id']);

            return $row;
        }, $rows);
    }

    public function item(string $kind, string $slug, ?string $space = null): ?array
    {
        $data = $this->fetch($this->spaceUrl($space) . sprintf($this->spec($kind)[2], rawurlencode($slug)));
        if (! is_array($data)) {
            return null;
        }
        $piece = $data['article'] ?? $data['topic'] ?? $data['release'] ?? $data;
        $piece['url'] = $this->readerUrl($kind, $space, $piece['slug'] ?? $slug);

        return $piece;
    }

    /** The piece as Markdown (plain text; `{{ }}` escapes it). */
    public function markdown(string $kind, string $slug, ?string $space = null): string
    {
        $body = $this->fetch($this->spaceUrl($space) . sprintf($this->spec($kind)[2], rawurlencode($slug)) . '.md', false);

        return is_string($body) ? $body : '';
    }

    /**
     * The piece rendered to HTML. Raw HTML in the Markdown is escaped and unsafe
     * links are dropped: forum posts are written by visitors.
     */
    public function markdownHtml(string $kind, string $slug, ?string $space = null): HtmlString
    {
        return new HtmlString(Str::markdown($this->markdown($kind, $slug, $space), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]));
    }

    private function spec(string $kind): array
    {
        if (! isset(self::KINDS[$kind])) {
            throw new InvalidArgumentException("forumulate: unknown kind {$kind}");
        }

        return self::KINDS[$kind];
    }

    private function spaceUrl(?string $space): string
    {
        $org = $this->config['org'] ?? null;
        $space = $space ?: ($this->config['space'] ?? null);
        if (! $org || ! $space) {
            throw new InvalidArgumentException('forumulate: set FORUMULATE_ORG and FORUMULATE_SPACE (or pass a space)');
        }

        return $this->apiUrl() . '/public/orgs/' . rawurlencode($org) . '/spaces/' . rawurlencode($space);
    }

    private function readerUrl(string $kind, ?string $space, string $slug): string
    {
        return sprintf(
            '%s/%s/%s/%s/%s',
            $this->baseUrl(),
            $this->spec($kind)[3],
            rawurlencode((string) $this->config['org']),
            rawurlencode((string) ($space ?: $this->config['space'])),
            rawurlencode($slug),
        );
    }

    private function fetch(string $url, bool $json = true): mixed
    {
        $key = 'forumulate:' . sha1($url);
        $hit = $this->cache->get($key);
        if ($hit !== null) {
            return $hit;
        }
        $value = false;
        $ttl = 30;
        try {
            $res = $this->http->timeout((int) ($this->config['timeout'] ?? 5))
                ->withHeaders(['Accept' => $json ? 'application/json' : 'text/markdown', 'User-Agent' => 'forumulate-laravel'])
                ->get($url);
            if ($res->successful()) {
                $value = $json ? $res->json() : $res->body();
                $ttl = (int) ($this->config['cache_ttl'] ?? 300);
            }
        } catch (Throwable) {
            // Fall through: cache the miss briefly so an outage does not cost
            // every request a full timeout.
        }
        $this->cache->put($key, $value ?? false, $ttl);

        return $value ?? false;
    }

    private function pull(array &$options, string $name): mixed
    {
        foreach ([$name, Str::camel($name), str_replace('-', '_', $name)] as $key) {
            if (array_key_exists($key, $options)) {
                $value = $options[$key];
                unset($options[$key]);

                return $value;
            }
        }

        return null;
    }

    private function attrs(array $pairs, string $prefix): string
    {
        $out = '';
        foreach ($pairs as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $attr = strtolower(str_replace('_', '-', Str::kebab((string) $name)));
            // Names come from template authors; refuse anything that is not a
            // plain attribute name rather than escape it into something odd.
            if (! preg_match('/^[a-z][a-z0-9-]*$/', $attr)) {
                throw new InvalidArgumentException("forumulate: invalid option name {$name}");
            }
            $out .= ' ' . $prefix . $attr . '="' . e($value === true ? 'true' : (string) $value) . '"';
        }

        return $out;
    }
}
