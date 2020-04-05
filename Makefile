dev-from-scratch: composer

composer:
	-rm -rf ./vendor
	-a | composer install

pretty:
	./vendor/bin/pretty

pretty-fix:
	./vendor/bin/pretty fix

psalm:
	./vendor/bin/psalm --show-info=true

test:
	./bin/phpunit

infection:
	./vendor/bin/infection --threads=4

release:
	git add CHANGELOG.md && git commit -m "release($(VERSION))" && git tag $(VERSION) && git push && git push --tags

.PHONY: dev-from-scratch composer pretty pretty-fix psalm test infection release