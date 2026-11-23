<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Unit\Launcher;

use InvalidArgumentException;
use OpCov\PHPUnit\Launcher\SourcePaths;
use OpCov\PHPUnit\Tests\Unit\TemporaryDirectory;
use Override;
use PHPUnit\Framework\TestCase;

final class SourcePathsTest extends TestCase
{
    private TemporaryDirectory $directory;

    #[Override]
    protected function setUp(): void
    {
        $this->directory = TemporaryDirectory::create();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testSourcePathsComeFromTheSourceElement(): void
    {
        $this->directory->write('src/A.php', '<?php');
        $this->directory->write('src/Generated/B.php', '<?php');
        $this->directory->write('lib/one/C.php', '<?php');
        $this->directory->write('lib/two/D.php', '<?php');
        $this->directory->write('helpers.php', '<?php');
        $configuration = $this->directory->write('phpunit.xml', <<<'XML'
            <phpunit>
                <source>
                    <include>
                        <directory suffix=".php">src</directory>
                        <directory>lib/*</directory>
                        <file>helpers.php</file>
                    </include>
                    <exclude>
                        <directory>src/Generated</directory>
                    </exclude>
                </source>
            </phpunit>
            XML);

        $paths = SourcePaths::fromConfiguration($configuration);

        $root = $this->directory->path;
        self::assertSame(
            [$root . '/helpers.php', $root . '/lib/one', $root . '/lib/two', $root . '/src'],
            $paths->include,
        );
        self::assertSame([$root . '/src/Generated'], $paths->exclude);
    }

    public function testASuffixFilterIsRefused(): void
    {
        $this->directory->write('src/A.php', '<?php');
        $configuration = $this->directory->write(
            'phpunit.xml',
            '<phpunit><source><include><directory suffix=".inc">src</directory></include></source></phpunit>',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('filters files by prefix or suffix');

        SourcePaths::fromConfiguration($configuration);
    }

    public function testAMissingIncludeIsRefused(): void
    {
        $configuration = $this->directory->write(
            'phpunit.xml',
            '<phpunit><source><include><directory>missing</directory></include></source></phpunit>',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('<source><include><directory>missing</directory>');

        SourcePaths::fromConfiguration($configuration);
    }

    public function testAConfigurationWithoutSourceIsRefused(): void
    {
        $configuration = $this->directory->write('phpunit.xml', '<phpunit/>');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('has no <source><include> entry');

        SourcePaths::fromConfiguration($configuration);
    }
}
