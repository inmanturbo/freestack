<?php

class Login {
    protected function cachePassword(?string $password): void
    {
        if ($password !== null && $password !== '') {
            $_SESSION['adminer_pw'] = $password;
        }
    }

    protected function cachedPassword(): ?string
    {
        return $_SESSION['adminer_pw'] ?? null;
    }

    protected function passwordFromToken(): ?string
    {
        if ($password = $this->cachedPassword()) {
            return $password;
        }

        $key = $_GET['key'] ?? null;

        if (! $key) {
            header('Location: http://freestack.test/database');
            exit;
        }

        // Works on Docker Desktop; add extra_hosts for Linux
        $base = 'http://host.docker.internal/api/adminer';
        $hostHeader = 'freestack.test'; // your Valet domain

        $url = $base.'/'.rawurlencode($key);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Host: {$hostHeader}"],
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 3,
        ]);

        $json = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($http !== 200 || ! $json) {
            header('Location: http://freestack.test/database');
            exit;
        }

        $password = json_decode($json, true);

        $this->cachePassword($password);

        return $password;
    }

    function login($login, $password) {
        return true; // always accept
    }

    function credentials() {
        $driver = match (true) {
            isset($_GET['sqlite']) => 'sqlite',
            isset($_GET['mariadb']) => 'mariadb',
            isset($_GET['pgsql']) => 'pgsql',
            default => 'mariadb',
        };

        if ($driver === 'sqlite') {
            return ['', ''];
        }

        $password = $this->passwordFromToken();

        return [
            $_GET['server'],
            $_GET['username'],
            $password,
        ];
    }
}
