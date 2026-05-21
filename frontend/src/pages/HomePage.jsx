import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import Container from "../components/Container";
import Seo from "../components/Seo";
import ErrorState from "../components/ErrorState";
import HomeHero from "../components/HomeHero";
import LoadingState from "../components/LoadingState";
import ProductCard from "../components/ProductCard";
import RecipeCard from "../components/RecipeCard";
import Reveal from "../components/Reveal";
import Section from "../components/Section";
import SectionHeading from "../components/SectionHeading";
import ContentImage from "../components/ContentImage";
import { usePageContent } from "../hooks/usePageContent";
import { api } from "../lib/api";

const trustBadgeTones = [
  "from-hira-orange/20 to-hira-wheat/40 text-hira-red",
  "from-hira-green/30 to-white text-hira-forest",
  "from-hira-orange/10 to-white text-hira-orange",
  "from-hira-wheat/25 to-white text-hira-ink"
];

const imageFallbacks = {
  hero: [
    "https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=1600&q=80"
  ],
  story: [
    "https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=80"
  ],
  poha: [
    "https://images.unsplash.com/photo-1617191519105-d07b98b10de4?auto=format&fit=crop&w=1200&q=80"
  ],
  sabudana: [
    "https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=1200&q=80"
  ],
  snacks: [
    "https://images.unsplash.com/photo-1512152272829-e3139592d56f?auto=format&fit=crop&w=1200&q=80"
  ],
  comingSoon: [
    "https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=1200&q=80"
  ]
};

function normalizeCategory(value) {
  return (value || "").toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();
}

function productMatchesCategory(productCategory, categoryName) {
  const productKey = normalizeCategory(productCategory);
  const categoryKey = normalizeCategory(categoryName);

  if (!categoryKey) {
    return true;
  }

  if (productKey === categoryKey) {
    return true;
  }

  if (productKey.includes(categoryKey) || categoryKey.includes(productKey)) {
    return true;
  }

  if (categoryKey.includes("snack")) {
    return /snack|fryum|parmal|namkeen/.test(productKey);
  }

  if (categoryKey.includes("poha")) {
    return productKey.includes("poha");
  }

  if (categoryKey.includes("sabudana")) {
    return productKey.includes("sabudana");
  }

  return false;
}

function TrustBadge({ item, index }) {
  const tone = trustBadgeTones[index % trustBadgeTones.length];

  // Prevent crashes if title contains extra whitespace / empty tokens
  const initials = (item?.title || "")
    .trim()
    .split(/\s+/)
    .map((word) => word?.[0])
    .filter(Boolean)
    .join("")
    .slice(0, 3);

  return (
    <article className="rounded-[2rem] border border-hira-orange/10 bg-white/85 p-6 shadow-soft backdrop-blur">
      <div
        className={`grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br ${tone} text-sm font-bold uppercase tracking-[0.2em]`}
      >
        {initials}
      </div>
      <h3 className="mt-5 font-display text-3xl text-hira-ink">{item?.title}</h3>
      <p className="mt-2 text-sm leading-7 text-hira-ink/65">{item?.text}</p>
    </article>
  );
}

export default function HomePage() {
  const { content, loading, error } = usePageContent("home");
  const [products, setProducts] = useState([]);
  const [productError, setProductError] = useState("");
  const [featuredRecipes, setFeaturedRecipes] = useState([]);
  const [recipeError, setRecipeError] = useState("");
  const [activeCategory, setActiveCategory] = useState("");
  const [activeRecipeCategory, setActiveRecipeCategory] = useState("Poha");

  useEffect(() => {
    api
      .getProducts({ includeComingSoon: true })
      .then((data) => setProducts(data))
      .catch((err) => setProductError(err.message || "Unable to load products."));
  }, []);

  useEffect(() => {
    api
      .getFeaturedRecipes(6)
      .then((data) => {
        const normalized = Array.isArray(data)
          ? data.map((recipe) => ({
              id: recipe.id,
              slug: recipe.slug,
              name: recipe.name,
              category: recipe.category,
              shortDescription: recipe.short_description || "",
              heroImage: recipe.hero_image || recipe.thumbnail_image || "",
              image: recipe.thumbnail_image || recipe.hero_image || "",
              cookingTimeMinutes: recipe.cook_time_minutes ?? 0,
              difficulty: recipe.difficulty || "Easy",
              servings: recipe.servings ?? 2
            }))
          : [];

        setFeaturedRecipes(normalized);
      })
      .catch((err) => setRecipeError(err.message || "Unable to load featured recipes."));
  }, []);

  const liveProducts = useMemo(
    () => products.filter((product) => !product.is_coming_soon),
    [products]
  );

  const categoryTabs = useMemo(
    () => (content?.categories || []).filter((category) => category?.name),
    [content]
  );

  useEffect(() => {
    if (!categoryTabs.length) {
      return;
    }

    const hasActive = categoryTabs.some((category) => category.name === activeCategory);
    if (!hasActive) {
      setActiveCategory(categoryTabs?.[0]?.name || "");
    }
  }, [activeCategory, categoryTabs]);

  const filteredProducts = useMemo(() => {
    if (!activeCategory) {
      return liveProducts;
    }

    return liveProducts.filter((product) =>
      productMatchesCategory(product.category, activeCategory)
    );
  }, [activeCategory, liveProducts]);

  const recipeCategoryChips = ["Poha", "Sabudana", "Snacks"];
  const filteredFeaturedRecipes = useMemo(() => {
    const filtered = featuredRecipes.filter((recipe) =>
      activeRecipeCategory ? productMatchesCategory(recipe.category, activeRecipeCategory) : true
    );

    return filtered.length ? filtered : featuredRecipes;
  }, [activeRecipeCategory, featuredRecipes]);

  if (loading) return <LoadingState label="Loading homepage" />;
  if (error) return <ErrorState message={error} />;

  const description =
    "Hira FMCG brings Ujjain-rooted staples and evolving snack ideas into a premium brand experience shaped by story, heritage, and trust.";
  const storySteps = content?.story?.steps || [];
  const heritageHighlights = content?.heritage?.highlights || [];
  const comingSoonItems = content?.coming_soon?.items || [];
  const trustItems = content?.trust?.items || [];
  const activeCategoryDetails =
    categoryTabs.find((category) => category.name === activeCategory) || null;

  const heritageInsights = Array.isArray(content?.heritage?.insights)
    ? content.heritage.insights
    : [];

  const featuredProducts = (Array.isArray(filteredProducts) ? filteredProducts : []).slice(
    0,
    3
  );

  const leadRecipe = filteredFeaturedRecipes[0] || null;
  const supportingRecipes = filteredFeaturedRecipes.slice(1, 4);

  function getCategoryFallbacks(categoryName) {
    const key = normalizeCategory(categoryName);

    if (key.includes("poha")) return imageFallbacks.poha;
    if (key.includes("sabudana")) return imageFallbacks.sabudana;
    if (key.includes("snack")) return imageFallbacks.snacks;

    return imageFallbacks.poha;
  }

  return (
    <div className="overflow-hidden">
      <Seo
        title="Hira FMCG | Ujjain to Modern Kitchens"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "WebSite",
          name: "Hira FMCG",
          description,
          url: "http://localhost:5173/"
        }}
      />

      <HomeHero hero={content.hero} story={content.story} categories={content.categories} />

      <Section id="home-story" className="relative">
          <div className="grid items-center gap-10 lg:grid-cols-[0.92fr_1.08fr]">
            <Reveal>
              <SectionHeading
                eyebrow="Our Story"
                title={content.story?.title}
                description={content.story?.intro}
              />

              <div className="mt-10 space-y-5">
                {storySteps.map((step, index) => (
                  <article
                    key={`${step.title}-${index}`}
                    className="surface-panel rounded-[1.8rem] p-6"
                  >
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                      Chapter 0{index + 1}
                    </p>
                    <h3 className="mt-3 font-display text-3xl text-hira-ink">
                      {step.title}
                    </h3>
                    <p className="mt-3 text-base leading-8 text-hira-ink/72">
                      {step.text}
                    </p>
                  </article>
                ))}
              </div>
            </Reveal>

            <Reveal className="lg:pl-8">
              <div className="relative overflow-hidden rounded-[2rem] border border-white/50 bg-white/70 p-4 shadow-soft backdrop-blur">
                <ContentImage
                  src={content.story?.image}
                  alt={content.story?.title}
                  className="h-[620px] w-full rounded-2xl object-cover"
                  fallbacks={imageFallbacks.story}
                />
                <div className="absolute inset-x-8 bottom-8 rounded-[1.8rem] bg-gradient-to-r from-black/80 via-black/70 to-hira-red/70 p-7 text-white">
                  <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-wheat">
                    {content.story?.overlay_eyebrow || 'USA to Ujjain'}
                  </p>
                  <p className="mt-4 max-w-xl text-lg leading-8 text-white/86">
                    {content.story?.overlay_text || 'A brand born from movement, memory, and the belief that everyday staples can carry both emotional depth and modern confidence.'}
                  </p>
                </div>
              </div>
            </Reveal>
          </div>
      </Section>

      <Section className="relative bg-white/60">
          <Container className="max-w-7xl mx-auto px-6 py-16">
            <div className="overflow-hidden rounded-[2rem] border border-hira-orange/10 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.14),transparent_32%),linear-gradient(135deg,rgba(255,247,237,0.98),rgba(255,255,255,0.92))] p-8 shadow-soft sm:p-10 lg:p-14">
              <div className="grid gap-10 lg:grid-cols-[0.95fr_1.05fr]">
                <Reveal>
                  <SectionHeading
                    eyebrow="Heritage"
                    title={content.heritage?.title}
                    description={content.heritage?.text}
                  />
                  <div className="mt-8 rounded-[1.8rem] bg-hira-ink px-6 py-7 text-white shadow-soft">
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-wheat">
                      {content.heritage?.subtitle || 'Why Ujjain Matters'}
                    </p>
                    <p className="mt-4 font-display text-3xl leading-tight">
                      {content.heritage?.tagline || 'A place where staple foods are not just sourced, but known.'}
                    </p>
                  </div>
                </Reveal>

                <div className="grid gap-5 md:grid-cols-3">
                  {heritageHighlights.map((item, index) => (
                    <Reveal key={item?.title ?? index}>
                      <article className="surface-panel h-full rounded-[1.8rem] p-6">
                        <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-forest">
                          {heritageInsights[index]?.label || "Rooted Insight"}
                        </p>
                        <h3 className="mt-4 font-display text-3xl text-hira-ink">
                          {item.title}
                        </h3>
                        <p className="mt-4 text-sm leading-7 text-hira-ink/68">
                          {item.text}
                        </p>
                      </article>
                    </Reveal>
                  ))}
                </div>
              </div>
            </div>
          </Container>
      </Section>

      <Section className="bg-white">
          <div className="flex flex-wrap items-end justify-between gap-6">
            <SectionHeading
              eyebrow={content.showcase?.eyebrow}
              title={content.showcase?.title}
              description={productError || content.showcase?.text}
            />
            <Link
              to="/products"
              className="rounded-full border border-hira-orange/20 bg-hira-cream px-6 py-3 text-sm font-semibold text-hira-ink transition hover:-translate-y-0.5 hover:border-hira-orange/40 hover:text-hira-orange"
            >
              View Full Range
            </Link>
          </div>

          <div className="mt-10 hide-scrollbar flex gap-3 overflow-x-auto pb-2">
            {categoryTabs.map((category) => (
              <button
                key={category.name}
                type="button"
                onClick={() => setActiveCategory(category.name)}
                className={`whitespace-nowrap rounded-full px-5 py-3 text-sm font-semibold transition ${
                  activeCategory === category.name
                    ? "bg-hira-orange text-white shadow-soft"
                    : "border border-hira-orange/10 bg-white text-hira-ink/80 hover:border-hira-orange/30 hover:text-hira-orange"
                }`}
              >
                {category.name}
              </button>
            ))}
          </div>

          {/* <div className="mt-10 grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
            <Reveal>
              <article className="group relative overflow-hidden rounded-2xl border border-hira-orange/10 bg-hira-cream/70 p-4 shadow-soft">
                <div className="absolute inset-0 bg-gradient-to-br from-white/40 via-transparent to-hira-green/10" />
                <ContentImage
                  src={activeCategoryDetails?.image}
                  alt={activeCategoryDetails?.name}
                  className="h-[440px] w-full rounded-2xl object-cover transition-transform duration-300 group-hover:scale-105"
                  fallbacks={getCategoryFallbacks(activeCategoryDetails?.name)}
                />
                <div className="absolute inset-x-7 bottom-7 rounded-[1.6rem] bg-white/90 p-6 backdrop-blur">
                  <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                    Featured Category
                  </p>
                  <h3 className="mt-3 font-display text-4xl text-hira-ink">
                    {activeCategoryDetails?.name}
                  </h3>
                  <p className="mt-3 text-base leading-8 text-hira-ink/72">
                    {activeCategoryDetails?.description}
                  </p>
                </div>
              </article>
            </Reveal>

            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
              {featuredProducts.map((product) => (
                <Reveal key={product.id}>
                  <ProductCard product={product} />
                </Reveal>
              ))}
            </div>
          </div> */}
          <div className="mt-10 grid md:grid-cols-3 gap-8 items-stretch">

  {/* FEATURED CATEGORY - conditional render if image exists */}
  {/* {activeCategoryDetails?.image && (
    <Reveal>
      <article className="h-full flex flex-col overflow-hidden rounded-2xl border border-hira-orange/10 bg-hira-cream/70 shadow-soft group">

        <ContentImage
          src={activeCategoryDetails?.image}
          alt={activeCategoryDetails?.name}
          className="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-105"
          fallbacks={getCategoryFallbacks(activeCategoryDetails?.name)}
        />

        <div className="p-6 flex-grow font-sans">
          <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
            Featured Category
          </p>

          <h3 className="mt-3 font-display text-3xl font-semibold leading-snug text-hira-ink">
            {activeCategoryDetails?.name}
          </h3>

          <p className="mt-3 text-base leading-7 text-hira-ink/70">
            {activeCategoryDetails?.description}
          </p>
        </div>

      </article>
    </Reveal>
  )} */}
{activeCategoryDetails?.image?.startsWith?.("/uploads/") && (
  <Reveal>
    <article className="h-full flex flex-col overflow-hidden rounded-2xl border border-hira-orange/10 bg-hira-cream/70 shadow-soft group">

      <ContentImage
        src={activeCategoryDetails?.image}
        alt={activeCategoryDetails?.name}
        className="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-105"
        fallbacks={getCategoryFallbacks(activeCategoryDetails?.name)}
      />

      <div className="p-6 flex-grow font-sans">
        <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
          Featured Category
        </p>

        <h3 className="mt-3 font-display text-3xl font-semibold leading-snug text-hira-ink">
          {activeCategoryDetails?.name}
        </h3>

        <p className="mt-3 text-base leading-7 text-hira-ink/70">
          {activeCategoryDetails?.description}
        </p>
      </div>

    </article>
  </Reveal>
)}


  {/* PRODUCTS */}
  {(featuredProducts || []).map((product) => (
    <Reveal key={product?.id}>
      <ProductCard product={product} />
    </Reveal>
  ))}

</div>
      </Section>

      <Section className="bg-white">
        <Container className="max-w-7xl mx-auto px-6 py-12">
          <div className="flex flex-col gap-5">
            <div className="flex flex-wrap items-end justify-between gap-6">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                  FROM HIRA KITCHENS
                </p>

                <h2 className="mt-3 font-display text-3xl sm:text-4xl text-hira-forest">
                  Recipes crafted for modern kitchens
                </h2>

                <p className="mt-4 max-w-3xl text-base leading-7 text-hira-ink/72">
                  Discover simple, delicious recipes made using HIRA essentials — from everyday poha favorites to snack-time classics.
                </p>
              </div>

              <Link
                to="/recipes"
                className="mt-2 rounded-full border border-hira-orange/20 bg-white px-6 py-3 text-sm font-semibold text-hira-ink transition hover:-translate-y-0.5 hover:border-hira-orange/40 hover:text-hira-orange"
              >
                View all recipes
              </Link>
            </div>

            <div className="hide-scrollbar flex gap-3 overflow-x-auto pb-2">
              {recipeCategoryChips.map((category) => (
                <button
                  key={category}
                  type="button"
                  onClick={() => setActiveRecipeCategory(category)}
                  className={`whitespace-nowrap rounded-full px-5 py-3 text-sm font-semibold transition ${
                    activeRecipeCategory === category
                      ? "bg-hira-orange text-white shadow-soft"
                      : "border border-hira-orange/10 bg-white text-hira-ink/80 hover:border-hira-orange/30 hover:text-hira-orange"
                  }`}
                >
                  {category}
                </button>
              ))}
            </div>
          </div>

          <div className="mt-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            {(filteredFeaturedRecipes || []).slice(0, 4).map((recipe) => (
              <Reveal key={recipe.slug}>
                <RecipeCard recipe={recipe} />
              </Reveal>
            ))}
          </div>
        </Container>
      </Section>

      <Section className="bg-hira-cream/70">
          <SectionHeading
            eyebrow="Coming Soon"
            title={content.coming_soon?.title}
            description={content.coming_soon?.text}
            align="center"
          />

          <div className="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {comingSoonItems.map((item) => (
              <Reveal key={item.title}>
                <article className="group relative overflow-hidden rounded-2xl border border-hira-orange/10 shadow-soft">
                  <ContentImage
                    src={item.image}
                    alt={item.title}
                    className="h-80 w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    fallbacks={imageFallbacks.comingSoon}
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent" />
                  <div className="absolute inset-x-6 bottom-6 rounded-[1.6rem] bg-white/86 p-6 backdrop-blur-md">
                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-white">
                      Something exciting is taking shape...
                    </p>
                    <h3 className="mt-3 font-display text-3xl text-white">
                      {item.title}
                    </h3>
                  </div>
                </article>
              </Reveal>
            ))}
          </div>
      </Section>

      <Section id="trust">
          <div className="grid gap-8 lg:grid-cols-[0.86fr_1.14fr]">
            <Reveal>
              <SectionHeading
                eyebrow="Trust"
                title={content.trust?.title}
                description={content.trust?.text}
              />
            </Reveal>

            <div className="grid gap-5 sm:grid-cols-2">
              {trustItems.map((item, index) => (
                <Reveal key={item?.title ?? index}>
                  <TrustBadge item={item} index={index} />
                </Reveal>
              ))}
            </div>
          </div>
      </Section>

      <section className="pb-16 pt-6">
        <Container>
          <Reveal>
            <div className="overflow-hidden rounded-[2rem] bg-gradient-to-r from-hira-orange via-hira-red to-red-700 p-8 text-white shadow-soft sm:p-12">
              <div className="grid items-center gap-8 lg:grid-cols-[1.06fr_0.94fr]">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-hira-wheat">
                    Partnership
                  </p>
                  <h2 className="mt-4 font-display text-5xl leading-none sm:text-6xl">
                    {content.cta?.title}
                  </h2>
                  <p className="mt-5 max-w-2xl text-lg leading-8 text-white/88">
                    {content.cta?.text}
                  </p>
                </div>
                <div className="flex justify-start lg:justify-end">
                  <Link
                    to={content.cta?.button_link || "/distributor"}
                    className="rounded-full bg-white px-7 py-4 text-base font-semibold text-hira-red transition hover:scale-[1.02]"
                  >
                    {content.cta?.button_label || "Become Distributor"}
                  </Link>
                </div>
              </div>
            </div>
          </Reveal>
        </Container>
      </section>
    </div>
  );
}
