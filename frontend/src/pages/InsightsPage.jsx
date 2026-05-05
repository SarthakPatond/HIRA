import { Link } from "react-router-dom";
import PageHero from "../components/PageHero";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";

const categories = [
  {
    title: "Healthy Snacking Habits",
    description:
      "Educational content around conscious snacking, portioning, and evolving Indian household preferences."
  },
  {
    title: "Traditional Indian Breakfasts",
    description:
      "Story-led recipes and heritage content around poha, sabudana, and kitchen rituals that still matter."
  },
  {
    title: "B2B Manufacturing Insights",
    description:
      "Articles tailored for stockists, repackers, retailers, and channel partners evaluating reliable supply partners."
  }
];

const articles = [
  {
    category: "Traditional Indian Breakfasts",
    title: "Why Ujjaini Poha Still Belongs in the Modern Indian Breakfast Story",
    summary:
      "A heritage-first article that connects regional trust with current eating habits and convenience-led routines."
  },
  {
    category: "Healthy Snacking Habits",
    title: "How Ready-to-Fry Snacks Can Feel Fresh, Light, and Custom at Home",
    summary:
      "An SEO-friendly topic aligned with your future product line and the promise of consumer-controlled preparation."
  },
  {
    category: "B2B Manufacturing Insights",
    title: "What Distributors Look for in a Staple and Snack Manufacturing Partner",
    summary:
      "A search-intent article that can attract wholesale leads while reinforcing credibility and operational discipline."
  }
];

export default function InsightsPage() {
  const description =
    "Explore Hira FMCG's insights architecture for healthy snacking, traditional Indian breakfasts, and B2B manufacturing content.";

  return (
    <div>
      <Seo
        title="Insights | Hira FMCG"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "Blog",
          name: "Hira FMCG Insights",
          description,
          url: "http://localhost:5173/insights"
        }}
      />

      <PageHero
        eyebrow="SEO Content Strategy"
        title="An insights hub built around the exact search intent your brand needs"
        description="This section lays the groundwork for organic traffic, education-led trust, and category authority across both B2C and B2B audiences."
        image="/brand-art/2.png"
      />

      <section className="section-pad">
        <div className="container-shell">
          <div className="grid gap-6 lg:grid-cols-3">
            {categories.map((category) => (
              <Reveal key={category.title}>
                <article className="rounded-[2rem] bg-white p-8 shadow-soft">
                  <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-orange">
                    Editorial Pillar
                  </p>
                  <h2 className="mt-4 font-display text-3xl text-hira-forest">
                    {category.title}
                  </h2>
                  <p className="mt-4 text-base leading-8 text-hira-ink/75">
                    {category.description}
                  </p>
                </article>
              </Reveal>
            ))}
          </div>

          <div className="mt-14">
            <div className="flex flex-wrap items-end justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                  Article Starters
                </p>
                <h2 className="mt-3 font-display text-4xl text-hira-forest">
                  Suggested launch content
                </h2>
              </div>
              <Link
                to="/contact"
                className="rounded-full bg-[#19130f] px-6 py-3 font-semibold text-[#f6e0a2]"
              >
                Talk to Brand Team
              </Link>
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-3">
              {articles.map((article) => (
                <Reveal key={article.title}>
                  <article className="rounded-[2rem] border border-[#d7c084]/[0.18] bg-[#140f0b] p-8 text-white shadow-soft">
                    <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-gold">
                      {article.category}
                    </p>
                    <h3 className="mt-4 font-display text-3xl">{article.title}</h3>
                    <p className="mt-4 text-base leading-8 text-white/75">
                      {article.summary}
                    </p>
                  </article>
                </Reveal>
              ))}
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
