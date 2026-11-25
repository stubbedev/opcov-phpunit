<?php

declare(strict_types=1);

namespace Composer {
    class Composer
    {
    }
}

namespace Composer\IO {
    interface IOInterface
    {
        /**
         * @param string|list<string> $messages
         *
         * @return void
         */
        public function write($messages, bool $newline = true, int $verbosity = 2);

        /**
         * @param string|list<string> $messages
         *
         * @return void
         */
        public function writeError($messages, bool $newline = true, int $verbosity = 2);
    }
}

namespace Composer\Plugin {
    use Composer\Composer;
    use Composer\IO\IOInterface;

    interface PluginInterface
    {
        /**
         * @return void
         */
        public function activate(Composer $composer, IOInterface $io);

        /**
         * @return void
         */
        public function deactivate(Composer $composer, IOInterface $io);

        /**
         * @return void
         */
        public function uninstall(Composer $composer, IOInterface $io);
    }
}

namespace Composer\EventDispatcher {
    interface EventSubscriberInterface
    {
        /**
         * @return array<string, string|array{0: string, 1?: int}|list<array{0: string, 1?: int}>>
         */
        public static function getSubscribedEvents();
    }
}

namespace Composer\Script {
    class ScriptEvents
    {
        public const string POST_INSTALL_CMD = 'post-install-cmd';
        public const string POST_UPDATE_CMD = 'post-update-cmd';
    }
}
