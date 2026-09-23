# Contributing

1. Fork and create a branch from `main`.
2. `composer install`
3. Make your change, keep it small and focused.
4. `composer format` (Pint), `composer analyse` (PHPStan level 8), `composer test`.
5. Open a pull request with a Conventional Commits title (`feat:`, `fix:`, ...).

Compatibility promise: PHP 8.1+ and Laravel 9 through 13. Do not use language
features newer than PHP 8.1 (readonly classes, typed constants, `#[\Override]`)
or Illuminate APIs that are missing from Laravel 9.
