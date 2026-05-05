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
  message: ""
};

export default function ContactPage() {
  const { content, loading, error } = usePageContent("contact");
  const [form, setForm] = useState(initialState);
  const [status, setStatus] = useState({ loading: false, message: "", error: "" });

  if (loading) return <LoadingState label="Loading contact page" />;
  if (error) return <ErrorState message={error} />;

  const description =
    "Contact Hira FMCG for retail, distribution, institutional supply, and brand inquiries.";

  async function handleSubmit(event) {
    event.preventDefault();
    setStatus({ loading: true, message: "", error: "" });

    try {
      await api.submitContact(form);
      setForm(initialState);
      setStatus({
        loading: false,
        message: "Your message has been sent successfully.",
        error: ""
      });
    } catch (submitError) {
      setStatus({
        loading: false,
        message: "",
        error: submitError.message || "Unable to send message."
      });
    }
  }

  return (
    <div>
      <Seo
        title="Contact | Hira FMCG"
        description={description}
        schema={{
          "@context": "https://schema.org",
          "@type": "ContactPage",
          name: "Contact Hira FMCG",
          description,
          telephone: content?.phone,
          email: content?.email
        }}
      />

      <PageHero
        eyebrow="Contact"
        title={content.title}
        description={content.subtitle}
        image={content.hero_image}
      />

      <Section containerClassName="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
          <div className="rounded-[2rem] border border-hira-orange/10 bg-white p-8 shadow-soft sm:p-10">
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
              Contact Details
            </p>
            <div className="mt-6 grid gap-6 text-hira-ink/75">
              <div>
                <h2 className="font-display text-3xl text-hira-forest">Address</h2>
                <p className="mt-3 leading-8">{content.address}</p>
              </div>
              <div>
                <h2 className="font-display text-3xl text-hira-forest">Phone</h2>
                <p className="mt-3 leading-8">{content.phone}</p>
              </div>
              <div>
                <h2 className="font-display text-3xl text-hira-forest">Email</h2>
                <p className="mt-3 leading-8">{content.email}</p>
              </div>
            </div>
          </div>

          <div className="rounded-[2rem] border border-hira-orange/10 bg-white p-8 shadow-soft sm:p-10">
            <p className="text-xs font-semibold uppercase tracking-[0.28em] text-hira-orange">
              Contact Form
            </p>
            <h2 className="mt-4 font-display text-4xl text-hira-forest">
              Start the conversation
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
              <textarea
                className="touch-input min-h-[180px]"
                placeholder="How can we help you?"
                value={form.message}
                onChange={(event) => setForm({ ...form, message: event.target.value })}
                required
              />
              <button
                type="submit"
                disabled={status.loading}
                className="rounded-full bg-hira-orange px-6 py-4 font-semibold text-white transition hover:bg-hira-red disabled:cursor-not-allowed disabled:opacity-70"
              >
                {status.loading ? "Sending..." : "Send Message"}
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
