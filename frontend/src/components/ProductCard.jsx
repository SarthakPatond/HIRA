import { Link } from "react-router-dom";
import ContentImage from "./ContentImage";

export default function ProductCard({ product }) {
  return (
    <Link
      to={`/products/${product.slug}`}
      className="group flex h-full flex-col overflow-hidden rounded-2xl border border-hira-orange/10 bg-white shadow-soft transition-transform duration-300 hover:-translate-y-1"
    >
      <div className="relative overflow-hidden rounded-2xl">
        <ContentImage
          src={product.image}
          alt={product.name}
          className="h-64 w-full object-cover transition-transform duration-300 group-hover:scale-105"
          fallbacks={[
            "https://images.unsplash.com/photo-1506368249639-73a05d6f6488?auto=format&fit=crop&w=900&q=80"
          ]}
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/72 via-transparent to-transparent opacity-90" />
        <div className="absolute bottom-5 left-5 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-hira-orange shadow-sm backdrop-blur">
          {product.category}
        </div>
      </div>
      <div className="flex flex-1 flex-col p-6">
        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-hira-forest">
          Pantry Essential
        </p>
        <h3 className="mt-3 font-display text-3xl leading-tight text-hira-ink">
          {product.name}
        </h3>
        <p className="mt-3 text-sm leading-7 text-hira-ink/68">
          {product.description}
        </p>
      </div>
    </Link>
  );
}
