import { useEffect, useState } from "react";
import { api } from "../lib/api";

export function usePageContent(pageName) {
  const [state, setState] = useState({
    loading: true,
    error: "",
    content: null
  });

  useEffect(() => {
    let cancelled = false;

    setState({ loading: true, error: "", content: null });

    api
      .getPage(pageName)
      .then((data) => {
        if (!cancelled) {
          setState({ loading: false, error: "", content: data.content });
        }
      })
      .catch((error) => {
        if (!cancelled) {
          setState({
            loading: false,
            error: error.message || "Unable to load page content.",
            content: null
          });
        }
      });

    return () => {
      cancelled = true;
    };
  }, [pageName]);

  return state;
}
