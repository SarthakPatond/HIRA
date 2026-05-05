import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import ContentImage from "../components/ContentImage";
import Seo from "../components/Seo";
import ErrorState from "../components/ErrorState";
import LoadingState from "../components/LoadingState";
import ProductCard from "../components/ProductCard";
import Reveal from "../components/Reveal";
import Section from "../components/Section";
import { api } from "../lib/api";

function extractIdFromSlug(slug = "") {
  const match = slug.match(/-(\d+)$/);
  return match ? Number(match[1]) : 0;
}

export default function ProductDetailsPage() {
  const { slug } = useParams();
  const [state, setState] = useState({
    loading: true,
    error: "",
    product: null,
    relatedProducts: []
  });

  useEffect(() => {
    const id = extractIdFromSlug(slug);
    if (!id) {
      setState({
        loading: false,
        error: "Invalid product link.",
        product: null,
        relatedProducts: []
      });
      return;
    }

    Promise.all([api.getProduct(id), api.getProducts({ includeComingSoon: true })])
      .then(([product, products]) => {
        const relatedProducts = products
          .filter(
            (item) =>
              item.id !== product.id &&
              (item.category === product.category || !item.is_coming_soon)
          )
          .slice(0, 3);

        setState({
          loading: false,
          error: "",
          product,
          relatedProducts
        });
      })
      .catch((error) => {
        setState({
          loading: false,
          error: error.message || "Unable to load product.",
          product: null,
          relatedProducts: []
        });
      });
  }, [slug]);

  if (state.loading) return <LoadingState label="Loading product" />;
  if (state.error) return <ErrorState message={state.error} />;

  const { product, relatedProducts } = state;
  const description =
    product.description ||
    `Explore ${product.name} from Hira FMCG, including pack sizes, benefits, and wholesale inquiry options.`;

  return (
    <>
      <Seo
        title={`${product.name} | Hira FMCG`}
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "Product",
          name: product.name,
          description,
          category: product.category,
          image: product.image ? [product.image] : [],
          brand: {
            "@type": "Brand",
            name: "Hira FMCG"
          }
        }}
      />

      <Section>
          <div className="grid gap-10 lg:grid-cols-[0.92fr_1.08fr]">
            <Reveal>
              <div className="overflow-hidden rounded-[2rem] border border-hira-orange/10 bg-white p-4 shadow-soft">
                <ContentImage
                  src={product.image}
                  alt={product.name}
                  className="h-full min-h-[480px] w-full rounded-2xl object-cover"
                  fallbacks={[
                    "https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?auto=format&fit=crop&w=1100&q=80"
                  ]}
                />
              </div>
            </Reveal>

            <Reveal>
              <div className="rounded-[2rem] border border-hira-orange/10 bg-white p-8 shadow-soft">
                <p className="text-xs font-semibold uppercase tracking-[0.26em] text-hira-orange">
                  {product.category}
                </p>
                <h1 className="mt-4 font-display text-5xl text-hira-forest">
                  {product.name}
                </h1>
                <p className="mt-5 text-lg leading-8 text-hira-ink/75">
                  {product.description}
                </p>

                <div className="mt-8 grid gap-6 md:grid-cols-2">
                  <div>
                    <h2 className="font-display text-3xl text-hira-forest">
                      Benefits
                    </h2>
                    <ul className="mt-4 grid gap-3">
                      {product.benefits.length ? (
                        product.benefits.map((benefit) => (
                          <li
                            key={benefit}
                            className="rounded-2xl bg-hira-wheat/35 px-4 py-3 text-sm leading-7 text-hira-ink/80"
                          >
                            {benefit}
                          </li>
                        ))
                      ) : (
                        <li className="text-sm text-hira-ink/65">
                          Benefits will be added soon.
                        </li>
                      )}
                    </ul>
                  </div>
                  <div>
                    <h2 className="font-display text-3xl text-hira-forest">
                      Pack Sizes
                    </h2>
                    <div className="mt-4 flex flex-wrap gap-3">
                      {product.pack_sizes.length ? (
                        product.pack_sizes.map((size) => (
                          <span
                            key={size}
                            className="rounded-full bg-hira-forest px-4 py-2 text-sm font-semibold text-white"
                          >
                            {size}
                          </span>
                        ))
                      ) : (
                        <p className="text-sm text-hira-ink/65">
                          Pack size information will be added soon.
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                <div className="mt-10 flex flex-wrap gap-4">
                  <Link
                    to="/distributor"
                    className="rounded-full bg-hira-orange px-6 py-3 font-semibold text-white transition hover:bg-hira-red"
                  >
                    Inquiry
                  </Link>
                  <Link
                    to="/products"
                    className="rounded-full border border-hira-ink/10 bg-hira-cream px-6 py-3 font-semibold text-hira-ink"
                  >
                    Back to Products
                  </Link>
                </div>

                {product.is_coming_soon ? (
                  <div className="mt-6 rounded-[1.5rem] border border-hira-gold/40 bg-hira-gold/15 p-5 text-sm leading-7 text-hira-ink/75">
                    This product is marked as coming soon. Distributors can still
                    reach out to discuss early interest and launch planning.
                  </div>
                ) : null}
              </div>
            </Reveal>
          </div>

          <div className="mt-14 rounded-[2rem] border border-hira-orange/10 bg-hira-ink p-8 text-white shadow-soft">
            <div className="flex flex-wrap items-end justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-wheat">
                  Keep Exploring
                </p>
                <h2 className="mt-3 font-display text-4xl">
                  Related products and next actions
                </h2>
              </div>
              <Link
                to="/contact"
                className="rounded-full bg-white px-6 py-3 font-semibold text-hira-ink"
              >
                Contact Sales
              </Link>
            </div>

            <div className="product-grid mt-8 grid gap-6">
              {relatedProducts.map((item) => (
                <Reveal key={item.id}>
                  <ProductCard product={item} />
                </Reveal>
              ))}
            </div>
          </div>
      </Section>
    </>
  );
}
