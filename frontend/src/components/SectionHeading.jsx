export default function SectionHeading({
  eyebrow,
  title,
  description,
  align = "left"
}) {
  const alignment =
    align === "center"
      ? "mx-auto max-w-3xl text-center"
      : "max-w-3xl text-left";

  return (
    <div className={alignment}>
      {eyebrow ? (
        <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
          {eyebrow}
        </p>
      ) : null}
      <h2 className="mt-4 text-balance font-display text-4xl leading-tight text-hira-ink sm:text-5xl">
        {title}
      </h2>
      {description ? (
        <p className="mt-4 text-base leading-8 text-hira-ink/70 sm:text-lg">
          {description}
        </p>
      ) : null}
    </div>
  );
}
