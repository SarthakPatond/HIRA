import { useState } from "react";
import Section from "../components/Section";
import Seo from "../components/Seo";
import ErrorState from "../components/ErrorState";
import LoadingState from "../components/LoadingState";
import PageHero from "../components/PageHero";
import { usePageContent } from "../hooks/usePageContent";
import { api } from "../lib/api";

const initialState = {
  name: "",
  phone: "",
  business_type: "",
  message: ""
};

export default function DistributorPage() {
  const { content, loading, error } = usePageContent("home");
  const [form, setForm] = useState(initialState);
  const [status, setStatus] = useState({ loading: false, message: "", error: "" });

  if (loading) return <LoadingState label="Loading distributor page" />;
  if (error) return <ErrorState message={error} />;

  const description =
    "Use Hira FMCG's B2B portal for distributor, stockist, wholesaler, and repacking partner inquiries.";

  async function handleSubmit(event) {
    event.preventDefault();
    setStatus({ loading: true, message: "", error: "" });

    try {
      await api.submitLead(form);
      setForm(initialState);
      setStatus({
        loading: false,
        message: "Your distributor inquiry has been sent.",
        error: ""
      });
    } catch (submitError) {
      setStatus({
        loading: false,
        message: "",
        error: submitError.message || "Unable to send inquiry."
      });
    }
  }

  return (
    <div>
      <Seo
        title="B2B Portal | Hira FMCG"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "WebPage",
          name: "Hira FMCG Distributor Portal",
          description
        }}
      />

      <PageHero
        eyebrow="Distributor Partnerships"
        title={content.cta.title}
        description={content.cta.text}
        image="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80"
      />

      <Section containerClassName="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
          <div className="rounded-[2rem] bg-hira-forest p-8 text-white shadow-soft sm:p-10">
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-wheat">
              Why Partner
            </p>
            <h2 className="mt-4 font-display text-4xl">
              A dedicated B2B portal for stockists, wholesalers, and repacking partners.
            </h2>
            <ul className="mt-8 grid gap-4 text-sm leading-7 text-white/80">
              <li>Dependable staple categories with household familiarity.</li>
              <li>Brand storytelling that improves shelf recall and trust.</li>
              <li>Room to scale into launch calendars and future categories.</li>
              <li>Direct lead capture into the admin dashboard for quick follow-up.</li>
              <li>Structured wholesale inquiry flow instead of a generic contact form.</li>
            </ul>
          </div>

          <div className="rounded-[2rem] border border-hira-orange/10 bg-white p-8 shadow-soft sm:p-10">
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
              Inquiry Form
            </p>
            <h2 className="mt-4 font-display text-4xl text-hira-forest">
              Become a distributor
            </h2>
            <form className="mt-8 grid gap-5" onSubmit={handleSubmit}>
              <input
                className="touch-input"
                placeholder="Name"
                value={form.name}
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
              <input
                className="touch-input"
                placeholder="Phone"
                value={form.phone}
                onChange={(event) => setForm({ ...form, phone: event.target.value })}
                required
              />
              <input
                className="touch-input"
                placeholder="Business Type"
                value={form.business_type}
                onChange={(event) =>
                  setForm({ ...form, business_type: event.target.value })
                }
                required
              />
              <textarea
                className="touch-input min-h-[180px]"
                placeholder="Tell us about your market, distribution reach, or retail interest."
                value={form.message}
                onChange={(event) => setForm({ ...form, message: event.target.value })}
                required
              />
              <button
                type="submit"
                disabled={status.loading}
                className="rounded-full bg-hira-orange px-6 py-4 font-semibold text-white transition hover:bg-hira-red disabled:cursor-not-allowed disabled:opacity-70"
              >
                {status.loading ? "Submitting..." : "Submit Inquiry"}
              </button>
              {status.message ? (
                <p className="rounded-2xl bg-hira-green/10 px-4 py-3 text-sm text-hira-green">
                  {status.message}
                </p>
              ) : null}
              {status.error ? (
                <p className="rounded-2xl bg-hira-red/10 px-4 py-3 text-sm text-hira-red">
                  {status.error}
                </p>
              ) : null}
            </form>
          </div>
      </Section>
    </div>
  );
}
