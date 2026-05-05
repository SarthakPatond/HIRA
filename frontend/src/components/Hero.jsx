import { Link } from "react-router-dom";

export default function Hero({ hero }) {
  const isVideo = hero?.media_type === "video";

  return (
    <section className="relative overflow-hidden">
      <div className="absolute inset-0 bg-gradient-to-r from-hira-forest/85 via-hira-forest/70 to-hira-red/55" />
      {isVideo ? (
        <video
          className="h-[88vh] w-full object-cover"
          src={hero?.media_url}
          autoPlay
          muted
          loop
          playsInline
        />
      ) : (
        <img
          className="h-[88vh] w-full object-cover"
          src={hero?.media_url}
          alt={hero?.title}
        />
      )}

      <div className="absolute inset-0">
        <div className="container-shell flex h-full items-center">
          <div className="max-w-3xl rounded-[2rem] border border-white/15 bg-white/10 p-8 text-white shadow-soft backdrop-blur sm:p-12">
            <p className="text-xs font-semibold uppercase tracking-[0.32em] text-hira-wheat">
              {hero?.eyebrow}
            </p>
            <h1 className="mt-5 text-balance font-display text-5xl leading-tight sm:text-6xl lg:text-7xl">
              {hero?.title}
            </h1>
            <p className="mt-6 max-w-2xl text-lg leading-8 text-white/80">
              {hero?.subtitle}
            </p>
            <div className="mt-8 flex flex-wrap gap-4">
              <Link
                to={hero?.cta_link || "/products"}
                className="rounded-full bg-hira-gold px-6 py-3 font-semibold text-hira-ink transition hover:scale-[1.02]"
              >
                {hero?.cta_label || "Explore Products"}
              </Link>
              <Link
                to={hero?.secondary_link || "/distributor"}
                className="rounded-full border border-white/35 bg-white/10 px-6 py-3 font-semibold text-white transition hover:bg-white/20"
              >
                {hero?.secondary_label || "Become a Distributor"}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
