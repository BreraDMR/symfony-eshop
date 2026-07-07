export default function ProductCard({ product }) {
    return (
        <a className="card" href={`#/product/${product.slug}`}>
            <div className="card__thumb">
                {product.image ? (
                    <img src={product.image} alt={product.name} loading="lazy" />
                ) : (
                    <span className="card__placeholder" aria-hidden="true">☕</span>
                )}
            </div>
            <h3 className="card__name">{product.name}</h3>
            <p className="card__category">{product.category}</p>
            <p className="card__price">{product.price.formatted}</p>
            {product.inStock ? (
                <span className="card__stock">In stock</span>
            ) : (
                <span className="card__stock card__stock--out">Out of stock</span>
            )}
        </a>
    )
}
