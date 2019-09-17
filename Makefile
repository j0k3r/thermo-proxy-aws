.PHONY: fix analyse test deploy

build: fix analyse test

fix:
	php vendor/bin/php-cs-fixer fix

analyse:
	php vendor/bin/phpstan analyse src tests --no-progress --level 7

test:
	php vendor/bin/simple-phpunit -v

deploy:
	serverless deploy --aws-profile jeremy --verbose
