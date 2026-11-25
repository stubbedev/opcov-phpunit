<?php

declare(strict_types=1);

namespace OpCov\PHPUnit\Cli;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Throwable;

final class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    private ?IOInterface $io = null;

    #[\Override]
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
    }

    #[\Override]
    public function deactivate(Composer $composer, IOInterface $io): void {}

    #[\Override]
    public function uninstall(Composer $composer, IOInterface $io): void {}

    /**
     * @return array<string, string>
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'install',
            ScriptEvents::POST_UPDATE_CMD => 'install',
        ];
    }

    public function install(): void
    {
        try {
            $binary = Binary::forPackage()->ensure();
        } catch (Throwable $cause) {
            $this->io?->writeError(sprintf(
                '<warning>opcov: cannot install the opcov CLI (%s); vendor/bin/opcov downloads it on first use</warning>',
                $cause->getMessage(),
            ));

            return;
        }

        $this->io?->write(sprintf('<info>opcov: the opcov CLI is %s</info>', $binary));
    }
}
