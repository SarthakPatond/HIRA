import ContentImage from "./ContentImage";
import Container from "./Container";

export default function PageHero({ eyebrow, title, description, image }) {
  return (
    <section className="relative overflow-hidden pb-20 pt-32 md:pt-36">
      <div className="absolute inset-0 bg-hira-cream" />
      <Container className="relative">
        <div className="grid items-center gap-8 rounded-[2rem] border border-hira-orange/10 bg-white p-8 shadow-soft lg:grid-cols-[1.05fr_0.95fr] lg:p-12">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
              {eyebrow}
            </p>
            <h1 className="mt-4 text-balance font-display text-5xl leading-tight text-hira-ink sm:text-6xl">
              {title}
            </h1>
            <p className="mt-5 max-w-2xl text-lg leading-8 text-hira-ink/75">
              {description}
            </p>
          </div>
          <div className="overflow-hidden rounded-2xl">
            <ContentImage
              src={image}
              alt={title}
              className="h-full min-h-[320px] w-full object-cover"
              fallbacks={[
                "https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=1200&q=80"
              ]}
            />
          </div>
        </div>
      </Container>
    </section>
  );
}
