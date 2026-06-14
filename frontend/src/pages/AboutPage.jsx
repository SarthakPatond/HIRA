import Seo from "../components/Seo";
import ErrorState from "../components/ErrorState";
import LoadingState from "../components/LoadingState";
import PageHero from "../components/PageHero";
import Reveal from "../components/Reveal";
import SectionHeading from "../components/SectionHeading";
import { usePageContent } from "../hooks/usePageContent";

export default function AboutPage() {
  const { content, loading, error } = usePageContent("about");

  if (loading) return <LoadingState label="Loading about page" />;
  if (error) return <ErrorState message={error} />;

  const description =
    "Discover Hira FMCG's journey from generational roots and Ujjain expertise to a modern food brand bringing Ujjaini Poha to wider markets.";

  return (
    <div>
      <Seo
        title="About | Hira FMCG"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "AboutPage",
          name: "About Hira FMCG",
          description,
          url: "https://ujjainipoha.com/about"
        }}
      />

      <PageHero
        eyebrow="About Hira"
        title={content.hero_title}
        description={content.hero_text}
        image={content.hero_image}
      />

      <section className="section-pad pt-8">
        <div className="container-shell">
          <div className="grid gap-8 rounded-[2.8rem] border border-hira-orange/15 bg-[#F8EBDD] p-8 text-hira-ink shadow-soft lg:grid-cols-[0.95fr_1.05fr] lg:p-10">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-gold">
                Founder Storyline
              </p>
              <h2 className="mt-4 font-display text-4xl sm:text-5xl">
                From the Y2K dream of trying life in the USA to putting Ujjaini
                Poha on the global food map
              </h2>
            </div>
            <div className="grid gap-4 text-base leading-8 text-hira-ink/75">
              <p>
                The emotional core of Hira is not just product expansion. It is
                a return journey from ambition shaped by global exposure back to
                the food memory, trust, and credibility of Ujjain.
              </p>
              <p>
                The brand exists to keep the traditional essence alive while
                adapting it to today&apos;s eating habits, packaging expectations,
                and food technologies.
              </p>
              <p>
                That is the through-line of the site: generational roots,
                regional pride, and thoughtful value addition for modern kitchens.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="section-pad">
        <div className="container-shell">
          <SectionHeading
            eyebrow="Core Story"
            title="Why the brand exists"
            description="The about page is fully CMS-controlled so your story can evolve as the business grows."
          />
          <div className="mt-12 grid gap-6 lg:grid-cols-2">
            {content.sections.map((section) => (
              <Reveal key={section.title}>
                <article className="rounded-[2rem] bg-white p-8 shadow-soft">
                  <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-orange">
                    {section.title}
                  </p>
                  <p className="mt-4 text-lg leading-8 text-hira-ink/75">
                    {section.text}
                  </p>
                </article>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section className="section-pad bg-white/60">
        <div className="container-shell">
          <SectionHeading
            eyebrow="Timeline"
            title="A storytelling layout built for brand credibility"
            description="Use the admin panel to change milestones, years, and narrative as the company expands."
            align="center"
          />
          <div className="mx-auto mt-12 max-w-5xl">
            <div className="grid gap-8">
              {content.timeline.map((item, index) => (
                <Reveal key={`${item.year}-${item.title}`}>
                  <div className="grid gap-6 rounded-[2rem] bg-white p-8 shadow-soft md:grid-cols-[140px_1fr]">
                    <div className="text-center md:text-left">
                      <div className="inline-flex rounded-full bg-hira-forest px-5 py-3 font-semibold text-white">
                        {item.year}
                      </div>
                    </div>
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-orange">
                        Chapter {index + 1}
                      </p>
                      <h3 className="mt-3 font-display text-3xl text-hira-forest">
                        {item.title}
                      </h3>
                      <p className="mt-4 text-lg leading-8 text-hira-ink/75">
                        {item.text}
                      </p>
                    </div>
                  </div>
                </Reveal>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="section-pad pt-0">
        <div className="container-shell">
          <div className="rounded-[2.5rem] border border-[#d7c084]/[0.18] bg-[#fff7de] p-8 shadow-soft sm:p-10">
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
              Regional Credibility
            </p>
            <h2 className="mt-4 font-display text-4xl text-hira-forest">
              Ujjain is not just a location. It is a trust signal.
            </h2>
            <p className="mt-5 max-w-4xl text-lg leading-8 text-hira-ink/75">
              The site now has a clearer angle for positioning your origin:
              Ujjain as a traditional poha hub, backed by manufacturing know-how
              and a familiar product culture. That gives the brand a stronger
              geographic identity instead of feeling like a generic FMCG catalog.
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}
