#!/bin/bash

# chmod +x test.sh

docker compose exec php ./vendor/bin/phpunit
# docker compose exec -e XDEBUG_TRIGGER=1 php ./vendor/bin/phpunit