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
      className="group flex h-full flex-col overflow-hidden rounded-[2rem] border border-hira-orange/10 bg-white shadow-soft transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_28px_80px_-42px_rgba(77,39,11,0.45)]"
    >
      <div className="relative overflow-hidden">
        <ContentImage
          src={recipe.image}
          alt={recipe.name}
          className="h-64 w-full object-cover transition-transform duration-500 group-hover:scale-110"
          fallbacks={[
            "https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=80"
          ]}
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />

        <div className="absolute left-5 top-5 flex flex-wrap gap-2">
          <span className="rounded-full bg-white/90 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.22em] text-hira-orange shadow-sm backdrop-blur">
            {recipe.category}
          </span>
          <span
            className={[
              "rounded-full border bg-white/90 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] backdrop-blur",
              difficultyToBadgeStyles(recipe.difficulty)
            ].join(" ")}
          >
            {recipe.difficulty}
          </span>
        </div>

        <div className="absolute bottom-5 left-5 right-5 flex items-end justify-between gap-4">
          <div className="rounded-[1.25rem] bg-white/92 px-4 py-3 shadow-soft backdrop-blur">
            <p className="text-[11px] font-semibold uppercase tracking-[0.24em] text-hira-orange/80">
              Cook time
            </p>
            <p className="mt-1 text-lg font-bold text-hira-ink">
              {recipe.cookingTimeMinutes || 0} min
            </p>
          </div>
          <p className="translate-y-3 text-xs font-semibold uppercase tracking-[0.26em] text-white opacity-0 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100">
            View recipe →
          </p>
        </div>
      </div>

      <div className="flex flex-1 flex-col p-6">
        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-forest">
          HIRA Kitchen Story
        </p>
        <h3 className="mt-3 font-display text-[2rem] leading-tight text-hira-ink">
          {recipe.name}
        </h3>

        <p className="mt-3 text-sm leading-7 text-hira-ink/68">
          {recipe.shortDescription}
        </p>

        <div className="mt-auto pt-5">
          <div className="grid grid-cols-3 gap-3 rounded-[1.4rem] bg-hira-cream/45 p-4">
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-hira-orange/80">
                Time
              </p>
              <p className="mt-1 text-sm font-bold text-hira-ink">
                {recipe.cookingTimeMinutes || 0} min
              </p>
            </div>
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-hira-orange/80">
                Level
              </p>
              <p className="mt-1 text-sm font-bold text-hira-ink">
                {recipe.difficulty || "Easy"}
              </p>
            </div>
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-hira-orange/80">
                Servings
              </p>
              <p className="mt-1 text-sm font-bold text-hira-ink">
                {recipe.servings || "2"}
              </p>
            </div>
          </div>
          <p className="mt-4 text-xs font-semibold uppercase tracking-[0.22em] text-hira-orange/80 transition group-hover:text-hira-orange">
            Explore the full recipe →
          </p>
        </div>
      </div>
    </Link>
  );
}
