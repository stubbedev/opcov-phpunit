<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use RuntimeException;

final class Handshake
{
    public const string PACKAGE_VERSION = '0.3.0';

    public const string REPORT_SCHEMA = 'opcov-report/1';

    public static function verifyLoadedExtension(): Version
    {
        if (!extension_loaded('opcov')) {
            return self::check(null);
        }

        return self::check(\opcov\version());
    }

    public static function check(?string $extensionVersion): Version
    {
        if ($extensionVersion === null) {
            throw new RuntimeException(
                'the opcov extension is not loaded; install it (docs/install.md) and load it with extension=opcov',
            );
        }

        $package = Version::parse(self::PACKAGE_VERSION);
        $extension = Version::parse($extensionVersion);

        if (!$extension->isCompatibleWith($package)) {
            throw new RuntimeException(sprintf(
                'the opcov extension %s is not compatible with opcov/phpunit %s; install versions of the same %s.x line',
                $extension->toString(),
                $package->toString(),
                $package->compatibilityLine(),
            ));
        }

        return $extension;
    }
}
