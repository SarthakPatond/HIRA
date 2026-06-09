import { useEffect, useState } from "react";
import { Link, NavLink } from "react-router-dom";
import Container from "./Container";

const links = [
  { to: "/", label: "Home" },
  { to: "/products", label: "Products" },
  { to: "/recipes", label: "Recipes" },
  { to: "/about", label: "About" },
  { to: "/contact", label: "Contact" }
];

export default function Navbar() {
  const [mobileOpen, setMobileOpen] = useState(false);

  useEffect(() => {
    function closeOnResize() {
      if (window.innerWidth >= 1024) {
        setMobileOpen(false);
      }
    }

    window.addEventListener("resize", closeOnResize);
    return () => window.removeEventListener("resize", closeOnResize);
  }, []);

  return (
    <header className="sticky top-0 z-50 border-b border-hira-orange/10 bg-white/90 backdrop-blur-xl">
      <Container className="flex items-center justify-between py-4">
        <Link to="/" className="flex items-center gap-3">
          <div className="grid h-14 w-17 place-items-center rounded-2xl bg-gradient-to-br to-hira-red text-sm font-bold text-white shadow-soft">
            <img
              className="h-14 w-14"
              src="/Hiraa Logo Final.png"
              alt="Hira FMCG Logo"
            />
          </div>
          <div>
            <p className="font-display text-2xl font-semibold text-hira-ink">
              Hira FMCG 
            </p>
            <p className="text-[10px] uppercase tracking-[0.3em] text-hira-orange">
              Ujjain to Modern Kitchens
            </p>
          </div>
        </Link>

        <nav className="hidden items-center gap-8 lg:flex">
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              className={({ isActive }) =>
                `text-sm font-semibold transition ${
                  isActive
                    ? "text-hira-orange"
                    : "text-hira-ink/75 hover:text-hira-orange"
                }`
              }
            >
              {link.label}
            </NavLink>
          ))}
        </nav>

        <div className="hidden lg:block">
          <Link
            to="/distributor"
            className="rounded-full bg-hira-orange px-5 py-3 text-sm font-semibold text-white transition hover:bg-hira-red"
          >
            Become a Distributor
          </Link>
        </div>

        <button
          type="button"
          onClick={() => setMobileOpen((current) => !current)}
          className="relative inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-hira-orange/15 text-hira-ink lg:hidden"
          aria-label="Toggle menu"
        >
          <span
            className={`block h-[2px] w-5 bg-current transition ${
              mobileOpen ? "rotate-45" : "-translate-y-[6px]"
            }`}
          />
          <span
            className={`absolute block h-[2px] w-5 bg-current transition ${
              mobileOpen ? "opacity-0" : ""
            }`}
          />
          <span
            className={`block h-[2px] w-5 bg-current transition ${
              mobileOpen ? "-rotate-45" : "translate-y-[6px]"
            }`}
          />
        </button>
      </Container>

      <div
        className={`overflow-hidden border-t border-hira-orange/10 bg-white transition-all duration-300 lg:hidden ${
          mobileOpen ? "max-h-80" : "max-h-0"
        }`}
      >
        <Container className="grid gap-2 py-4">
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              onClick={() => setMobileOpen(false)}
              className={({ isActive }) =>
                `rounded-2xl px-4 py-3 text-sm font-semibold transition ${
                  isActive
                    ? "bg-hira-orange text-white"
                    : "bg-hira-cream text-hira-ink hover:bg-hira-wheat/25"
                }`
              }
            >
              {link.label}
            </NavLink>
          ))}
          <Link
            to="/distributor"
            onClick={() => setMobileOpen(false)}
            className="mt-2 rounded-full bg-hira-orange px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-hira-red"
          >
            Become a Distributor
          </Link>
        </Container>
      </div>
    </header>
  );
}
