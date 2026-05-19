import { Link } from "react-router-dom";
import ContentImage from "./ContentImage";

function difficultyToBadgeStyles(difficulty) {
  switch ((difficulty || "").toLowerCase()) {
    case "easy":
      return "border-green-200 bg-green-50 text-green-800";
    case "medium":
      return "border-yellow-200 bg-yellow-50 text-yellow-800";
    case "hard":
      return "border-red-200 bg-red-50 text-red-800";
    default:
      return "border-hira-orange/20 bg-hira-cream text-hira-ink";
  }
}

export default function RecipeCard({ recipe }) {
  return (
    <Link
      to={`/recipes/${recipe.slug}`}
      className="flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-sm transition hover:shadow-soft"
    >
      <ContentImage
        src={recipe.image}
        alt={recipe.name}
        className="h-52 w-full object-cover"
        fallbacks={[
          "https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=80"
        ]}
      />

      <div className="flex flex-1 flex-col p-5">
        <h3 className="text-[1.25rem] font-display leading-tight text-hira-ink">{recipe.name}</h3>

        <p className="mt-3 text-sm leading-7 text-hira-ink/68">{recipe.shortDescription}</p>

        <div className="mt-5">
          <div className="grid grid-cols-3 gap-3 rounded-[1.4rem] bg-hira-cream/45 p-4">
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-hira-orange/80">
                Time
              </p>
              <p className="mt-1 text-sm font-bold text-hira-ink">{recipe.cookingTimeMinutes || 0} min</p>
            </div>
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-hira-orange/80">
                Difficulty
              </p>
              <p className="mt-1 text-sm font-bold text-hira-ink">{recipe.difficulty || "Easy"}</p>
            </div>
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-hira-orange/80">
                Servings
              </p>
              <p className="mt-1 text-sm font-bold text-hira-ink">{recipe.servings || "2"}</p>
            </div>
          </div>
        </div>

        <div className="mt-auto pt-5">
          <button
            type="button"
            className="w-full rounded-full bg-hira-orange px-6 py-3 text-sm font-semibold text-white"
          >
            View Recipe
          </button>
        </div>
      </div>
    </Link>
  );
}
