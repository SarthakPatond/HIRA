import { useEffect } from "react";

function upsertMeta(name, content, attribute = "name") {
  if (!content) return;

  let element = document.head.querySelector(`meta[${attribute}="${name}"]`);
  if (!element) {
    element = document.createElement("meta");
    element.setAttribute(attribute, name);
    document.head.appendChild(element);
  }

  element.setAttribute("content", content);
}

export default function Seo({ title, description, schema }) {
  useEffect(() => {
    if (title) {
      document.title = title;
    }

    upsertMeta("description", description);
    upsertMeta("og:title", title, "property");
    upsertMeta("og:description", description, "property");
    upsertMeta("twitter:title", title, "name");
    upsertMeta("twitter:description", description, "name");

    let script = null;
    if (schema) {
      script = document.createElement("script");
      script.type = "application/ld+json";
      script.text = JSON.stringify(schema);
      document.head.appendChild(script);
    }

    return () => {
      if (script) {
        script.remove();
      }
    };
  }, [title, description, schema]);

  return null;
}
