#!/bin/bash

# chmod +x phpstan.sh

docker compose exec php ./vendor/bin/phpstan analyze --memory-limit=1G