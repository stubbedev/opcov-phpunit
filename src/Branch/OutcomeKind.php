<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Branch;

use InvalidArgumentException;

// @mago-expect lint:too-many-enum-cases
enum OutcomeKind: string
{
    case IfTrue = 'IfTrue';
    case IfFalse = 'IfFalse';
    case ShortCircuit = 'ShortCircuit';
    case Evaluate = 'Evaluate';
    case CoalesceValue = 'CoalesceValue';
    case CoalesceFallback = 'CoalesceFallback';
    case TernaryValue = 'TernaryValue';
    case TernaryFallback = 'TernaryFallback';
    case NullsafeNull = 'NullsafeNull';
    case NullsafeValue = 'NullsafeValue';
    case MatchArm = 'MatchArm';
    case MatchNoArm = 'MatchNoArm';
    case SwitchCase = 'SwitchCase';
    case SwitchDefault = 'SwitchDefault';
    case LoopEnter = 'LoopEnter';
    case LoopSkip = 'LoopSkip';
    case LoopBack = 'LoopBack';
    case LoopExit = 'LoopExit';
    case Catch = 'Catch';
    case Finally = 'Finally';
    case FinallyException = 'FinallyException';
    case Engine = 'Engine';
    case Jump = 'Jump';

    public static function parse(mixed $kind, string $what): self
    {
        if (is_string($kind)) {
            $parsed = self::tryFrom($kind);

            if ($parsed === null || $parsed->numbered()) {
                throw new InvalidArgumentException(sprintf('%s is the unknown outcome kind "%s"', $what, $kind));
            }

            return $parsed;
        }

        if (!is_array($kind) || count($kind) !== 1) {
            throw new InvalidArgumentException(sprintf(
                '%s is neither an outcome kind name nor an object with one member',
                $what,
            ));
        }

        $name = (string) array_key_first($kind);
        $parsed = self::tryFrom($name);

        if ($parsed === null || !$parsed->numbered()) {
            throw new InvalidArgumentException(sprintf('%s is the unknown numbered outcome kind "%s"', $what, $name));
        }

        if (!is_int($kind[$name] ?? null) || $kind[$name] < 0) {
            throw new InvalidArgumentException(sprintf('%s.%s is not a non-negative integer', $what, $name));
        }

        return $parsed;
    }

    public function countsTowardVerdict(): bool
    {
        return !in_array($this, [self::Engine, self::LoopEnter, self::LoopSkip], strict: true);
    }

    private function numbered(): bool
    {
        return $this === self::MatchArm || $this === self::SwitchCase;
    }
}
