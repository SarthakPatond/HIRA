import { Link } from "react-router-dom";
import { usePageContent } from "../hooks/usePageContent";
import Seo from "./Seo";
import Container from "./Container";

export default function Footer() {
  const { content } = usePageContent("contact");

  return (
    <>
      <Seo
        schema={{
          "@context": "https://schema.org",
          "@type": "LocalBusiness",
          name: "Hira FMCG",
          address: content?.address || "Ujjain, Madhya Pradesh, India",
          telephone: content?.phone || "+91 98765 43210",
          email: content?.email || "hello@hirafmcg.com"
        }}
      />
      <footer className="mt-16 border-t border-hira-orange/10 bg-white">
        <Container className="grid gap-10 py-12 md:grid-cols-[1.2fr_1fr_1fr]">
          <div>
            <p className="text-xs uppercase tracking-[0.26em] text-hira-orange">
              Hira FMCG
            </p>
            <h3 className="mt-3 font-display text-4xl text-hira-ink">
              Traditional roots. Modern food habits.
            </h3>
            <p className="mt-4 max-w-xl text-sm leading-7 text-hira-ink/70">
              Pure, authentic, and quality food products crafted with tradition
              and innovation for retailers, families, and distributors.
            </p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-[0.26em] text-hira-orange">
              Quick Links
            </p>
            <div className="mt-4 grid gap-3 text-sm text-hira-ink/75">
              <Link className="transition hover:text-hira-orange" to="/">
                Home
              </Link>
              <Link className="transition hover:text-hira-orange" to="/products">
                Products
              </Link>
              <Link className="transition hover:text-hira-orange" to="/about">
                About
              </Link>
              <Link className="transition hover:text-hira-orange" to="/contact">
                Contact
              </Link>
            </div>
          </div>
          <div>
            <p className="text-xs uppercase tracking-[0.26em] text-hira-orange">
              Contact
            </p>
            <div className="mt-4 grid gap-3 text-sm leading-7 text-hira-ink/75">
              <p>{content?.address || "Ujjain, Madhya Pradesh, India"}</p>
              <p>{content?.phone || "+91 98765 43210"}</p>
              <p>{content?.email || "hello@hirafmcg.com"}</p>
            </div>
          </div>
        </Container>
        <div className="border-t border-hira-orange/10">
          <Container className="py-4 text-sm text-hira-ink/60">
            Copyright &copy; Hira FMCG. All rights reserved.
          </Container>
        </div>
      </footer>
    </>
  );
}
