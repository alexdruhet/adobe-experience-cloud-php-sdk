#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/.."

$DIR/vendor/bin/phpcs --config-set installed_paths $DIR/vendor/escapestudios/symfony2-coding-standard
$DIR/vendor/bin/phpcs --config-set default_standard Symfony2
$DIR/vendor/bin/phpcs --config-set report_format full
$DIR/vendor/bin/phpcs --config-set colors 1
$DIR/vendor/bin/phpcs --config-set severity 1
$DIR/vendor/bin/phpcs --config-set encoding utf-8
$DIR/vendor/bin/phpcs --config-set tab_width 4

cp $DIR/scripts/git-pre-commit $DIR/.git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

echo 'git pre-commit hook is now installed'

exit 0