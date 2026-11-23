<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Launcher;

use DOMElement;
use InvalidArgumentException;

final readonly class SourceEntry
{
    private function __construct(
        private EntryKind $kind,
        private string $entry,
        private string $where,
    ) {}

    public static function fromElement(DOMElement $element, string $section, string $file): self
    {
        $kind = EntryKind::from($element->tagName);
        $where = sprintf(
            '<source><%s><%s>%s</%s> in %s',
            $section,
            $kind->value,
            $element->textContent,
            $kind->value,
            $file,
        );
        $suffix = $element->getAttribute('suffix');

        if ($element->getAttribute('prefix') !== '' || $suffix !== '' && $suffix !== '.php') {
            throw new InvalidArgumentException(sprintf(
                '%s filters files by prefix or suffix, which opcov.collect_paths cannot express; list the files or set the opcov INI settings yourself',
                $where,
            ));
        }

        $entry = trim($element->textContent);

        if ($entry === '') {
            throw new InvalidArgumentException(sprintf('%s is empty', $where));
        }

        return new self($kind, $entry, $where);
    }

    /**
     * @return list<string>
     */
    public function resolve(string $base): array
    {
        $pattern = str_starts_with($this->entry, '/') ? $this->entry : $base . '/' . $this->entry;
        $matches = strpbrk($this->entry, characters: '*?[') === false
            ? [$pattern]
            : glob($pattern, $this->kind->globFlags());

        if ($matches === false || $matches === []) {
            throw new InvalidArgumentException(sprintf('%s matches no path', $this->where));
        }

        return array_map($this->real(...), $matches);
    }

    private function real(string $match): string
    {
        $real = realpath($match);

        if ($real === false || !$this->kind->matches($real)) {
            throw new InvalidArgumentException(sprintf(
                '%s: %s is not an existing %s',
                $this->where,
                $match,
                $this->kind->value,
            ));
        }

        if (trim($real) !== $real || strpbrk($real, characters: "\n\r") !== false) {
            throw new InvalidArgumentException(sprintf(
                '%s: the path %s has surrounding whitespace or a line break, which an opcov listfile cannot hold',
                $this->where,
                json_encode($real),
            ));
        }

        return $real;
    }
}
