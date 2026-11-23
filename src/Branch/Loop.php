<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use OpCov\PHPUnit\Json;

final readonly class Loop
{
    /**
     * @param int<0, max> $number
     * @param int<0, max> $site
     * @param positive-int $line
     * @param null|list<int<0, max>> $zero
     * @param list<int<0, max>> $second
     */
    private function __construct(
        private int $number,
        private int $site,
        private int $line,
        private ?array $zero,
        private array $second,
    ) {}

    /**
     * @param array<array-key, mixed> $loop
     */
    public static function fromArray(array $loop, string $what, int $testCount): self
    {
        return new self(
            Id::Loop->number(Json::string($loop['id'] ?? null, $what . '.id'), $what . '.id'),
            Json::nonNegativeInt($loop['site'] ?? null, $what . '.site'),
            Json::positiveInt($loop['line'] ?? null, $what . '.line'),
            ($loop['zero'] ?? null) === null ? null : Json::hits($loop['zero'], $what . '.zero', $testCount),
            Json::hits($loop['second'] ?? null, $what . '.second', $testCount),
        );
    }

    /**
     * @param int<0, max> $first the branch number of the zero finding of loop l0
     *
     * @return array<int<0, max>, Branch>
     */
    public function branches(int $first): array
    {
        $branches = [];

        foreach (LoopFinding::cases() as $finding) {
            $hits = match ($finding) {
                LoopFinding::Zero => $this->zero,
                LoopFinding::Second => $this->second,
            };

            if ($hits !== null) {
                $branches[$finding->branch($first, $this->number)] = new Branch($this->site, $this->line, $hits);
            }
        }

        return $branches;
    }
}
