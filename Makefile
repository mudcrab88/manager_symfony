init: docker-down-clear manager-clear docker-pull docker-build docker-up manager-init

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-pull:
	docker-compose pull

docker-build:
	docker-compose build

manager-test:
	docker-compose run --rm manager-php-cli php bin/phpunit

manager-clear:
	docker run --rm -v ${PWD}/manager:/app --workdir=/app alpine rm -f .ready

manager-init: manager-composer-install manager-oauth-keys manager-wait-db manager-migrations manager-fixtures manager-ready

manager-composer-install:
	docker-compose run --rm manager-php-cli composer install

manager-assets-install:
	docker-compose run --rm manager-node yarn install
	docker-compose run --rm manager-node npm rebuild node-sass

manager-oauth-keys:
	docker-compose run --rm manager-php-cli mkdir -p var/oauth
	docker-compose run --rm manager-php-cli openssl genrsa -out var/oauth/private.key 2048
	docker-compose run --rm manager-php-cli openssl rsa -in var/oauth/private.key -pubout -out var/oauth/public.key
	docker-compose run --rm manager-php-cli chmod 644 var/oauth/private.key var/oauth/public.key

manager-wait-db:
	until docker-compose exec -T manager-postgres pg_isready --timeout=0 --dbname=app ; do sleep 1 ; done

manager-migrations:
	docker-compose run --rm manager-php-cli php bin/console doctrine:migrations:migrate --no-interaction

manager-fixtures:
	docker-compose run --rm manager-php-cli php bin/console doctrine:fixtures:load --no-interaction

manager-ready:
	docker run --rm -v ${PWD}/manager:/app --workdir=/app alpine touch .ready

manager-migrations-diff:
	docker-compose run --rm manager-php-cli php bin/console doctrine:migrations:diff

manager-migrate:
	docker-compose run --rm manager-php-cli php bin/console doctrine:migrations:migrate

dev-up:
	docker network create app
	docker run -d --name manager-php-fpm -v ${PWD}/manager:/app --network=app manager-php-fpm
	docker run -d --name manager-nginx -v ${PWD}/manager:/app -p 8080:80 --network=app manager-nginx

dev-down:
	docker stop manager-nginx
	docker stop manager-php-fpm
	docker rm manager-nginx
	docker rm manager-php-fpm
	docker network remove app


dev-build:
	docker build --file=manager/docker/development/nginx.docker --tag manager-nginx manager/docker/development
	docker build --file=manager/docker/development/php-fpm.docker --tag manager-php-fpm manager/docker/development
	docker build --file=manager/docker/development/php-cli.docker --tag manager-php-cli manager/docker/development

dev-cli:
	docker run --rm -v ${PWD}/manager:/app manager-php-cli php bin/app.php

prod-up:
	docker run -d --name manager-php-fpm manager-php-fpm
	docker run -d --name manager-nginx -p 8080:80 manager-nginx

prod-cli:
	docker run --rm manager-php-cli php bin/app.php

prod-build:
	docker build --file=manager/docker/development/nginx.docker --tag manager-nginx manager
	docker build --file=manager/docker/development/php-fpm.docker --tag manager-php-fpm manager
	docker build --file=manager/docker/development/php-cli.docker --tag manager-php-cli manager
