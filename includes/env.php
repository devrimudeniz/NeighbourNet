<?php
/**
 * Lightweight environment loader for local development and GitHub-ready setup.
 */

if (!function_exists('env_load')) {
    function env_load($path = null) {
        static $loadedPath = null;
        static $values = [];

        $path = $path ?: dirname(__DIR__) . '/.env';
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if ($loadedPath === $normalizedPath) {
            return $values;
        }

        $values = [];
        $loadedPath = $normalizedPath;

        if (!is_file($normalizedPath)) {
            return $values;
        }

        $lines = file($normalizedPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $values;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($value !== '' && (
                ($value[0] === '"' && substr($value, -1) === '"') ||
                ($value[0] === "'" && substr($value, -1) === "'")
            )) {
                $value = substr($value, 1, -1);
            }

            $values[$name] = $value;

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        return $values;
    }
}

if (!function_exists('env_value')) {
    function env_value($key, $default = null) {
        env_load();

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return $default;
    }
}

if (!function_exists('env_bool')) {
    function env_bool($key, $default = false) {
        $value = env_value($key, null);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('app_url')) {
    function app_url() {
        $fallbackHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $fallbackScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $fallback = $fallbackScheme . '://' . $fallbackHost;

        return rtrim((string) env_value('APP_URL', $fallback), '/');
    }
}

env_load();
