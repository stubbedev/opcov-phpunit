<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cli;

use OpCov\PHPUnit\AtomicFile;
use OpCov\PHPUnit\Handshake;
use RuntimeException;

final readonly class Binary
{
    private const string RELEASES = 'https://github.com/stubbedev/opcov/releases/download';

    public function __construct(
        private string $releaseUrl,
        private string $version,
        private Platform $platform,
        private string $cacheDirectory,
        private string $target,
    ) {}

    public static function forPackage(): self
    {
        $platform = Platform::host();

        return new self(
            self::RELEASES . '/v' . Handshake::PACKAGE_VERSION,
            Handshake::PACKAGE_VERSION,
            $platform,
            Cache::root() . '/opcov/' . Handshake::PACKAGE_VERSION,
            dirname(__DIR__, levels: 2) . '/bin/opcov-' . $platform->name,
        );
    }

    public function asset(): string
    {
        return sprintf('opcov-cli-v%s-%s', $this->version, $this->platform->name);
    }

    public function ensure(): string
    {
        if (is_file($this->target) && is_executable($this->target)) {
            return $this->target;
        }

        $cached = $this->cacheDirectory . '/' . $this->asset();

        if (!is_file($cached)) {
            $this->fetch($cached);
        }

        Cache::directory(dirname($this->target));
        AtomicFile::replace($this->target, static function (string $temporary) use ($cached): void {
            if (!copy($cached, $temporary)) {
                throw new RuntimeException(sprintf('cannot copy %s to %s', $cached, $temporary));
            }

            Cache::executable($temporary);
        });

        return $this->target;
    }

    private function fetch(string $cached): void
    {
        Cache::directory($this->cacheDirectory);
        $asset = $this->asset();
        $sums = Download::toString(
            $this->releaseUrl . '/SHA256SUMS',
            $this->cacheDirectory . '/.SHA256SUMS.' . bin2hex(random_bytes(8)),
        );
        $expected = Cache::checksum($sums, $asset);

        if ($expected === null) {
            throw new RuntimeException(sprintf('%s/SHA256SUMS does not list %s', $this->releaseUrl, $asset));
        }

        AtomicFile::replace($cached, function (string $temporary) use ($asset, $expected): void {
            Download::toFile($this->releaseUrl . '/' . $asset, $temporary);
            $actual = hash_file('sha256', $temporary);

            if ($actual === false || !hash_equals($expected, $actual)) {
                throw new RuntimeException(sprintf(
                    '%s/%s has sha256 %s, but SHA256SUMS lists %s',
                    $this->releaseUrl,
                    $asset,
                    $actual === false ? 'unknown' : $actual,
                    $expected,
                ));
            }

            Cache::executable($temporary);
        });
    }
}
