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
    () =>
      [src, ...fallbacks]
        .filter(Boolean)
        .map((item) => resolveMediaUrl(item))
        .concat(MEDIA_PLACEHOLDER),
    [fallbacks, src]
  );
  const [index, setIndex] = useState(0);

  useEffect(() => {
    setIndex(0);
  }, [sources]);

  function handleError() {
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
      onError={handleError}
    />
  );
}
