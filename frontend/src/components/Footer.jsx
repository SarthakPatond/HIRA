import { Link } from "react-router-dom";
import {
  FaEnvelope,
  FaFacebookF,
  FaInstagram,
  FaWhatsapp
} from "react-icons/fa";
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
          // telephone: content?.phone || "+91 98765 43210",
          email: content?.email || "hello@hirafmcg.com"
        }}
      />
      <footer className="mt-16 border-t border-hira-orange/10 bg-white">
        <Container className="grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-[1.2fr_1fr_1fr_1fr]">
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
              {/* <p>{content?.phone || "+91 98765 43210"}</p> */}
              <p>{content?.email || "hello@hirafmcg.com"}</p>
            </div>
          </div>
          <div>
            <p className="text-xs uppercase tracking-[0.26em] text-hira-orange">
              Connect With Us
            </p>
            <div className="mt-4 grid gap-3 text-sm leading-7 text-hira-ink/75">
              <a
                href="https://www.instagram.com/ujjainipoha?igsh=Nm1zc2tjdG42YWNs&utm_source=qr"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-3 break-words transition hover:text-hira-orange"
                aria-label="Instagram"
              >
                <FaInstagram className="shrink-0 text-base" />
                <span>Instagram</span>
              </a>
              <a
                href="https://www.facebook.com/share/18DE34oHyv/?mibextid=wwXIfr"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-3 break-words transition hover:text-hira-orange"
                aria-label="Facebook"
              >
                <FaFacebookF className="shrink-0 text-base" />
                <span>Facebook</span>
              </a>
              <a
                href="mailto:hiraindustries.ujjain@gmail.com"
                className="inline-flex items-center gap-3 break-all transition hover:text-hira-orange"
                aria-label="Email"
              >
                <FaEnvelope className="shrink-0 text-base" />
                <span>hiraindustries.ujjain@gmail.com</span>
              </a>
              <a
                href="https://wa.me/919575212055"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-3 break-words transition hover:text-hira-orange"
                aria-label="WhatsApp"
              >
                <FaWhatsapp className="shrink-0 text-base" />
                <span>+91 9575212055</span>
              </a>
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
