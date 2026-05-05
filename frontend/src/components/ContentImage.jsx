import { useEffect, useMemo, useState } from "react";
import { MEDIA_PLACEHOLDER, resolveMediaUrl } from "../lib/media";

export default function ContentImage({
  src,
  alt,
  className = "",
  fallbacks = [],
  ...props
}) {
const sources = useMemo(
    () => {
      const primary = resolveMediaUrl(src, true);
      console.log("FINAL IMG SRC (primary):", primary);
      return [src, ...fallbacks]
        .filter(Boolean)
        .map((item) => resolveMediaUrl(item, true))
        .concat(MEDIA_PLACEHOLDER);
    },
    [fallbacks, src]
  );
  const [index, setIndex] = useState(0);

  useEffect(() => {
    setIndex(0);
  }, [sources]);

function handleError() {
    console.log("FINAL IMG SRC FAILED at index", index, ":", sources[index]);
    setIndex((current) =>
      current < sources.length - 1 ? current + 1 : current
    );
  }

  return (
    <img
      {...props}
      src={sources[index]}
      alt={alt}
      className={className}
      onLoad={() => console.log("FINAL IMG SRC LOADED:", sources[index])}
      onError={handleError}
    />
  );
}
