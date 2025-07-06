# Beans & Leaves — Symfony e-shop

[![CI](https://github.com/BreraDMR/symfony-eshop/actions/workflows/ci.yml/badge.svg)](https://github.com/BreraDMR/symfony-eshop/actions/workflows/ci.yml)

A small but complete coffee & tea e-shop built with **Symfony 7.4 / PHP 8.4**.
It has a product catalogue with a price list, a session cart, an order and
checkout flow with a pluggable payment gateway, and an admin area. On top of
that it demonstrates Redis caching, asynchronous email via RabbitMQ, an
Elasticsearch-backed product search, and an alternative React storefront that
talks to a JSON API.

The goal is a clean, readable backend with sensible boundaries, that is easy to
test and runs entirely from Docker.

## Features

- **Catalogue** — products grouped by category, product pages, images.
- **Cart** — session-based, CSRF-protected add/update/remove.
- **Checkout & orders** — orders snapshot product names and prices; a
  `PaymentGatewayInterface` abstracts the payment provider, with an offline
  `FakePaymentGateway` (default) and a `StripePaymentGateway`.
- **Admin** — authenticated area with product CRUD (incl. image upload) and
  order management.
- **Redis cache** — storefront reads go through a tag-aware cache pool and are
  invalidated as a group when a product changes.
- **Asynchronous email** — paying for an order dispatches a message to RabbitMQ;
  a worker sends the confirmation email (caught by Mailpit in development).
- **Search** — full-text product search on Elasticsearch, kept in sync by a
  Doctrine listener, with a database fallback when the cluster is unavailable.
- **JSON API + React SPA** — read-only `/api` endpoints power a Vite + React
  storefront served at `/spa/`.

## Tech stack

| Area | Choice |
| --- | --- |
| Language / framework | PHP 8.4, Symfony 7.4 (LTS) |
| Persistence | PostgreSQL 16, Doctrine ORM |
| Cache | Redis 7 |
| Messaging | RabbitMQ 3.13 + Symfony Messenger |
| Mail | Symfony Mailer (Mailpit in dev) |
| Search | Elasticsearch 8.15 |
| Server rendering | Twig |
| Frontend SPA | React 18 + Vite |
| Local environment | Docker Compose (php-fpm, nginx, postgres, redis, rabbitmq, elasticsearch, worker, mailpit) |
| Tests | PHPUnit |

## Getting started

```bash
# build and start the stack
docker compose up -d --build

# PHP dependencies
docker compose exec php composer install

# database schema and demo data
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php bin/console doctrine:fixtures:load --no-interaction

# build the Elasticsearch index
docker compose exec php bin/console app:search:reindex
```

- Storefront (Twig): http://localhost:8080
- Admin: http://localhost:8080/admin — `admin@example.com` / `admin`
- Mailpit (sent emails): http://localhost:8025
- RabbitMQ management: http://localhost:15672 — `eshop` / `eshop`

### React storefront

```bash
cd frontend
npm install
npm run build      # outputs to public/spa, served at http://localhost:8080/spa/
# or: npm run dev  # Vite dev server on :5173, proxies /api to :8080
```

See [`frontend/README.md`](frontend/README.md) for details.

## Running the tests

```bash
docker compose exec php bin/phpunit
```

Tests are isolated from the infrastructure: Messenger uses an in-memory
transport, the mailer uses the null transport, live search indexing is disabled
and the cache uses an array adapter — so the suite needs neither RabbitMQ, mail
nor Elasticsearch running.

## Design notes

- **Payment provider behind an interface.** The application depends on
  `PaymentGatewayInterface`; the concrete gateway is chosen at runtime by a
  factory from the `PAYMENT_GATEWAY` env var, so adding a provider does not
  touch the checkout code.
- **Read-through cache, not entities everywhere.** `ProductCatalog` wraps the
  repository with a tag-aware Redis pool; catalogue queries eager-load their
  category so cached results stay self-contained.
- **Work off the request path.** The order confirmation email is a Messenger
  message handled by a worker, so a slow mail server never delays checkout.
- **Search that degrades gracefully.** Elasticsearch only ranks matches; the
  entities are loaded from the database in that order. If the cluster is down,
  search falls back to a database query, and indexing failures are logged
  instead of breaking catalogue writes. The final ES client is hidden behind a
  `ProductSearchGateway` interface to keep the search service unit-testable.
- **One API, two frontends.** The Twig pages and the React SPA render the same
  data; the API reuses the same catalogue, search and price-formatting services.

## Payment gateways

`PAYMENT_GATEWAY=fake` (default) keeps checkout fully offline. Set
`PAYMENT_GATEWAY=stripe` and `STRIPE_SECRET_KEY=...` to use Stripe Checkout.
