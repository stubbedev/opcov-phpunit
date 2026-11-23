<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;

final class RawLines
{
    /**
     * @param array<non-empty-string, array<positive-int, int>> $lines
     */
    public static function from(array $lines): RawCodeCoverageData
    {
        if (method_exists(RawCodeCoverageData::class, 'fromLineCoverage')) {
            return RawCodeCoverageData::fromLineCoverage($lines);
        }

        // @mago-expect analysis:non-existent-method
        // @mago-expect analysis:mixed-return-statement
        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($lines);
    }
}
