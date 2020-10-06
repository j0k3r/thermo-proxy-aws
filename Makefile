.PHONY: fix analyse test dynamo local deploy

build: fix analyse test

fix:
	php vendor/bin/php-cs-fixer fix

analyse:
	php vendor/bin/phpstan analyse

test:
	php vendor/bin/simple-phpunit -v

dynamo:
	docker run -p 8000:8000 amazon/dynamodb-local

local:
	php -S localhost:8888 -t src src/App.php

deploy:
	serverless deploy --aws-profile jeremy --verbose
