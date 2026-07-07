# React storefront

A small React (Vite) single-page app that renders the shop catalog from the
JSON API exposed by the Symfony application (`/api/products`,
`/api/products/{slug}`, `/api/categories`). It is an alternative, client-rendered
view of the same data as the Twig pages.

The catalogue supports category filtering and search, and each card opens a
client-side product page (`#/product/<slug>`) via a tiny hash router. Cart and
checkout intentionally stay in the server-rendered store, so the product page's
buy action links back there — the SPA itself is a read-only view of the API.

## Development

```bash
cd frontend
npm install
npm run dev
```

`npm run dev` starts the Vite dev server on http://localhost:5173 and proxies
`/api` and `/images` requests to the PHP app on http://localhost:8080, so make
sure the Docker stack is running (`docker compose up -d`).

## Production build

```bash
npm run build
```

This compiles the app into `../public/spa`, from where nginx serves it at
`http://localhost:8080/spa/`. The build output is not committed.
