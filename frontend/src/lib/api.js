const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || "http://localhost/HIRA/backend/api";

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE_URL}${path}`, options);
  const data = await response.json().catch(() => ({}));

  if (!response.ok || data.success === false) {
    throw new Error(data.message || "Something went wrong.");
  }

  return data.data;
}

export const api = {
  getPage: (page) => request(`/getPage.php?page=${encodeURIComponent(page)}`),
  getProducts: ({ includeComingSoon = true, category = "" } = {}) =>
    request(
      `/products.php?include_coming_soon=${includeComingSoon ? "1" : "0"}${
        category ? `&category=${encodeURIComponent(category)}` : ""
      }`
    ),
  getProduct: (id) => request(`/getProduct.php?id=${id}`),

  // Recipes
  getFeaturedRecipes: (limit = 4) =>
    request(`/getFeaturedRecipes.php?limit=${encodeURIComponent(String(limit || 4))}`),
  getRecipes: ({ category = "", status = "published" } = {}) => {
    const params = new URLSearchParams();
    if (category) params.set("category", category);
    if (status) params.set("status", status);
    const qs = params.toString();
    return request(`/getRecipes.php${qs ? `?${qs}` : ""}`);
  },
  getRecipeDetailsBySlug: (slug) =>
    request(
      `/getRecipeDetailsBySlug.php?slug=${encodeURIComponent(String(slug || "").trim())}`
    ),

  submitLead: (payload) =>
    request("/submitLead.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    }),
  submitContact: (payload) =>
    request("/submitContact.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
};
