<?php
namespace docker {
        function adminer_object() {
                /**
                 * Prefills the “Server” field with the ADMINER_DEFAULT_SERVER environment variable.
                 */
                final class DefaultServerPlugin extends \Adminer\Plugin {
                        public function __construct(
                                private \Adminer\Adminer $adminer
                        ) { }

                        public function loginFormField(...$args) {
                                return (function (...$args) {
                                        $field = $this->loginFormField(...$args);

                                        return \str_replace(
                                                'name="auth[server]" value="" title="hostname[:port]"',
                                                \sprintf('name="auth[server]" value="%s" title="hostname[:port]"', ($_ENV['ADMINER_DEFAULT_SERVER'] ?: 'db')),
                                                $field,
                                        );
                                })->call($this->adminer, ...$args);
                        }
                }

                $plugins = [];
                foreach (glob('plugins-enabled/*.php') as $plugin) {
                        $plugins[] = require($plugin);
                }

                $adminer = new \Adminer\Plugins($plugins);

                (function () {
                        $last = &$this->hooks['loginFormField'][\array_key_last($this->hooks['loginFormField'])];
                        if ($last instanceof \Adminer\Adminer) {
                                $defaultServerPlugin = new DefaultServerPlugin($last);
                                $this->plugins[] = $defaultServerPlugin;
                                $last = $defaultServerPlugin;
                        }
                })->call($adminer);

                return $adminer;
        }
}

namespace {
        $dbPath = getenv('ADMINER_DB') ?: '/var/www/html/database.sqlite';

        // Simulate the Adminer login POST
        $_POST['auth'] = [
            'driver' => 'sqlite',
            // For SQLite, Adminer reads the DB path from auth[db]
            'db'     => $dbPath,
        ];

        // Some Adminer builds look at query args to set the active driver tab;
        // this doesn't hurt and helps certain skins.
        $_GET['sqlite'] = 1;
        $_GET['db']     = $dbPath;

        // Hand off to Adminer (this renders the app already "logged in")

        if (basename($_SERVER['DOCUMENT_URI'] ?? $_SERVER['REQUEST_URI']) === 'adminer.css' && is_readable('adminer.css')) {
                header('Content-Type: text/css');
                readfile('adminer.css');
                exit;
        }

        function adminer_object() {
                return \docker\adminer_object();
        }

        require('adminer.php');
}