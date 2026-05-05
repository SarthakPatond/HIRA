import { useEffect, useState } from "react";
import { Link } from "react-router-dom";

function themeClasses(theme) {
  if (theme === "light") {
    return {
      shell: "text-hira-ink",
      overlay:
        "bg-gradient-to-r from-white/[0.92] via-white/[0.76] to-white/[0.55]",
      panel:
        "border border-[#d9b45c]/[0.35] bg-white/70 text-hira-ink shadow-soft",
      eyebrow: "text-[#9f6c00]",
      title: "text-hira-ink",
      body: "text-hira-ink/75",
      secondary:
        "rounded-full border border-hira-ink/10 bg-hira-ink text-white hover:bg-hira-forest",
      tertiary:
        "rounded-full border border-hira-ink/15 bg-white/80 text-hira-ink hover:bg-white"
    };
  }

  return {
    shell: "text-white",
    overlay:
      "bg-gradient-to-r from-black/[0.88] via-black/[0.72] to-black/[0.4]",
    panel:
      "border border-[#d9b45c]/[0.25] bg-black/[0.35] text-white shadow-soft backdrop-blur-md",
    eyebrow: "text-hira-gold",
    title: "text-white",
    body: "text-white/75",
    secondary:
      "rounded-full bg-hira-gold text-hira-ink hover:bg-[#f7cf63]",
    tertiary:
      "rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/15"
  };
}

export default function HomeCarousel({ hero, story, categories, cta }) {
  const slides = [
    {
      id: "legacy",
      image: "/brand-art/1.png",
      theme: "dark",
      eyebrow: hero?.eyebrow || "Hira FMCG",
      title: hero?.title || "From Ujjain's Legacy to Modern Kitchens",
      subtitle:
        hero?.subtitle ||
        "A premium staple and snacks brand shaped for modern retail storytelling.",
      primaryLabel: hero?.cta_label || "Explore Products",
      primaryLink: hero?.cta_link || "/products",
      secondaryLabel: hero?.secondary_label || "Become a Distributor",
      secondaryLink: hero?.secondary_link || "/distributor",
      highlights: [
        "Shelf-forward packaging",
        "Pantry-first categories",
        "Emotion-led branding"
      ]
    },
    {
      id: "journey",
      image: "/brand-art/2.png",
      theme: "light",
      eyebrow: "The Journey",
      title:
        "A brand story that moves from global exposure back to rooted Indian trust",
      subtitle:
        story?.intro ||
        "Built from observation, memory, and manufacturing ambition.",
      primaryLabel: "Read Our Story",
      primaryLink: "/about",
      secondaryLabel: "See Portfolio",
      secondaryLink: "/products",
      highlights: ["USA perspective", "Ujjain foundation", "Manufacturing focus"]
    },
    {
      id: "portfolio",
      image: "/brand-art/3.png",
      theme: "dark",
      eyebrow: "Signature Portfolio",
      title:
        "Poha, sabudana, parmal and snacks designed for family familiarity and shelf recall",
      subtitle:
        "A clean, versatile range that feels local in spirit and modern in presentation.",
      primaryLabel: "Browse Categories",
      primaryLink: "/products",
      secondaryLabel: "Partner With Us",
      secondaryLink: "/distributor",
      highlights: categories?.slice(0, 4).map((item) => item.name) || []
    },
    {
      id: "network",
      image: "/brand-art/4.png",
      theme: "light",
      eyebrow: "Distributor Network",
      title:
        cta?.title || "Looking to grow distribution in your market?",
      subtitle:
        cta?.text || "Let's build a stronger FMCG footprint together.",
      primaryLabel: cta?.button_label || "Talk to Our Team",
      primaryLink: cta?.button_link || "/distributor",
      secondaryLabel: "Contact Us",
      secondaryLink: "/contact",
      highlights: [
        "Retail-ready positioning",
        "Fast inquiry capture",
        "Scalable category roadmap"
      ]
    }
  ];

  const [activeIndex, setActiveIndex] = useState(0);

  useEffect(() => {
    const interval = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % slides.length);
    }, 5000);

    return () => window.clearInterval(interval);
  }, [slides.length]);

  return (
    <section className="relative overflow-hidden px-3 pt-3 sm:px-4 sm:pt-4 lg:px-6 lg:pt-6">
      <div className="relative mx-auto max-w-[1550px] overflow-hidden rounded-[2rem] sm:rounded-[2.8rem]">
        {slides.map((slide, index) => {
          const theme = themeClasses(slide.theme);
          const isActive = activeIndex === index;

          return (
            <article
              key={slide.id}
              className={`absolute inset-0 transition-all duration-700 ${
                isActive
                  ? "pointer-events-auto translate-x-0 opacity-100"
                  : "pointer-events-none translate-x-8 opacity-0"
              }`}
            >
              <div className="relative min-h-[86vh] overflow-hidden sm:min-h-[92vh] lg:min-h-[860px]">
                <img
                  src={slide.image}
                  alt={slide.title}
                  className="absolute inset-0 h-full w-full object-cover"
                />
                <div className={`absolute inset-0 ${theme.overlay}`} />

                <div className="relative z-10 flex min-h-[86vh] items-end sm:min-h-[92vh] lg:min-h-[860px]">
                  <div className="container-shell pb-10 pt-28 sm:pb-12 sm:pt-32 lg:pb-16 lg:pt-36">
                    <div className="grid items-end gap-6 lg:grid-cols-[1.12fr_0.88fr] lg:gap-10">
                      <div className={theme.shell}>
                        <div
                          className={`max-w-4xl rounded-[2rem] p-6 sm:p-8 lg:p-10 ${theme.panel}`}
                        >
                          <p
                            className={`text-xs font-semibold uppercase tracking-[0.34em] ${theme.eyebrow}`}
                          >
                            {slide.eyebrow}
                          </p>
                          <h1
                            className={`mt-4 text-balance font-display text-[2.5rem] leading-[0.95] sm:text-[4rem] lg:text-[5.5rem] ${theme.title}`}
                          >
                            {slide.title}
                          </h1>
                          <p
                            className={`mt-5 max-w-2xl text-base leading-7 sm:text-lg sm:leading-8 ${theme.body}`}
                          >
                            {slide.subtitle}
                          </p>

                          <div className="mt-7 flex flex-wrap gap-3">
                            <Link
                              to={slide.primaryLink}
                              className={`px-6 py-3 text-sm font-semibold transition sm:text-base ${theme.secondary}`}
                            >
                              {slide.primaryLabel}
                            </Link>
                            <Link
                              to={slide.secondaryLink}
                              className={`px-6 py-3 text-sm font-semibold transition sm:text-base ${theme.tertiary}`}
                            >
                              {slide.secondaryLabel}
                            </Link>
                          </div>

                          <div className="mt-8 grid gap-3 sm:grid-cols-3">
                            {slide.highlights.map((item) => (
                              <div
                                key={item}
                                className={`rounded-[1.4rem] border px-4 py-4 text-sm font-medium ${
                                  slide.theme === "light"
                                    ? "border-[#d9b45c]/[0.25] bg-[#fff7df]/75 text-hira-ink/80"
                                    : "border-white/10 bg-white/[0.06] text-white/75"
                                }`}
                              >
                                {item}
                              </div>
                            ))}
                          </div>
                        </div>
                      </div>

                      <div className="hidden lg:block">
                        <div
                          className={`ml-auto max-w-md rounded-[2rem] p-5 ${theme.panel}`}
                        >
                          <p
                            className={`text-xs font-semibold uppercase tracking-[0.3em] ${theme.eyebrow}`}
                          >
                            Brand Moodboard
                          </p>
                          <div className="mt-4 grid gap-3">
                            {slides.map((thumb, thumbIndex) => {
                              const activeThumb = thumbIndex === activeIndex;
                              return (
                                <button
                                  key={thumb.id}
                                  type="button"
                                  onClick={() => setActiveIndex(thumbIndex)}
                                  className={`flex items-center gap-4 rounded-[1.4rem] border p-3 text-left transition ${
                                    activeThumb
                                      ? slide.theme === "light"
                                        ? "border-[#b98d2f] bg-[#fff4cf]"
                                        : "border-hira-gold/[0.35] bg-white/10"
                                      : slide.theme === "light"
                                        ? "border-hira-ink/10 bg-white/50 hover:bg-white/80"
                                        : "border-white/10 bg-black/20 hover:bg-black/[0.35]"
                                  }`}
                                >
                                  <img
                                    src={thumb.image}
                                    alt={thumb.title}
                                    className="h-16 w-20 rounded-2xl object-cover"
                                  />
                                  <div>
                                    <p className="text-xs uppercase tracking-[0.24em] opacity-70">
                                      0{thumbIndex + 1}
                                    </p>
                                    <p className="mt-1 text-sm font-semibold leading-6">
                                      {thumb.eyebrow}
                                    </p>
                                  </div>
                                </button>
                              );
                            })}
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="mt-6 flex items-center justify-between gap-4">
                      <div className="flex gap-2">
                        {slides.map((slideDot, dotIndex) => (
                          <button
                            key={slideDot.id}
                            type="button"
                            aria-label={`Go to ${slideDot.eyebrow}`}
                            onClick={() => setActiveIndex(dotIndex)}
                            className={`h-2.5 rounded-full transition-all ${
                              activeIndex === dotIndex
                                ? "w-12 bg-hira-gold"
                                : "w-2.5 bg-white/40"
                            }`}
                          />
                        ))}
                      </div>

                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() =>
                            setActiveIndex((current) =>
                              current === 0 ? slides.length - 1 : current - 1
                            )
                          }
                          className="grid h-11 w-11 place-items-center rounded-full border border-white/[0.18] bg-black/25 text-white backdrop-blur transition hover:bg-black/40"
                          aria-label="Previous slide"
                        >
                          &lt;
                        </button>
                        <button
                          type="button"
                          onClick={() =>
                            setActiveIndex((current) => (current + 1) % slides.length)
                          }
                          className="grid h-11 w-11 place-items-center rounded-full border border-white/[0.18] bg-black/25 text-white backdrop-blur transition hover:bg-black/40"
                          aria-label="Next slide"
                        >
                          &gt;
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          );
        })}

        <div className="pointer-events-none relative min-h-[86vh] opacity-0 sm:min-h-[92vh] lg:min-h-[860px]" />
      </div>
    </section>
  );
}
