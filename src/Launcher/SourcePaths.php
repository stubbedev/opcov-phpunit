<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

final readonly class SourcePaths
{
    /**
     * @param non-empty-list<string> $include
     * @param list<string> $exclude
     */
    private function __construct(
        public array $include,
        public array $exclude,
    ) {}

    public static function fromConfiguration(string $file): self
    {
        $root = self::load($file);
        $sources = $root->tagName === 'phpunit' ? self::children($root, 'source') : [];
        $base = dirname($file);
        $include = self::paths($sources, 'include', $base, $file);

        if ($include === []) {
            throw new InvalidArgumentException(sprintf(
                'the PHPUnit configuration %s has no <source><include> entry, so there is nothing to measure',
                $file,
            ));
        }

        return new self($include, self::paths($sources, 'exclude', $base, $file));
    }

    private static function load(string $file): DOMElement
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->load($file, LIBXML_NONET);
            $errors = libxml_get_errors();
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previous);
        }

        $root = $document->documentElement;

        if (!$loaded || $root === null) {
            throw new InvalidArgumentException(sprintf(
                'cannot parse the PHPUnit configuration %s: %s',
                $file,
                $errors === [] ? 'no document element' : trim($errors[0]->message),
            ));
        }

        return $root;
    }

    /**
     * @return list<DOMElement>
     */
    private static function children(DOMElement $parent, string ...$names): array
    {
        $children = [];

        foreach ($parent->childNodes as $child) {
            if (!$child instanceof DOMElement || !in_array($child->tagName, $names, strict: true)) {
                continue;
            }

            $children[] = $child;
        }

        return $children;
    }

    /**
     * @param list<DOMElement> $sources
     *
     * @return list<string>
     */
    private static function paths(array $sources, string $section, string $base, string $file): array
    {
        $paths = [];

        foreach ($sources as $source) {
            foreach (self::children($source, $section) as $group) {
                foreach (self::children($group, EntryKind::Directory->value, EntryKind::File->value) as $element) {
                    foreach (SourceEntry::fromElement($element, $section, $file)->resolve($base) as $path) {
                        $paths[$path] = true;
                    }
                }
            }
        }

        ksort($paths, SORT_STRING);

        return array_keys($paths);
    }
}
