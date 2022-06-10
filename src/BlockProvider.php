<?php

namespace Bigfork\SilverstripeTemplateYield;

use InvalidArgumentException;

class BlockProvider
{
    /**
     * A list of content rendered from each section tag
     */
    protected static array $sectionContents = [];

    /**
     * A list of fallback content from yield tags
     */
    protected static array $sectionFallbackContent = [];

    /**
     * Clear all content rendered from section tags
     */
    public static function reset(): void
    {
        static::$sectionContents = [];
        static::$sectionFallbackContent = [];
    }

    /**
     * Usage:
     *
     * <% yield 'MySectionName' %>
     * or
     * <% yield 'MySectionName', 'FallbackContent' %>
     */
    public static function yieldOpenTemplate(array $res): string
    {
        $identifier = $res['Arguments'][0]['text'] ?? '';
        if (!$identifier) {
            throw new InvalidArgumentException('An identifier must be passed to <% yield %>');
        }

        $identifier = trim($identifier, "'\"");

        $fallbackCode = '';
        $content = $res['Arguments'][1]['text'] ?? '';
        if ($content) {
            if ($res['Arguments'][1]['ArgumentMode'] !== 'string') {
                $content = str_replace('$$FINAL', 'XML_val', $res['Arguments'][1]['php']);
            }
            $fallbackCode = <<<PHP
\Bigfork\SilverstripeTemplateYield\BlockProvider::store('{$identifier}', {$content});
PHP;
        }

        return <<<PHP
\$val .= '<!-- SS_YIELD="{$identifier}" -->';
{$fallbackCode};
PHP;
    }

    /**
     * Usage:
     *
     * <% yield 'MySectionName' %>
     *     Fallback content
     * <% end_yield %>
     */
    public static function yieldClosedTemplate(array $res): string
    {
        $identifier = $res['Arguments'][0]['text'] ?? '';
        if (!$identifier) {
            throw new InvalidArgumentException('An identifier must be passed to <% yield %>');
        }

        $identifier = trim($identifier, "'\"");
        return <<<PHP
\$val .= '<!-- SS_YIELD="{$identifier}" -->';
\$old = \$val;

\$val = '';
{$res['Template']['php']};
\Bigfork\SilverstripeTemplateYield\BlockProvider::storeFallbackContent('{$identifier}', \$val);

\$val = \$old;
PHP;
    }

    /**
     * Usage:
     *
     * <% section 'MySectionName', 'My section content' %>
     * or
     * <% section 'MySectionName', $SomeLookupValue %>
     */
    public static function sectionOpenTemplate(array $res): string
    {
        $identifier = $res['Arguments'][0]['text'] ?? '';
        if (!$identifier) {
            throw new InvalidArgumentException('An identifier must be passed to <% section %>');
        }

        $content = $res['Arguments'][1]['text'] ?? '';
        if (!$content) {
            throw new InvalidArgumentException('A second argument must be passed to an inline <% section %> tag');
        }

        $identifier = trim($identifier, "'\"");
        if ($res['Arguments'][1]['ArgumentMode'] === 'string') {
            return <<<PHP
\Bigfork\SilverstripeTemplateYield\BlockProvider::store('{$identifier}', {$content});
PHP;
        }

        $content = str_replace('$$FINAL', 'XML_val', $res['Arguments'][1]['php']);
        return <<<PHP
\Bigfork\SilverstripeTemplateYield\BlockProvider::store('{$identifier}', {$content});
PHP;
    }

    /**
     * Usage:
     *
     * <% section 'MySectionName' %>
     *     My section content
     * <% end_section %>
     */
    public static function sectionClosedTemplate(array $res): string
    {
        $identifier = $res['Arguments'][0]['text'] ?? '';
        if (!$identifier) {
            throw new InvalidArgumentException('An identifier must be passed to <% section %>');
        }

        $clear = !empty($res['Arguments'][1]);

        $identifier = trim($identifier, "'\"");
        return <<<PHP
\$old = \$val;
\$val = '';
{$res['Template']['php']};
\$val = \Bigfork\SilverstripeTemplateYield\BlockProvider::yieldIntoString(\$val);
\Bigfork\SilverstripeTemplateYield\BlockProvider::store('{$identifier}', \$val, {$clear});
\$val = \$old;
PHP;

    }

    /**
     * Store content from a section tag
     */
    public static function store(string $identifier, string $content, bool $clear = false): void
    {
        if ($clear) {
            static::$sectionContents[$identifier] = [];
        }

        static::$sectionContents[$identifier][] = $content;
    }

    /**
     * Store fallback content from a yield tag
     */
    public static function storeFallbackContent(string $identifier, string $content): void
    {
        static::$sectionFallbackContent[$identifier] = $content;
    }

    /**
     * Processes placeholders and replaces them with yielded content
     */
    public static function yieldIntoString(string $output, bool $reset = false): string
    {
        while (preg_match('/<!-- SS_YIELD="(.*?)" -->/', $output, $matches)) {
            $identifier = $matches[1] ?? null;
            $sectionContents = static::$sectionContents[$identifier] ?? [];
            if (empty($sectionContents) && isset(static::$sectionFallbackContent[$identifier])) {
                $sectionContents[] = static::$sectionFallbackContent[$identifier];
            }

            $output = str_replace($matches[0], implode('', $sectionContents), $output);
        }

        if ($reset) {
            static::$sectionContents = [];
            static::$sectionFallbackContent = [];
        }

        return $output;
    }
}
