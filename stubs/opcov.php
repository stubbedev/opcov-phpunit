<?php

declare(strict_types=1);

namespace opcov;

final class CannotCollectException extends \RuntimeException
{
}

function version(): string
{
}

function ensure_can_collect(): void
{
}

function start(): void
{
}

/**
 * @return array<non-empty-string, array{
 *     lines: array<positive-int, true>,
 *     functions: array<string, array{
 *         outcomes: array<string, true>,
 *         loops: array<string, array{zero?: true, second?: true}>,
 *     }>,
 * }>
 */
function stop(?string $test = null): array
{
}

function reset(): void
{
}

/**
 * @param array{root?: string, absolute_paths?: bool} $options
 */
function write_report(string $path, array $options = []): void
{
}

/**
 * @return array<string, mixed>
 */
function get_status(): array
{
}

/**
 * @param list<string> $files
 *
 * @return array<non-empty-string, array{
 *     lines: array<positive-int, list<int>>,
 *     functions: array<string, array{
 *         span: null|list<int>,
 *         outcomes: list<array{
 *             id: string,
 *             site: int,
 *             kind: string|array<string, int>,
 *             line: int,
 *             span: null|list<int>,
 *             hits: list<int>,
 *         }>,
 *         loops: list<array{id: string, kind: string, line: int, zero: null|list<int>, second: list<int>}>,
 *     }>,
 * }>
 */
function executable(array $files): array
{
}
