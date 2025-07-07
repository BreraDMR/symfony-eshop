async function getJson(url) {
    const response = await fetch(url)

    if (!response.ok) {
        throw new Error(`Request to ${url} failed with ${response.status}`)
    }

    return response.json()
}

export async function fetchCategories() {
    const data = await getJson('/api/categories')

    return data.categories
}

export async function fetchProducts({ category, query }) {
    const params = new URLSearchParams()

    if (query) {
        params.set('q', query)
    } else if (category) {
        params.set('category', category)
    }

    const suffix = params.toString() ? `?${params.toString()}` : ''
    const data = await getJson(`/api/products${suffix}`)

    return data.products
}

// Returns the product, or null when the API answers 404 (unknown slug).
export async function fetchProduct(slug) {
    const response = await fetch(`/api/products/${encodeURIComponent(slug)}`)

    if (response.status === 404) {
        return null
    }

    if (!response.ok) {
        throw new Error(`Request to /api/products/${slug} failed with ${response.status}`)
    }

    const data = await response.json()

    return data.product
}
