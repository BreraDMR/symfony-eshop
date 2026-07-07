import { useEffect, useState } from 'react'
import { fetchProduct } from '../api.js'

// Client-side product page for the SPA. Cart and checkout stay in the
// server-rendered app (session + CSRF), so the buy action links back there.
export default function ProductDetail({ slug }) {
    const [product, setProduct] = useState(null)
    const [status, setStatus] = useState('loading') // loading | ready | missing | error

    useEffect(() => {
        let active = true
        setStatus('loading')

        fetchProduct(slug)
            .then((data) => {
                if (!active) {
                    return
                }
                if (data === null) {
                    setStatus('missing')
                } else {
                    setProduct(data)
                    setStatus('ready')
                }
            })
            .catch(() => {
                if (active) {
                    setStatus('error')
                }
            })

        return () => {
            active = false
        }
    }, [slug])

    return (
        <section className="detail">
            <a className="detail__back" href="#/">← Back to catalogue</a>

            {status === 'loading' && <p className="notice">Loading…</p>}
            {status === 'error' && <p className="notice notice--error">Could not load this product.</p>}
            {status === 'missing' && <p className="notice">This product could not be found.</p>}

            {status === 'ready' && product && (
                <article className="detail__body">
                    <div className="detail__media">
                        {product.image ? (
                            <img src={product.image} alt={product.name} />
                        ) : (
                            <span className="detail__placeholder" aria-hidden="true">☕</span>
                        )}
                    </div>
                    <div className="detail__info">
                        <p className="detail__category">{product.category}</p>
                        <h2 className="detail__name">{product.name}</h2>
                        <p className="detail__price">{product.price.formatted}</p>
                        {product.description && <p className="detail__desc">{product.description}</p>}
                        <p className={product.inStock ? 'detail__stock' : 'detail__stock detail__stock--out'}>
                            {product.inStock ? 'In stock' : 'Out of stock'}
                        </p>
                        {product.inStock && (
                            <a className="detail__cta" href={`/product/${product.slug}`}>Add to cart in the shop →</a>
                        )}
                    </div>
                </article>
            )}
        </section>
    )
}
