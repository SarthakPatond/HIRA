import { Link } from "react-router-dom";

export default function NotFoundPage() {
  return (
    <section className="section-pad">
      <div className="container-shell">
        <div className="rounded-[2.5rem] bg-white p-10 text-center shadow-soft">
          <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
            404
          </p>
          <h1 className="mt-4 font-display text-5xl text-hira-forest">
            Page not found
          </h1>
          <p className="mx-auto mt-5 max-w-2xl text-lg leading-8 text-hira-ink/75">
            The page you are looking for does not exist or may have been moved.
          </p>
          <Link
            to="/"
            className="mt-8 inline-flex rounded-full bg-hira-forest px-6 py-3 font-semibold text-white"
          >
            Back Home
          </Link>
        </div>
      </div>
    </section>
  );
}
