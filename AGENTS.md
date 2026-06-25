# AGENTS.md

DarkAdmin is a single WordPress plugin (PHP) that adds a dark mode to the WP admin dashboard. There
is no JS/TS build step; assets are plain CSS/JS under `assets/`. The plugin bootstraps from
`darkadmin.php` and loads files in `includes/`.

## Cursor Cloud specific instructions

The VM snapshot already has all system tooling installed: PHP 8.3 (with `mysqli`, `pdo_mysql`,
`mbstring`, `xml`, `curl`, `zip`, `gd`), Composer, MySQL 8, WP-CLI, and a global PHPCS install with
the `WordPress`, `WordPress-Extra`, and `PHPCompatibility` standards. The startup update script runs
`composer install`, which refreshes the only repo-managed dependencies (the PHPUnit dev stack in
`vendor/`).

### Start MySQL (required before tests or the dev site)

MySQL is a service and is NOT auto-started. Start it each session with: `sudo service mysql start`
The dev/test DB user is `root` / `root` on host `127.0.0.1`. Databases: `wordpress_test` (PHPUnit)
and `wordpress_dev` (dev site).

### Tests (PHPUnit)

Tests need the WordPress test library, installed at `/tmp/wordpress-tests-lib` (the default location
`tests/bootstrap.php` looks for; no `WP_TESTS_DIR` needed). If that directory is missing, recreate
it with: `echo Y | bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest`
(`bin/install-wp-tests.sh` is the upstream wp-cli scaffold script; it is not committed, re-download
from `https://raw.githubusercontent.com/wp-cli/scaffold-command/main/templates/install-wp-tests.sh`
if absent.) Then run: `vendor/bin/phpunit --configuration phpunit.xml` (or `composer test`).

### Lint / code style

PHPCS and the WordPress + PHPCompatibility standards are installed globally, not in the repo's
`composer.json`. Run via the global binary:

- Code style:
  `~/.config/composer/vendor/bin/phpcs --standard=WordPress --extensions=php includes/ darkadmin.php uninstall.php`
- PHP compatibility:
  `~/.config/composer/vendor/bin/phpcs --standard=PHPCompatibility --runtime-set testVersion 8.0- --extensions=php includes/ darkadmin.php uninstall.php`

### Run the plugin (dev site)

A full WordPress install lives at `~/wp-dev`, with this repo symlinked into
`~/wp-dev/wp-content/plugins/darkadmin-dark-mode-for-adminpanel` (so editing files here is live).
Because of the symlink, run WP-CLI from `~/wp-dev` with `--allow-root`. Start the server with:
`cd ~/wp-dev && wp server --host=0.0.0.0 --port=8088 --allow-root` Admin login: `admin` / `password`
at `http://localhost:8088/wp-admin/`. Dark mode is already enabled (`darkadmin_dark_mode_enabled=1`,
`darkadmin_preset=modern`). Note: dark styles are intentionally skipped on the block/site editor
screens (`post.php`, `post-new.php`, `site-editor.php`).
