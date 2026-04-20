.PHONY: help install update test format format-check clean

help:
	@echo "Haykal monorepo — local dev commands"
	@echo ""
	@echo "  make install       Install composer dependencies."
	@echo "  make update        Update composer dependencies."
	@echo "  make test          Run the full test suite."
	@echo "  make format        Format code with Pint."
	@echo "  make format-check  Check formatting without modifying files."
	@echo "  make clean         Remove vendor/ and composer.lock."

install:
	composer install

update:
	composer update

test:
	vendor/bin/phpunit

format:
	vendor/bin/pint

format-check:
	vendor/bin/pint --test

clean:
	rm -rf vendor composer.lock .phpunit.cache
