import { useEffect, useState } from 'react'
import { fetchCategories, fetchProducts } from './api.js'
import CategoryFilter from './components/CategoryFilter.jsx'
import ProductCard from './components/ProductCard.jsx'

export default function App() {
    const [categories, setCategories] = useState([])
    const [category, setCategory] = useState(null)
    const [query, setQuery] = useState('')
    const [products, setProducts] = useState([])
    const [status, setStatus] = useState('loading')

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
                <h1>Beans &amp; Leaves</h1>
                <input
                    type="search"
                    className="app__search"
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Search products…"
                    aria-label="Search products"
                />
            </header>

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

            <footer className="app__note">
                <p>
                    This is a React single-page <strong>demo</strong> of the shop. It renders the same
                    catalogue as the main site, but as a client-side app that reads the JSON API
                    (<code>/api/products</code>, <code>/api/categories</code>) instead of server-rendered pages.
                </p>
                <p>
                    <a href="/">← Back to the main store</a>
                </p>
            </footer>
        </main>
    )
}
