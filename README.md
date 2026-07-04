# symfony-eshop

A small e-shop built with Symfony 7.4: a product price list, a shopping
cart, an order/checkout flow with a pluggable payment gateway, and an
administration area for managing products and orders.

This is a demo project — the goal is a clean, well-structured backend that
is easy to read, test and extend.

## Status

Work in progress. See the commit history for the incremental build.

## Tech stack

- PHP 8.4 / Symfony 7.4
- PostgreSQL (Doctrine ORM)
- Twig for the admin area
- Docker / Docker Compose for local development

More services (Redis, RabbitMQ, Elasticsearch) and a React storefront are
added later in the build.

## Getting started

```bash
# build and start the containers
docker compose up -d --build

# install dependencies
docker compose exec php composer install

# create the database schema
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# load demo products
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
```

The storefront is then available at http://localhost:8080.
