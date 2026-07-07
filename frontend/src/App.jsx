import { useEffect, useState } from 'react'
import { fetchCategories, fetchProducts } from './api.js'
import CategoryFilter from './components/CategoryFilter.jsx'
import ProductCard from './components/ProductCard.jsx'
import ProductDetail from './components/ProductDetail.jsx'

// Tiny hash-based router: '#/product/<slug>' opens a product, anything else is
// the catalogue. Hashes keep the browser back button and shareable links
// without pulling in a routing library for a two-view app.
function parseHash() {
    const match = window.location.hash.match(/^#\/product\/(.+)$/)

    return match ? { name: 'product', slug: decodeURIComponent(match[1]) } : { name: 'list' }
}

export default function App() {
    const [route, setRoute] = useState(parseHash)
    const [categories, setCategories] = useState([])
    const [category, setCategory] = useState(null)
    const [query, setQuery] = useState('')
    const [products, setProducts] = useState([])
    const [status, setStatus] = useState('loading')

    useEffect(() => {
        const onHashChange = () => {
            setRoute(parseHash())
            window.scrollTo(0, 0)
        }
        window.addEventListener('hashchange', onHashChange)

        return () => window.removeEventListener('hashchange', onHashChange)
    }, [])

    useEffect(() => {
        fetchCategories()
            .then(setCategories)
            .catch(() => setCategories([]))
    }, [])

    useEffect(() => {
        let active = true
        setStatus('loading')

        // Debounce while typing a search; category clicks apply immediately.
        const delay = query ? 250 : 0
        const timer = setTimeout(() => {
            fetchProducts({ category, query })
                .then((items) => {
                    if (active) {
                        setProducts(items)
                        setStatus('ready')
                    }
                })
                .catch(() => {
                    if (active) {
                        setStatus('error')
                    }
                })
        }, delay)

        return () => {
            active = false
            clearTimeout(timer)
        }
    }, [category, query])

    return (
        <main className="app">
            <header className="app__header">
                <h1><a className="app__brand" href="#/">Beans &amp; Leaves</a></h1>
                {route.name === 'list' && (
                    <input
                        type="search"
                        className="app__search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search products…"
                        aria-label="Search products"
                    />
                )}
            </header>

            {route.name === 'product' ? (
                <ProductDetail slug={route.slug} />
            ) : (
                <>
                    {query === '' && (
                        <CategoryFilter
                            categories={categories}
                            active={category}
                            onSelect={setCategory}
                            disabled={status === 'loading'}
                        />
                    )}

                    {status === 'error' && <p className="notice notice--error">Could not load products. Please try again.</p>}
                    {status === 'ready' && products.length === 0 && <p className="notice">No products found.</p>}

                    <section className="grid">
                        {products.map((product) => (
                            <ProductCard key={product.slug} product={product} />
                        ))}
                    </section>
                </>
            )}

            <footer className="app__note">
                <p>
                    This is a React single-page <strong>demo</strong> of the shop. It renders the same
                    catalogue and product pages as the main site, but as a client-side app that reads the
                    JSON API (<code>/api/products</code>, <code>/api/products/{'{slug}'}</code>,
                    <code>/api/categories</code>) instead of server-rendered pages. Cart and checkout stay
                    in the main store.
                </p>
                <p>
                    <a href="/">← Back to the main store</a>
                </p>
            </footer>
        </main>
    )
}
