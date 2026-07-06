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
