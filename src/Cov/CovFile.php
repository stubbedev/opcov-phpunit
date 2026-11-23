<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cov;

use OpCov\PHPUnit\AtomicFile;
use SebastianBergmann\CodeCoverage\CodeCoverage;
// @mago-expect analysis:non-existent-use-import
use SebastianBergmann\CodeCoverage\Report\PHP;
use SebastianBergmann\CodeCoverage\Serialization\Serializer;

final class CovFile
{
    public static function write(CodeCoverage $coverage, string $target): void
    {
        AtomicFile::replace(
            $target,
            /**
             * @param non-empty-string $temporary
             */
            static function (string $temporary) use ($coverage): void {
                if (class_exists(Serializer::class)) {
                    new Serializer()->serialize($temporary, $coverage);

                    return;
                }

                // @mago-expect analysis:non-existent-class
                new PHP()->process($coverage, $temporary);
            },
        );
    }
}
