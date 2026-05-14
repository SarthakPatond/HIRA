import { Link } from "react-router-dom";
import ContentImage from "./ContentImage";
import Container from "./Container";

function HeroAction({ href, children, className }) {
  if ((href || "").startsWith("#")) {
    return (
      <a href={href} className={className}>
        {children}
      </a>
    );
  }

  return (
    <Link to={href || "/products"} className={className}>
      {children}
    </Link>
  );
}

const heroFallbacks = [
  "https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=1600&q=80",
  "https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1600&q=80"
];

export default function HomeHero({ hero, story, categories = [] }) {
  const isVideo = hero?.media_type === "video";
  const featureImage =
    categories[0]?.image ||
    story?.image ||
    "https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=80";

  return (
    <section className="relative flex min-h-screen items-center overflow-hidden">
      {isVideo ? (
        <video
          className="absolute inset-0 h-full w-full object-cover"
          src={hero?.media_url}
          autoPlay
          muted
          loop
          playsInline
        />
      ) : (
        <ContentImage
          className="absolute inset-0 h-full w-full object-cover"
          src={hero?.media_url}
          alt={hero?.title}
          fallbacks={heroFallbacks}
        />
      )}

      <div className="absolute inset-0 bg-gradient-to-r from-hira-ink/20 via-hira-ink/10 to-hira-orange/18" />
      <div className="absolute inset-0 bg-gradient-to-b from-black/10 via-black/6 to-black/22" />
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(244,162,97,0.20),transparent_26%),radial-gradient(circle_at_bottom_left,rgba(217,119,69,0.18),transparent_30%)]" />

      <Container className="relative z-10 grid min-h-screen items-center gap-14 py-28 lg:grid-cols-[0.95fr_1.05fr]">
        <div className="max-w-3xl animate-fadeUp text-hira-forest">
          <p className="text-xs font-semibold uppercase tracking-[0.34em] text-hira-forest/70">
            {hero?.eyebrow || "A pantry story from Ujjain"}
          </p>
          <h1 className="mt-6 text-balance font-display text-5xl leading-[0.94] sm:text-6xl lg:text-[5.2rem]">
            {hero?.title || "Some traditions don't change. They evolve."}
          </h1>
          <p className="mt-6 max-w-2xl text-lg leading-8 text-hira-ink/80 sm:text-xl">
            {hero?.subtitle || "From Ujjain to the world."}
          </p>
          <div className="mt-10 flex flex-col items-start gap-4 sm:flex-row">
            <HeroAction
              href={hero?.cta_link}
              className="rounded-full bg-hira-orange px-7 py-4 text-sm font-semibold text-white transition hover:scale-[1.02] hover:bg-hira-red sm:text-base"
            >
              {hero?.cta_label || "Discover the Story"}
            </HeroAction>
            <HeroAction
              href={hero?.secondary_link}
              className="rounded-full border border-hira-orange/15 bg-white/65 px-7 py-4 text-sm font-semibold text-hira-forest transition hover:bg-white/85 sm:text-base"
            >
              {hero?.secondary_label || "Become Distributor"}
            </HeroAction>
          </div>
        </div>

        <div className="hidden justify-end lg:flex">
          <div className="relative w-full max-w-[34rem]">
            <div className="rounded-[2rem] border border-white/16 bg-white/10 p-4 shadow-soft backdrop-blur-md">
              <ContentImage
                src={featureImage}
                alt={categories[0]?.name || hero?.title}
                className="h-[480px] w-full rounded-2xl object-cover"
                fallbacks={heroFallbacks}
              />
            </div>
            <div className="absolute bottom-8 left-8 max-w-sm rounded-[1.75rem] border border-hira-orange/15 bg-[#FFF8F2]/92 p-6 text-hira-ink shadow-soft backdrop-blur-md">
              <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
                Brand Note
              </p>
              <p className="mt-3 font-display text-3xl leading-tight text-hira-forest">
                Emotion first. Product second.
              </p>
              <p className="mt-3 text-sm leading-7 text-hira-ink/75">
                A calmer, more premium storytelling layout that lets the brand
                breathe before the product details begin.
              </p>
            </div>
          </div>
        </div>

        <a
          href="#home-story"
          className="absolute bottom-8 left-1/2 flex -translate-x-1/2 flex-col items-center gap-3 text-xs font-semibold uppercase tracking-[0.28em] text-hira-ink/70 transition hover:text-hira-ink"
        >
          <span>Scroll</span>
          <span className="hero-scroll-line" />
          <span className="h-2 w-2 animate-pulseSoft rounded-full bg-hira-wheat" />
        </a>
      </Container>
    </section>
  );
}
