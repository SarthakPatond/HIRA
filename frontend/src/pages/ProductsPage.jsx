import { useEffect, useMemo, useState } from "react";
import Section from "../components/Section";
import ErrorState from "../components/ErrorState";
import LoadingState from "../components/LoadingState";
import PageHero from "../components/PageHero";
import ProductCard from "../components/ProductCard";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import { api } from "../lib/api";

export default function ProductsPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [products, setProducts] = useState([]);
  const [activeCategory, setActiveCategory] = useState("All");

  useEffect(() => {
    api
      .getProducts({ includeComingSoon: true })
      .then((data) => {
        setProducts(data);
        setLoading(false);
      })
      .catch((err) => {
        setError(err.message || "Unable to load products.");
        setLoading(false);
      });
  }, []);

  const categories = useMemo(() => {
    const values = [...new Set(products.map((product) => product.category))];
    return ["All", ...values];
  }, [products]);

  const filteredProducts =
    activeCategory === "All"
      ? products
      : products.filter((product) => product.category === activeCategory);

  if (loading) return <LoadingState label="Loading products" />;
  if (error) return <ErrorState message={error} />;

  const description =
    "Browse Hira FMCG's products across poha, sabudana, parmal, and snacks with category-led navigation built for both shoppers and distributors.";

  return (
    <div>
      <Seo
        title="Products | Hira FMCG"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "CollectionPage",
          name: "Hira FMCG Products",
          description,
          url: "https://ujjainipoha.com/products"
        }}
      />

      <PageHero
        eyebrow="Product Portfolio"
        title="Made for modern shelves, trusted in everyday kitchens"
        description="Browse the Hira catalog across staples and snacks, with category filters that make large catalogs easy to manage."
        image="https://plus.unsplash.com/premium_photo-1715959420730-de9456ad726d?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
      />

      <Section>
          <div className="hide-scrollbar flex gap-3 overflow-x-auto rounded-full border border-hira-orange/10 bg-white p-3 shadow-soft">
            {categories.map((category) => (
              <button
                key={category}
                type="button"
                onClick={() => setActiveCategory(category)}
                className={`whitespace-nowrap rounded-full px-5 py-3 text-sm font-semibold transition ${
                  activeCategory === category
                    ? "bg-hira-orange text-white"
                    : "bg-hira-cream text-hira-ink hover:bg-hira-wheat/20"
                }`}
              >
                {category}
              </button>
            ))}
          </div>

          <div className="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            {filteredProducts.map((product) => (
              <Reveal key={product.id}>
                <ProductCard product={product} />
              </Reveal>
            ))}
          </div>
      </Section>
    </div>
  );
}
