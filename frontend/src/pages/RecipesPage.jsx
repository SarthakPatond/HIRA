import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import ContentImage from "../components/ContentImage";
import ErrorState from "../components/ErrorState";
import LoadingState from "../components/LoadingState";
import PageHero from "../components/PageHero";
import RecipeCard from "../components/RecipeCard";
import Reveal from "../components/Reveal";
import Section from "../components/Section";
import Seo from "../components/Seo";
import { api } from "../lib/api";

function normalizeRecipe(recipe) {
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

export default function RecipesPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [recipes, setRecipes] = useState([]);
  const [activeCategory, setActiveCategory] = useState("All");

  const categories = useMemo(() => ["All", "Poha", "Sabudana", "Snacks"], []);

  useEffect(() => {
    let isMounted = true;

    async function load() {
      try {
        setLoading(true);
        setError(null);

        const response = await api.getRecipes();
        const normalized = Array.isArray(response)
          ? response.map(normalizeRecipe)
          : [];

        if (isMounted) {
          setRecipes(normalized);
        }
      } catch (exception) {
        if (isMounted) {
          setError(exception?.message || "Failed to load recipes.");
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
  }, []);

  const filteredRecipes = useMemo(() => {
    if (activeCategory === "All") {
      return recipes;
    }

    return recipes.filter((recipe) => recipe.category === activeCategory);
  }, [activeCategory, recipes]);

  const leadRecipe = filteredRecipes[0] || recipes[0] || null;
  const supportingRecipes = (filteredRecipes[0] ? filteredRecipes.slice(1) : recipes.slice(1)).slice(0, 6);

  if (loading) return <LoadingState label="Loading recipes" />;
  if (error) return <ErrorState message={error} />;

  const description =
    "Discover premium HIRA recipes for Poha, Sabudana, and snack-time favorites, all built around trusted pantry staples.";

  return (
    <div>
      <Seo
        title="Recipes | Hira FMCG"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "CollectionPage",
          name: "Hira FMCG Recipes",
          description,
          url: "http://localhost:5173/recipes"
        }}
      />

      <PageHero
        eyebrow="Recipes"
        title="Cook with HIRA"
        description="Story-led, pantry-friendly recipes designed around the flavors people actually come back to."
        image="https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=1470&q=80"
      />

      <Section className="pt-4">
        <div className="rounded-[2rem] border border-hira-orange/10 bg-white/90 p-4 shadow-soft backdrop-blur sm:p-6">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                Browse by category
              </p>
              <h2 className="mt-3 font-display text-4xl text-hira-forest">
                Recipes that feel premium, not generic
              </h2>
            </div>
            <div className="rounded-full bg-hira-cream/60 px-5 py-3 text-sm font-semibold text-hira-ink">
              {filteredRecipes.length} recipes
            </div>
          </div>

          <div className="mt-6 hide-scrollbar flex gap-3 overflow-x-auto">
            {categories.map((category) => (
              <button
                key={category}
                type="button"
                onClick={() => setActiveCategory(category)}
                className={`whitespace-nowrap rounded-full px-5 py-3 text-sm font-semibold transition ${
                  activeCategory === category
                    ? "bg-hira-orange text-white shadow-soft"
                    : "border border-hira-orange/12 bg-white text-hira-ink hover:border-hira-orange/35 hover:text-hira-orange"
                }`}
              >
                {category}
              </button>
            ))}
          </div>
        </div>

        {leadRecipe ? (
          <div className="mt-10 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <Reveal>
              <article className="group relative overflow-hidden rounded-[2.2rem] border border-hira-orange/12 bg-white shadow-soft">
                <ContentImage
                  src={leadRecipe.heroImage || leadRecipe.image}
                  alt={leadRecipe.name}
                  className="h-[540px] w-full object-cover transition duration-500 group-hover:scale-105"
                  fallbacks={[
                    "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=80"
                  ]}
                />
                <div className="absolute inset-0 bg-gradient-to-r from-black/78 via-black/38 to-transparent" />
                <div className="absolute inset-x-8 bottom-8 top-8 flex max-w-2xl flex-col justify-end text-white">
                  <div className="flex flex-wrap gap-3">
                    <span className="rounded-full bg-white/16 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-hira-wheat backdrop-blur">
                      {leadRecipe.category}
                    </span>
                    <span className="rounded-full bg-white/16 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-white backdrop-blur">
                      {leadRecipe.cookingTimeMinutes} min
                    </span>
                    <span className="rounded-full bg-white/16 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-white backdrop-blur">
                      Serves {leadRecipe.servings}
                    </span>
                  </div>

                  <p className="mt-8 text-xs font-semibold uppercase tracking-[0.3em] text-hira-wheat">
                    Featured Recipe
                  </p>
                  <h3 className="mt-4 font-display text-5xl leading-none sm:text-6xl">
                    {leadRecipe.name}
                  </h3>
                  <p className="mt-5 max-w-xl text-lg leading-8 text-white/84">
                    {leadRecipe.shortDescription}
                  </p>

                  <div className="mt-8 flex flex-wrap gap-4">
                    <Link
                      to={`/recipes/${leadRecipe.slug}`}
                      className="rounded-full bg-white px-7 py-4 text-sm font-semibold text-hira-red transition hover:scale-[1.02]"
                    >
                      View full recipe
                    </Link>
                    <Link
                      to="/products"
                      className="rounded-full border border-white/24 bg-white/10 px-7 py-4 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/16"
                    >
                      Explore HIRA products
                    </Link>
                  </div>
                </div>
              </article>
            </Reveal>

            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-1">
              {supportingRecipes.slice(0, 3).map((recipe) => (
                <Reveal key={recipe.slug}>
                  <RecipeCard recipe={recipe} />
                </Reveal>
              ))}
            </div>
          </div>
        ) : (
          <div className="mt-10 rounded-[2rem] border border-dashed border-hira-orange/18 bg-white/80 p-10 text-center text-hira-ink/70 shadow-soft">
            Recipes will appear here as soon as they are published from the CMS.
          </div>
        )}

        {supportingRecipes.length > 3 ? (
          <div className="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            {supportingRecipes.slice(3).map((recipe) => (
              <Reveal key={recipe.slug}>
                <RecipeCard recipe={recipe} />
              </Reveal>
            ))}
          </div>
        ) : null}
      </Section>
    </div>
  );
}
