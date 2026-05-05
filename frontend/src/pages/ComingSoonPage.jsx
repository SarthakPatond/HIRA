import { Link } from "react-router-dom";
import ErrorState from "../components/ErrorState";
import LoadingState from "../components/LoadingState";
import PageHero from "../components/PageHero";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import { usePageContent } from "../hooks/usePageContent";

export default function ComingSoonPage() {
  const { content, loading, error } = usePageContent("home");

  if (loading) return <LoadingState label="Loading coming soon page" />;
  if (error) return <ErrorState message={error} />;

  const description =
    "Explore Hira's teaser page for upcoming healthy ready-to-fry snacks, innovation-led pantry staples, and future launches.";

  return (
    <div>
      <Seo
        title="Coming Soon | Hira FMCG"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "CollectionPage",
          name: "Hira FMCG Coming Soon",
          description,
          url: "http://localhost:5173/coming-soon"
        }}
      />

      <PageHero
        eyebrow="Launch Teasers"
        title="New pantry ideas are taking shape behind the curtain"
        description="This page is designed to build anticipation around innovation-led launches without revealing every detail too early."
        image="/brand-art/3.png"
      />

      <section className="section-pad">
        <div className="container-shell">
          <div className="mx-auto max-w-3xl text-center">
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
              Brand Narrative
            </p>
            <h2 className="mt-4 font-display text-4xl text-hira-forest sm:text-5xl">
              Tradition stays intact. The format evolves.
            </h2>
            <p className="mt-5 text-lg leading-8 text-hira-ink/75">
              Hira&apos;s upcoming range is being built around value addition,
              modern convenience, and home-finished eating experiences while
              keeping the emotional familiarity of Indian staples intact.
            </p>
          </div>

          <div className="mt-12 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            {content.coming_soon.items.map((item, index) => (
              <Reveal key={item.title}>
                <article className="overflow-hidden rounded-[2.2rem] border border-[#d7c084]/[0.18] bg-[#130f0c] shadow-soft">
                  <div className="relative">
                    <img
                      src={item.image}
                      alt={item.title}
                      className="h-80 w-full scale-[1.03] object-cover blur-[3px]"
                    />
                    <div className="absolute inset-0 bg-black/45" />
                    <div
                      className="absolute inset-0 opacity-30"
                      style={{
                        backgroundImage: `url('${index % 2 === 0 ? "/brand-art/1.png" : "/brand-art/4.png"}')`,
                        backgroundSize: "cover"
                      }}
                    />
                    <div className="absolute inset-x-5 bottom-5 rounded-[1.7rem] border border-[#d7c084]/[0.2] bg-white/[0.86] p-5">
                      <p className="text-xs font-semibold uppercase tracking-[0.22em] text-hira-orange">
                        Coming Soon
                      </p>
                      <h3 className="mt-3 font-display text-3xl text-hira-forest">
                        {item.title}
                      </h3>
                      <p className="mt-3 text-sm leading-7 text-hira-ink/75">
                        Details stay intentionally under wraps for now, but the
                        experience is being built for modern kitchens, curious
                        snackers, and faster-moving retail shelves.
                      </p>
                    </div>
                  </div>
                </article>
              </Reveal>
            ))}
          </div>

          <div className="mt-12 rounded-[2.5rem] border border-[#d7c084]/[0.18] bg-[#140f0b] p-8 text-white shadow-soft sm:p-10">
            <div className="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-gold">
                  Early Interest
                </p>
                <h3 className="mt-4 font-display text-4xl">
                  Want first access when the new line is ready?
                </h3>
                <p className="mt-4 text-lg leading-8 text-white/78">
                  If you are a stockist, distributor, or repacking partner, use
                  the B2B portal to register interest before the public reveal.
                </p>
              </div>
              <div className="flex flex-wrap items-center gap-4">
                <Link
                  to="/distributor"
                  className="rounded-full bg-hira-gold px-6 py-3 font-semibold text-hira-ink"
                >
                  Open B2B Portal
                </Link>
                <Link
                  to="/products"
                  className="rounded-full border border-white/20 bg-white/10 px-6 py-3 font-semibold text-white"
                >
                  View Live Products
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
