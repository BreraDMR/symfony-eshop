export default function CategoryFilter({ categories, active, onSelect, disabled }) {
    return (
        <nav className="filter" aria-label="Product categories">
            <button
                type="button"
                className={active === null ? 'filter__item filter__item--active' : 'filter__item'}
                onClick={() => onSelect(null)}
                disabled={disabled}
            >
                All
            </button>
            {categories.map((category) => (
                <button
                    key={category.slug}
                    type="button"
                    className={active === category.slug ? 'filter__item filter__item--active' : 'filter__item'}
                    onClick={() => onSelect(category.slug)}
                    disabled={disabled}
                >
                    {category.name}
                </button>
            ))}
        </nav>
    )
}
