# Coding standards

This project uses PHP_CodeSniffer with WordPress Coding Standards (WPCS) and PHPCompatibility.

- Ruleset: see `.phpcs.xml.dist` at the project root.
- Install dev tools: run `composer install` (Composer will install PHPCS and register standards).
- Verify WPCS configuration: `composer run wpcs:doctor`
  - Expected: `Installed standards` includes `WordPress`, `WordPress-Core`, `WordPress-Extra`, `WordPress-Docs`.
  - Expected: `installed_paths` shows `vendor/wp-coding-standards/wpcs` and `vendor/phpcompatibility/php-compatibility`.
- Lint: `composer run lint:php`
- Auto-fix: `composer run lint:php:fix`

## IDE integration (PhpStorm)

- PhpStorm will auto-detect `.phpcs.xml.dist`. To run inspections, enable "PHP | Quality tools | PHP_CodeSniffer" and point the PHPCS binary to `vendor\bin\phpcs` (Windows) or `vendor/bin/phpcs` (macOS/Linux). Set the configuration to "Local" and the standard will be read from the ruleset.
- You can also set a File Watcher to run `phpcbf` on save using `vendor\bin\phpcbf`.

## Enabling WPCS inspections in PhpStorm

If you are not seeing WordPress Coding Standards (WPCS) inspections in PhpStorm, follow these steps. This project already includes PHPCS/WPCS config files at the root: `.phpcs.xml.dist`, `.phpcs.xml`, and `phpcs.xml`.

1. Ensure PhpStorm uses the project PHPCS binary
   - Open: Settings > PHP > Quality Tools > PHP_CodeSniffer
   - Configuration: Local
   - PHP_CodeSniffer path (Windows): `<project root>\vendor\bin\phpcs.bat`
   - Click Validate. You should see version and Installed standards including: WordPress, WordPress-Core, WordPress-Docs, WordPress-Extra, PHPCompatibility, PHPCompatibilityWP, etc.

2. Enable inspections and choose the standard/ruleset

   Depending on PhpStorm version, you may see slightly different UI labels.
   - Go to: Settings > Editor > Inspections > PHP > Quality tools > PHP_CodeSniffer validation
   - Enable the checkbox to turn on PHPCS inspections in the editor.
   - Choose ONE of the following:
     - Ruleset file: Select the project file `.phpcs.xml` (root). PhpStorm should auto-detect this as the default ruleset.
     - OR Coding standard: Select `WordPress` (this is a reliable fallback as vendor PHPCS definitely has WordPress installed).

### Notes

- If using a global phpcs, this project's rulesets also include a fallback `installed_paths` config to help global PHPCS locate WPCS and PHPCompatibility under `vendor`.
- CLI checks (run from project root):
  - `vendor\bin\phpcs -i` (should list WordPress and PHPCompatibility standards)
  - `vendor\bin\phpcs -sp --standard=WordPress admin\events\events-page.php` (should report WPCS findings)
  - `vendor\bin\phpcbf` to auto-fix fixable issues.

If inspections still don't appear, ensure the Editor > Inspections page has PHP_CodeSniffer validation enabled, and try switching between selecting the `.phpcs.xml` ruleset file and selecting the `WordPress` coding standard directly. Also confirm no file-level suppression is active in the current file.
