<?php

declare(strict_types=1);

namespace OpCov\PHPUnit;

use InvalidArgumentException;
use RuntimeException;

final readonly class OutputPath
{
    // @mago-expect lint:no-literal-password
    public const string TEST_TOKEN = '{TEST_TOKEN}';

    private function __construct(
        private string $template,
    ) {}

    public static function parse(string $template, string $baseDirectory): self
    {
        if ($template === '') {
            throw new InvalidArgumentException('the "output" parameter is empty');
        }

        if (!str_starts_with($template, '/')) {
            $template = $baseDirectory . '/' . $template;
        }

        return new self($template);
    }

    // @mago-expect lint:sensitive-parameter
    public function resolve(?string $testToken): string
    {
        $placeholder = str_contains($this->template, self::TEST_TOKEN);

        if ($testToken === null) {
            if ($placeholder) {
                throw new RuntimeException(sprintf(
                    'the "output" parameter %s contains %s, but the TEST_TOKEN environment variable is not set (it is set by paratest for each worker)',
                    $this->template,
                    self::TEST_TOKEN,
                ));
            }

            return $this->template;
        }

        if ($testToken === '') {
            throw new RuntimeException('the TEST_TOKEN environment variable is set but empty');
        }

        if (!$placeholder) {
            throw new RuntimeException(sprintf(
                'TEST_TOKEN is set (a parallel worker), but the "output" parameter %s does not contain %s, so every worker would overwrite the same report',
                $this->template,
                self::TEST_TOKEN,
            ));
        }

        return str_replace(self::TEST_TOKEN, $testToken, $this->template);
    }
}
