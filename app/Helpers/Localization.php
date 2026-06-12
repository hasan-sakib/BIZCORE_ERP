<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/**
 * Localization
 *
 * Simple translation helper for the BizCore ERP UI.
 *
 * Features:
 *   - Loads language files from resources/lang/{locale}/{file}.php
 *   - Supports dot-notation key lookup: "app.dashboard.title"
 *   - Named placeholder replacement: ":name" tokens in translation strings
 *   - Falls back to the fallback locale when a key is missing
 *   - Falls back to the raw key string when neither locale has the key
 *   - Thread-local static state (per-request locale override)
 *
 * Usage:
 *   use App\Helpers\Localization;
 *
 *   Localization::setLocale('bn');
 *   echo Localization::trans('app.dashboard.title');
 *   echo Localization::trans('app.errors.not_found', ['resource' => 'Invoice']);
 *
 * Configuration:
 *   The lang directory path, supported locales, and fallback locale are read
 *   from the application config array passed to ::configure().
 *   If configure() is never called, sensible defaults are used:
 *     lang_path        → BASE_PATH . '/resources/lang'
 *     fallback_locale  → 'en'
 *     supported_locales→ ['en', 'bn']
 */
final class Localization
{
    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    /** @var string  Base path to lang/ directory. */
    private static string $langPath = '';

    /** @var string  Active locale for the current request. */
    private static string $locale = 'en';

    /** @var string  Locale used when a key is absent in the active locale. */
    private static string $fallbackLocale = 'en';

    /** @var list<string>  Locales the application knows about. */
    private static array $supportedLocales = ['en', 'bn'];

    /**
     * Nested translation cache: locale → file → key[] → translated string.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private static array $cache = [];

    /** Whether configure() has been called at least once. */
    private static bool $configured = false;

    /**
     * Bootstrap the localization helper with application-level config.
     *
     * Call once during application boot (e.g., in a ServiceProvider).
     *
     * @param array{
     *     lang_path?: string,
     *     locale?: string,
     *     fallback_locale?: string,
     *     supported_locales?: list<string>
     * } $config
     */
    public static function configure(array $config): void
    {
        if (isset($config['lang_path'])) {
            self::$langPath = rtrim($config['lang_path'], DIRECTORY_SEPARATOR);
        }

        if (isset($config['locale']) && $config['locale'] !== '') {
            self::$locale = $config['locale'];
        }

        if (isset($config['fallback_locale']) && $config['fallback_locale'] !== '') {
            self::$fallbackLocale = $config['fallback_locale'];
        }

        if (isset($config['supported_locales']) && $config['supported_locales'] !== []) {
            self::$supportedLocales = $config['supported_locales'];
        }

        self::$configured = true;
    }

    // -------------------------------------------------------------------------
    // Locale management
    // -------------------------------------------------------------------------

    /**
     * Override the active locale for the current request.
     *
     * @throws RuntimeException  When an unsupported locale is requested.
     */
    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, self::$supportedLocales, true)) {
            throw new RuntimeException(
                sprintf(
                    'Locale "%s" is not supported. Supported: %s.',
                    $locale,
                    implode(', ', self::$supportedLocales),
                ),
            );
        }

        self::$locale = $locale;
    }

    /**
     * Return the currently active locale.
     */
    public static function getLocale(): string
    {
        return self::$locale;
    }

    /**
     * Return all locales the application supports.
     *
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        return self::$supportedLocales;
    }

    /**
     * Return the fallback locale.
     */
    public static function getFallbackLocale(): string
    {
        return self::$fallbackLocale;
    }

    // -------------------------------------------------------------------------
    // Translation
    // -------------------------------------------------------------------------

    /**
     * Resolve a translation key and apply placeholder replacements.
     *
     * Key format: "file.section.sub-key" (dot notation).
     * The first segment names the file: "app.dashboard.title" loads
     * resources/lang/{locale}/app.php and drills into ['dashboard']['title'].
     *
     * Placeholder tokens use a colon prefix: :name, :count, :resource.
     * They are replaced case-sensitively.
     *
     * @param  string               $key           Dot-notation translation key.
     * @param  array<string, mixed> $replacements  Named placeholder values.
     * @param  string|null          $locale        Force a specific locale for this call only.
     *
     * @return string  Translated string, or $key if not found.
     */
    public static function trans(
        string $key,
        array $replacements = [],
        ?string $locale = null,
    ): string {
        $effectiveLocale = $locale ?? self::$locale;

        $translation = self::resolve($key, $effectiveLocale);

        // Fall back to the fallback locale when not found in the active one.
        if ($translation === null && $effectiveLocale !== self::$fallbackLocale) {
            $translation = self::resolve($key, self::$fallbackLocale);
        }

        // Fall back to the raw key when neither locale has it.
        if ($translation === null) {
            return $key;
        }

        return self::interpolate($translation, $replacements);
    }

    /**
     * Shorthand alias for trans() — allows calling via the helper function
     * convention used elsewhere in the application.
     *
     * @param  array<string, mixed> $replacements
     */
    public static function t(string $key, array $replacements = [], ?string $locale = null): string
    {
        return self::trans($key, $replacements, $locale);
    }

    /**
     * Return all translations for a given locale as a flat key → value map.
     *
     * Useful for passing translations to JavaScript in the view layer.
     *
     * @return array<string, string>
     */
    public static function all(?string $locale = null): array
    {
        $effectiveLocale = $locale ?? self::$locale;
        $result          = [];
        $langDir         = self::langDir($effectiveLocale);

        if (!is_dir($langDir)) {
            return $result;
        }

        $files = glob($langDir . DIRECTORY_SEPARATOR . '*.php');
        if (is_array($files)) {
            foreach ($files as $file) {
                $fileKey  = pathinfo($file, PATHINFO_FILENAME);
                $messages = self::loadFile($effectiveLocale, $fileKey);

                foreach (self::flatten($messages, $fileKey) as $k => $v) {
                    $result[$k] = $v;
                }
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Drill into the translation tree using dot-notation after the file key.
     *
     * Returns null when any segment is missing.
     */
    private static function resolve(string $key, string $locale): ?string
    {
        $parts   = explode('.', $key);
        $fileKey = array_shift($parts);

        $messages = self::loadFile($locale, $fileKey);

        $node = $messages;
        foreach ($parts as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return null;
            }
            $node = $node[$segment];
        }

        return is_string($node) ? $node : null;
    }

    /**
     * Load (and cache) a lang file for the given locale.
     *
     * @return array<string, mixed>
     */
    private static function loadFile(string $locale, string $fileKey): array
    {
        if (isset(self::$cache[$locale][$fileKey])) {
            return self::$cache[$locale][$fileKey];
        }

        $path = self::langDir($locale) . DIRECTORY_SEPARATOR . $fileKey . '.php';

        if (!is_file($path)) {
            self::$cache[$locale][$fileKey] = [];
            return [];
        }

        $messages = require $path;

        if (!is_array($messages)) {
            self::$cache[$locale][$fileKey] = [];
            return [];
        }

        self::$cache[$locale][$fileKey] = $messages;
        return $messages;
    }

    /**
     * Substitute :placeholder tokens in the translation string.
     *
     * @param array<string, mixed> $replacements
     */
    private static function interpolate(string $line, array $replacements): string
    {
        if ($replacements === []) {
            return $line;
        }

        $search  = [];
        $replace = [];

        foreach ($replacements as $placeholder => $value) {
            $search[]  = ':' . ltrim($placeholder, ':');
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $line);
    }

    /**
     * Return the absolute path to a locale's lang directory.
     */
    private static function langDir(string $locale): string
    {
        $base = self::$langPath !== ''
            ? self::$langPath
            : self::defaultLangPath();

        return $base . DIRECTORY_SEPARATOR . $locale;
    }

    /**
     * Compute the default lang path relative to the project root.
     * Assumes the file lives at <root>/app/Helpers/Localization.php.
     */
    private static function defaultLangPath(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
    }

    /**
     * Recursively flatten a nested translation array into dot-notation keys.
     *
     * @param  array<string, mixed> $array
     * @return array<string, string>
     */
    private static function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '.' . $key : (string) $key;

            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value, $fullKey));
            } elseif (is_string($value)) {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Purge the internal file cache.
     *
     * Call in tests or after dynamically adding/updating lang files.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
