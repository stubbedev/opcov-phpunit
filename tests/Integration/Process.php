<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Tests\Integration;

use RuntimeException;

final readonly class Process
{
    private function __construct(
        public int $exitCode,
        public string $output,
        public string $errors,
    ) {}

    /**
     * @param list<string> $command
     * @param non-empty-string $workingDirectory
     */
    public static function run(array $command, string $workingDirectory): self
    {
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $workingDirectory);

        if ($process === false) {
            throw new RuntimeException(sprintf('cannot start %s', implode(' ', $command)));
        }

        $output = stream_get_contents($pipes[1] ?? throw new RuntimeException('no stdout pipe'));
        $errors = stream_get_contents($pipes[2] ?? throw new RuntimeException('no stderr pipe'));
        fclose($pipes[1]);
        fclose($pipes[2]);

        return new self(proc_close($process), (string) $output, (string) $errors);
    }

    public function describe(): string
    {
        return sprintf("exit code %d\nstdout:\n%s\nstderr:\n%s", $this->exitCode, $this->output, $this->errors);
    }
}
