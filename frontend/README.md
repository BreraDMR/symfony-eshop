# React storefront

A small React (Vite) single-page app that renders the shop catalog from the
JSON API exposed by the Symfony application (`/api/products`, `/api/categories`).
It is an alternative, client-rendered view of the same data as the Twig pages.

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
