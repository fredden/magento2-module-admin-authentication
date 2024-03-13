it: coding-standards dependency-analysis

coding-standards: vendor
	composer normalize
	vendor/bin/phpcbf || true
	vendor/bin/phpcs

dependency-analysis: vendor
	vendor/bin/composer-require-checker check --verbose

vendor: composer.json composer.lock
	composer validate --strict
	composer --no-interaction install --no-progress

composer.lock:
	test -f composer.lock || echo '{}' > composer.lock
