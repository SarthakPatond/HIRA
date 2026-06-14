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
          url: "https://ujjainipoha.com/recipes"
        }}
      />

      <PageHero
        eyebrow="Recipes"
        title="Cook with HIRA"
        description="Story-led, pantry-friendly recipes designed around the flavours people actually come back to."
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

        {filteredRecipes.length ? (
          <div className="mt-10 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            {filteredRecipes.map((recipe) => (
              <Reveal key={recipe.slug}>
                <RecipeCard recipe={recipe} />
              </Reveal>
            ))}
          </div>
        ) : (
          <div className="mt-10 rounded-[2rem] border border-dashed border-hira-orange/18 bg-white/80 p-10 text-center text-hira-ink/70 shadow-soft">
            Recipes will appear here as soon as they are published from the CMS.
          </div>
        )}
      </Section>
    </div>
  );
}
