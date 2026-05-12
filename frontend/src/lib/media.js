const INLINE_PLACEHOLDER = encodeURIComponent(`
  <svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
    <defs>
      <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
        <stop offset="0%" stop-color="#FFF7ED"/>
        <stop offset="100%" stop-color="#FED7AA"/>
      </linearGradient>
    </defs>
    <rect width="1200" height="800" fill="url(#g)"/>
    <circle cx="230" cy="180" r="110" fill="#FACC15" fill-opacity="0.22"/>
    <circle cx="975" cy="625" r="150" fill="#F97316" fill-opacity="0.18"/>
    <rect x="320" y="260" width="560" height="280" rx="32" fill="#FFFFFF" fill-opacity="0.7"/>
    <path d="M430 480l110-120 95 95 78-70 117 95H430z" fill="#F97316" fill-opacity="0.78"/>
    <circle cx="520" cy="345" r="34" fill="#FACC15"/>
    <text x="600" y="610" text-anchor="middle" fill="#9A3412" font-family="Arial, sans-serif" font-size="36" font-weight="700">
      Image coming soon
    </text>
  </svg>
`);

export const MEDIA_PLACEHOLDER = `data:image/svg+xml;charset=UTF-8,${INLINE_PLACEHOLDER}`;

export function resolveMediaUrl(value) {
  if (!value) {
    return "";
  }

  // Stable backend uploads path handling (NO cache-busting)
  let url;
  if (value.startsWith("http")) {
    url = value;
  } else if (value.startsWith("/HIRA/backend/uploads/")) {
    url = `http://localhost${value}`;
  } else if (value.startsWith("/uploads/")) {
    url = `http://localhost/HIRA/backend${value}`;
  } else if (
    /^(?:https?:)?\/\//i.test(value) ||
    value.startsWith("data:") ||
    value.startsWith("blob:")
  ) {
    url = value;
  } else if (value.startsWith("/")) {
    url = value;
  } else {
    url = `/${value.replace(/^\/+/, "")}`;
  }

  // IMPORTANT: do not append Date.now()/random cache-busting params
  return url;
}

export function withPlaceholder(value) {
  return resolveMediaUrl(value) || MEDIA_PLACEHOLDER;
}
