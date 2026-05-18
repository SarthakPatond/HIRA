import { Navigate, Route, Routes } from "react-router-dom";
import Layout from "./components/Layout";
import AboutPage from "./pages/AboutPage";
import ComingSoonPage from "./pages/ComingSoonPage";
import ContactPage from "./pages/ContactPage";
import DistributorPage from "./pages/DistributorPage";
import HomePage from "./pages/HomePage";
import InsightsPage from "./pages/InsightsPage";
import NotFoundPage from "./pages/NotFoundPage";
import RecipeDetailsPage from "./pages/RecipeDetailsPage";
import RecipesPage from "./pages/RecipesPage";
import ProductDetailsPage from "./pages/ProductDetailsPage";
import ProductsPage from "./pages/ProductsPage";

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/home" element={<Navigate to="/" replace />} />
        <Route path="/products" element={<ProductsPage />} />
        <Route path="/products/:slug" element={<ProductDetailsPage />} />
        <Route path="/recipes" element={<RecipesPage />} />
        <Route path="/recipes/:slug" element={<RecipeDetailsPage />} />
        <Route path="/about" element={<AboutPage />} />
        <Route path="/coming-soon" element={<ComingSoonPage />} />
        <Route path="/insights" element={<InsightsPage />} />
        <Route path="/distributor" element={<DistributorPage />} />
        <Route path="/contact" element={<ContactPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  );
}
