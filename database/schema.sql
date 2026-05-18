CREATE DATABASE IF NOT EXISTS hira_fmcg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hira_fmcg;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  benefits TEXT NULL,
  pack_sizes TEXT NULL,
  image VARCHAR(255) NULL,
  is_coming_soon TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cms_pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_name VARCHAR(50) NOT NULL UNIQUE,
  content LONGTEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  business_type VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  source VARCHAR(50) NULL,
  city VARCHAR(100) NULL,
  business_details TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

-- =========================
-- Recipes CMS (new tables)
-- =========================

CREATE TABLE IF NOT EXISTS recipes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  category VARCHAR(120) NOT NULL,
  short_description TEXT NOT NULL,

  hero_image VARCHAR(255) NULL,
  thumbnail_image VARCHAR(255) NULL,

  cook_time_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  servings INT UNSIGNED NOT NULL DEFAULT 0,
  difficulty VARCHAR(20) NOT NULL DEFAULT 'Easy',

  -- JSON strings for CMS-driven content
  tips LONGTEXT NULL,
  related_products_json LONGTEXT NULL,

  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT UNSIGNED NOT NULL,
  ingredient_order INT UNSIGNED NOT NULL DEFAULT 0,
  ingredient_text TEXT NOT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_recipe_ingredients_recipe_id (recipe_id)
);

CREATE TABLE IF NOT EXISTS recipe_steps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipe_id INT UNSIGNED NOT NULL,
  step_order INT UNSIGNED NOT NULL DEFAULT 0,
  step_text TEXT NOT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_recipe_steps_recipe_id (recipe_id)
);

