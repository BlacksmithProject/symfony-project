dev-from-scratch: composer database

composer:
	-rm -rf ./vendor
	-a | composer install

database:
	bin/console d:d:d --force
	bin/console d:d:c
	bin/console d:m:migrate --no-interaction

diff:
	bin/console d:m:diff

start:
	bin/console server:start

stop:
	bin/console server:stop

pretty:
	./vendor/bin/pretty

pretty-fix:
	./vendor/bin/pretty fix

stan:
	./vendor/bin/phpstan analyse -l 7 src

test:
	./vendor/bin/phpunit

infection:
	./vendor/bin/infection --threads=4

test-CI:
	./vendor/bin/phpunit --coverage-clover=coverage.clover

CI: stan test-CI

release:
	git add CHANGELOG.md && git commit -m "release(v$(VERSION))" && git tag v$(VERSION) && git push && git push --tags

.PHONY: dev-from-scratch composer pretty pretty-fix stan test test-CI CI release