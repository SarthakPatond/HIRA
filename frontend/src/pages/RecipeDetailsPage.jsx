import { useEffect, useMemo, useRef, useState } from "react";
import { Link, useParams } from "react-router-dom";
import ContentImage from "../components/ContentImage";
import ErrorState from "../components/ErrorState";
import LoadingState from "../components/LoadingState";
import ProductCard from "../components/ProductCard";
import RecipeCard from "../components/RecipeCard";
import Reveal from "../components/Reveal";
import Section from "../components/Section";
import Seo from "../components/Seo";
import { api } from "../lib/api";

function normalizeRecipeDetails(recipe) {
  if (!recipe || typeof recipe !== "object") return null;

  return {
    id: recipe.id,
    slug: recipe.slug,
    name: recipe.name,
    category: recipe.category,
    shortDescription: recipe.short_description || "",
    heroImage: recipe.hero_image || recipe.thumbnail_image || "",
    image: recipe.thumbnail_image || recipe.hero_image || "",
    cookingTimeMinutes: recipe.cook_time_minutes ?? 0,
    difficulty: recipe.difficulty || "Easy",
    servings: recipe.servings ?? 2,
    tips: Array.isArray(recipe.tips) ? recipe.tips : recipe.tips ? [recipe.tips] : [],
    ingredients: Array.isArray(recipe.ingredients) ? recipe.ingredients : [],
    steps: Array.isArray(recipe.steps) ? recipe.steps : [],
    relatedProducts: Array.isArray(recipe.related_products) ? recipe.related_products : []
  };
}

function normalizeRecipeListing(recipe) {
  if (!recipe || typeof recipe !== "object") return null;

  return {
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
  };
}

function ShareButton({ as = "button", ...props }) {
  const Tag = as;

  return (
    <Tag
      {...props}
      className={[
        "rounded-full border border-hira-orange/12 bg-white px-5 py-3 text-sm font-semibold text-hira-ink transition hover:border-hira-orange/35 hover:text-hira-orange",
        props.className || ""
      ].join(" ").trim()}
    />
  );
}

export default function RecipeDetailsPage() {
  const { slug } = useParams();
  const carouselRef = useRef(null);

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [copied, setCopied] = useState(false);
  const [recipe, setRecipe] = useState(null);
  const [relatedRecipes, setRelatedRecipes] = useState([]);

  useEffect(() => {
    let isMounted = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const details = await api.getRecipeDetailsBySlug(slug);
        const normalizedRecipe = normalizeRecipeDetails(details);

        if (!normalizedRecipe) {
          if (isMounted) setError("Invalid recipe link.");
          return;
        }

        const listing = await api.getRecipes({ category: normalizedRecipe.category });
        const normalizedListing = Array.isArray(listing)
          ? listing.map(normalizeRecipeListing).filter(Boolean)
          : [];

        const nextRelatedRecipes = normalizedListing
          .filter((item) => String(item.slug) !== String(normalizedRecipe.slug))
          .slice(0, 6);

        if (isMounted) {
          setRecipe(normalizedRecipe);
          setRelatedRecipes(nextRelatedRecipes);
        }
      } catch (exception) {
        if (isMounted) {
          setError(exception?.message || "Invalid recipe link.");
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    }

    load();
    return () => {
      isMounted = false;
    };
  }, [slug]);

  const ingredients = useMemo(() => recipe?.ingredients || [], [recipe]);
  const steps = useMemo(() => recipe?.steps || [], [recipe]);
  const tips = useMemo(() => recipe?.tips || [], [recipe]);
  const relatedProducts = useMemo(() => recipe?.relatedProducts || [], [recipe]);

  if (loading) return <LoadingState label="Loading recipe" />;
  if (error) return <ErrorState message={error} />;
  if (!recipe) return <ErrorState message="Invalid recipe link." />;

  const description =
    recipe.shortDescription || `Discover ${recipe.name} made with HIRA products.`;
  const shareUrl =
    typeof window !== "undefined" ? window.location.href : `http://localhost:5173/recipes/${recipe.slug}`;
  const shareText = `${recipe.name} | HIRA Recipes`;

  async function handleCopyLink() {
    try {
      if (navigator?.clipboard?.writeText) {
        await navigator.clipboard.writeText(shareUrl);
      }
      setCopied(true);
      window.setTimeout(() => setCopied(false), 1800);
    } catch {
      setCopied(false);
    }
  }

  function scrollCarousel(direction) {
    if (!carouselRef.current) return;
    carouselRef.current.scrollBy({
      left: direction * 340,
      behavior: "smooth"
    });
  }

  return (
    <>
      <Seo
        title={`${recipe.name} | Recipes | Hira FMCG`}
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "Recipe",
          name: recipe.name,
          description,
          image: recipe.heroImage ? [recipe.heroImage] : [],
          recipeCategory: recipe.category
        }}
      />

      <Section className="pt-8">
        <div className="grid items-stretch gap-8 lg:grid-cols-[1.04fr_0.96fr]">
          <Reveal>
            <div className="overflow-hidden rounded-[2.2rem] border border-hira-orange/12 bg-hira-cream/50 p-4 shadow-soft">
              <div className="aspect-[12/11] w-full overflow-hidden rounded-[1.75rem] bg-white">
                <ContentImage
                  src={recipe.heroImage || recipe.image}
                  alt={recipe.name}
                  className="h-full w-full object-cover"
                  fallbacks={[
                    "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=80"
                  ]}
                />
              </div>
            </div>
          </Reveal>

          <Reveal>
            <div className="rounded-[2.2rem] border border-hira-orange/10 bg-white p-8 shadow-soft">
              <div className="flex flex-wrap gap-3">
                <span className="rounded-full bg-hira-cream px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-hira-orange">
                  {recipe.category}
                </span>
                <span className="rounded-full border border-hira-orange/20 bg-hira-cream px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-hira-ink">
                  {recipe.difficulty}
                </span>
              </div>

              <h1 className="mt-6 font-display text-5xl leading-none text-hira-forest sm:text-6xl">
                {recipe.name}
              </h1>

              <p className="mt-5 max-w-2xl text-lg leading-8 text-hira-ink/74">
                {recipe.shortDescription}
              </p>

              <div className="mt-8 grid gap-4 sm:grid-cols-3">
                <div className="rounded-[1.6rem] bg-hira-cream/45 p-5">
                  <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-orange">
                    Cook time
                  </p>
                  <p className="mt-2 text-2xl font-bold text-hira-forest">
                    {recipe.cookingTimeMinutes} min
                  </p>
                </div>
                <div className="rounded-[1.6rem] bg-hira-cream/45 p-5">
                  <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-orange">
                    Difficulty
                  </p>
                  <p className="mt-2 text-2xl font-bold text-hira-forest">
                    {recipe.difficulty}
                  </p>
                </div>
                <div className="rounded-[1.6rem] bg-hira-cream/45 p-5">
                  <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-orange">
                    Servings
                  </p>
                  <p className="mt-2 text-2xl font-bold text-hira-forest">
                    {recipe.servings}
                  </p>
                </div>
              </div>

              <div className="mt-8">
                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                  Share this recipe
                </p>
                <div className="mt-4 flex flex-wrap gap-3">
                  <ShareButton type="button" onClick={handleCopyLink}>
                    {copied ? "Copied" : "Copy Link"}
                  </ShareButton>
                  <ShareButton
                    as="a"
                    href={`https://wa.me/?text=${encodeURIComponent(`${shareText} ${shareUrl}`)}`}
                    target="_blank"
                    rel="noreferrer"
                  >
                    WhatsApp
                  </ShareButton>
                  <ShareButton
                    as="a"
                    href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`}
                    target="_blank"
                    rel="noreferrer"
                  >
                    Facebook
                  </ShareButton>
                </div>
              </div>

              <div className="mt-10 flex flex-wrap gap-4">
                <Link
                  to="/products"
                  className="rounded-full bg-hira-orange px-7 py-4 font-semibold text-white transition hover:bg-hira-red"
                >
                  Explore HIRA products
                </Link>
                <Link
                  to="/recipes"
                  className="rounded-full border border-hira-ink/10 bg-hira-cream px-7 py-4 font-semibold text-hira-ink transition hover:border-hira-orange/25 hover:text-hira-orange"
                >
                  Back to Recipes
                </Link>
              </div>
            </div>
          </Reveal>
        </div>

        <div className="mt-12 grid gap-8 lg:grid-cols-[0.38fr_0.62fr]">
          <Reveal>
            <div className="rounded-[2rem] border border-hira-orange/10 bg-white p-7 shadow-soft">
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                Ingredients
              </p>
              <h2 className="mt-3 font-display text-3xl text-hira-forest">
                Gather everything first
              </h2>
              <ul className="mt-6 grid gap-3">
                {ingredients.length ? (
                  ingredients.map((ingredient, index) => (
                    <li
                      key={`${ingredient}-${index}`}
                      className="rounded-[1.35rem] bg-hira-cream/42 px-5 py-4 text-sm leading-7 text-hira-ink/80"
                    >
                      {ingredient}
                    </li>
                  ))
                ) : (
                  <li className="text-sm text-hira-ink/65">Ingredients will be added soon.</li>
                )}
              </ul>
            </div>
          </Reveal>

          <Reveal>
            <div className="rounded-[2rem] border border-hira-orange/10 bg-white p-7 shadow-soft">
              <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                    Method
                  </p>
                  <h2 className="mt-3 font-display text-3xl text-hira-forest">
                    Step-by-step timeline
                  </h2>
                </div>
                <div className="rounded-full bg-hira-cream/55 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-hira-orange">
                  {recipe.cookingTimeMinutes} min · Serves {recipe.servings}
                </div>
              </div>

              <div className="mt-8">
                {steps.length ? (
                  <ol className="space-y-6">
                    {steps.map((step, index) => (
                      <li key={`${step}-${index}`} className="relative pl-14">
                        {index < steps.length - 1 ? (
                          <span
                            className="absolute left-5 top-11 h-[calc(100%-14px)] w-px bg-hira-orange/18"
                            aria-hidden="true"
                          />
                        ) : null}
                        <span className="absolute left-0 top-1 inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-hira-orange/12 text-sm font-bold text-hira-orange shadow-soft">
                          {index + 1}
                        </span>
                        <div className="rounded-[1.45rem] border border-hira-orange/10 bg-hira-cream/30 p-5">
                          <p className="text-sm leading-7 text-hira-ink/82">{step}</p>
                        </div>
                      </li>
                    ))}
                  </ol>
                ) : (
                  <p className="text-sm text-hira-ink/65">Steps will be added soon.</p>
                )}
              </div>
            </div>
          </Reveal>
        </div>

        <div className="mt-10">
          <Reveal>
            <div className="rounded-[2rem] border border-hira-orange/10 bg-white p-7 shadow-soft">
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                Tips
              </p>
              <h2 className="mt-3 font-display text-3xl text-hira-forest">
                Make it taste even better
              </h2>
              <div className="mt-6 grid gap-4 md:grid-cols-2">
                {tips.length ? (
                  tips.map((tip, index) => (
                    <div
                      key={`${tip}-${index}`}
                      className="rounded-[1.45rem] bg-hira-cream/42 px-5 py-4 text-sm leading-7 text-hira-ink/80"
                    >
                      {tip}
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-hira-ink/65">Tips will be added soon.</p>
                )}
              </div>
            </div>
          </Reveal>
        </div>

        <div className="mt-10 rounded-[2rem] border border-hira-orange/10 bg-hira-ink p-8 text-white shadow-soft">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-wheat">
                More from this category
              </p>
              <h2 className="mt-3 font-display text-4xl">Related recipes</h2>
            </div>
            <div className="flex gap-3">
              <button
                type="button"
                onClick={() => scrollCarousel(-1)}
                className="rounded-full border border-white/18 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/14"
              >
                Prev
              </button>
              <button
                type="button"
                onClick={() => scrollCarousel(1)}
                className="rounded-full border border-white/18 bg-white/8 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/14"
              >
                Next
              </button>
            </div>
          </div>

          {relatedRecipes.length ? (
            <div
              ref={carouselRef}
              className="hide-scrollbar mt-8 flex gap-6 overflow-x-auto pb-2"
            >
              {relatedRecipes.map((item) => (
                <div key={item.slug} className="min-w-[320px] flex-[0_0_320px]">
                  <RecipeCard recipe={item} />
                </div>
              ))}
            </div>
          ) : (
            <div className="mt-8 rounded-[1.5rem] bg-white/8 px-6 py-5 text-white/75">
              More related recipes will appear here as the CMS catalog grows.
            </div>
          )}
        </div>

        <div className="mt-10">
          <div className="flex flex-wrap items-end justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                Suggested products
              </p>
              <h2 className="mt-3 font-display text-4xl text-hira-forest">
                Cook this with HIRA
              </h2>
            </div>
            <Link
              to="/products"
              className="rounded-full border border-hira-orange/18 bg-white px-6 py-3 text-sm font-semibold text-hira-ink transition hover:border-hira-orange/32 hover:text-hira-orange"
            >
              View all products
            </Link>
          </div>

          {relatedProducts.length ? (
            <div className="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
              {relatedProducts.map((product) => (
                <Reveal key={product.slug}>
                  <ProductCard product={product} />
                </Reveal>
              ))}
            </div>
          ) : (
            <div className="mt-8 rounded-[1.7rem] border border-dashed border-hira-orange/18 bg-white/84 px-6 py-8 text-hira-ink/70 shadow-soft">
              Suggested HIRA products will appear here once they are linked from the CMS recipe editor.
            </div>
          )}
        </div>
      </Section>
    </>
  );
}
