# List available recipes
default:
    @just --list

# Install dependencies
install:
    composer install

# Fix code style
lint:
    vendor/bin/pint

# Check code style without fixing
lint-check:
    vendor/bin/pint --test

# Run tests
test:
    vendor/bin/phpunit

# Run all checks
check: lint-check test
