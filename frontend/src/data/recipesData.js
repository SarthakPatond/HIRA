/**
 * Recipes data is kept in a dedicated module so it can be swapped
 * later with JSON/CMS/API without changing UI component structure.
 */

export const RECIPES = [
  // ===== Poha =====
  {
    slug: "kanda-poha",
    name: "Kanda Poha",
    category: "Poha",
    cookingTimeMinutes: 20,
    difficulty: "Easy",
    shortDescription:
      "A classic Maharashtrian-style poha with caramelized onions, mustard seeds, and a burst of lemon.",
    image:
      "https://images.unsplash.com/photo-1604908176997-125f25f8f2e5?auto=format&fit=crop&w=1200&q=80",
    heroImage:
      "https://images.unsplash.com/photo-1604908176997-125f25f8f2e5?auto=format&fit=crop&w=1600&q=80",
    thumbnail:
      "https://images.unsplash.com/photo-1604908176997-125f25f8f2e5?auto=format&fit=crop&w=600&q=80",
    ingredients: [
      "2 cups thick poha",
      "1 medium onion (finely sliced)",
      "2 tbsp oil",
      "1 tsp mustard seeds",
      "1 tsp cumin seeds",
      "8-10 curry leaves",
      "1-2 green chilies (slit)",
      "1/4 tsp turmeric powder",
      "Salt to taste",
      "1 tbsp lemon juice",
      "Fresh coriander (for garnish)"
    ],
    steps: [
      "Rinse poha in water quickly and drain. Keep it aside for 5 minutes.",
      "Heat oil in a pan. Add mustard seeds and cumin seeds. Let them crackle.",
      "Add curry leaves and green chilies. Sauté for 30 seconds.",
      "Add onions and cook until golden brown.",
      "Sprinkle turmeric powder and add the poha. Mix gently so it doesn’t turn mushy.",
      "Cook for 3-4 minutes on low flame. Add salt and lemon juice.",
      "Garnish with coriander and serve hot."
    ],
    tips: [
      "Use thick poha for the best texture.",
      "Don’t soak poha for long—just a quick rinse is enough.",
      "For extra flavor, add roasted peanuts during the final mix."
    ],
    servings: 2,
    relatedSlugs: ["indori-poha", "vegetable-poha", "sabudana-khichdi"]
  },
  {
    slug: "indori-poha",
    name: "Indori Poha",
    category: "Poha",
    cookingTimeMinutes: 22,
    difficulty: "Easy",
    shortDescription:
      "Indori-style poha with a tangy twist and a signature crunch for an everyday breakfast upgrade.",
    image:
      "/brand-art/2.png",
    heroImage:
      "/brand-art/2.png",
    ingredients: [
      "2 cups thick poha",
      "1 medium onion (chopped)",
      "1 tbsp peanuts (optional)",
      "2 tbsp oil",
      "1 tsp mustard seeds",
      "1/2 tsp cumin seeds",
      "Curry leaves (few)",
      "1 tsp ginger-garlic paste (optional)",
      "1/4 tsp turmeric powder",
      "1-2 tbsp lemon juice",
      "Salt to taste",
      "Chopped coriander (for garnish)"
    ],
    steps: [
      "Rinse poha quickly and drain. Set aside.",
      "Heat oil; add mustard and cumin seeds. Add curry leaves.",
      "Sauté onions until soft and slightly golden.",
      "Add peanuts (optional) and ginger-garlic paste (optional). Cook briefly.",
      "Add turmeric and then poha. Mix lightly.",
      "Cover and cook for 2 minutes. Turn off heat and add lemon juice.",
      "Serve with coriander on top."
    ],
    tips: [
      "For Indori flavor, balance tang with lemon at the end.",
      "Keep flame low to avoid drying out poha.",
      "Add a pinch of sugar if you prefer a subtle sweet-sour taste."
    ],
    relatedSlugs: ["kanda-poha", "vegetable-poha", "sabudana-vada"]
  },
  {
    slug: "vegetable-poha",
    name: "Vegetable Poha",
    category: "Poha",
    cookingTimeMinutes: 25,
    difficulty: "Medium",
    shortDescription:
      "Poha loaded with colorful veggies for a wholesome, filling breakfast with HIRA-style comfort.",
    image:
      "/brand-art/3.png",
    heroImage:
      "/brand-art/3.png",
    ingredients: [
      "2 cups thick poha",
      "1/2 cup mixed vegetables (peas, carrots, beans)",
      "2 tbsp oil",
      "1 tsp mustard seeds",
      "1/2 tsp cumin seeds",
      "Curry leaves (few)",
      "1/4 tsp turmeric powder",
      "Salt to taste",
      "1 tbsp lemon juice",
      "Chopped coriander",
      "Optional: roasted peanuts"
    ],
    steps: [
      "Rinse poha quickly and drain. Keep aside.",
      "In a pan, heat oil and add mustard seeds and cumin seeds.",
      "Add curry leaves and mixed vegetables. Cook until tender.",
      "Add turmeric and salt.",
      "Add poha and mix gently. Cook for 3-4 minutes.",
      "Switch off and add lemon juice and coriander.",
      "Serve hot."
    ],
    tips: [
      "Use steamed mixed vegetables for faster cooking.",
      "Add veggies in small pieces for better bite.",
      "Add peanuts right before serving for crunch."
    ],
    relatedSlugs: ["kanda-poha", "indori-poha", "sabudana-kheer"]
  },

  // ===== Sabudana =====
  {
    slug: "sabudana-khichdi",
    name: "Sabudana Khichdi",
    category: "Sabudana",
    cookingTimeMinutes: 35,
    difficulty: "Medium",
    shortDescription:
      "Fluffy sabudana khichdi with peanuts, cumin, and a perfect, non-sticky finish.",
    image:
      "/brand-art/4.png",
    heroImage:
      "/brand-art/4.png",
    ingredients: [
      "1 cup sabudana (sago)",
      "1 medium potato (boiled, diced)",
      "2 tbsp oil or ghee",
      "1 tsp cumin seeds",
      "1-2 green chilies (finely chopped)",
      "1/2 cup roasted peanuts",
      "1/4 tsp turmeric powder",
      "Salt to taste",
      "1 tbsp lemon juice",
      "Fresh coriander"
    ],
    steps: [
      "Soak sabudana in water for 4-6 hours (or overnight). Ensure it’s well-drained.",
      "In a pan, heat oil/ghee; add cumin seeds and green chilies.",
      "Add potatoes and sauté briefly.",
      "Add soaked sabudana, turmeric, and salt. Mix gently.",
      "Cover and cook for 8-10 minutes, stirring occasionally.",
      "Add roasted peanuts and lemon juice at the end.",
      "Garnish with coriander and serve."
    ],
    tips: [
      "Drain well—wet excess water causes stickiness.",
      "Stir gently; don’t mash potatoes during cooking.",
      "If it becomes dry, sprinkle a few drops of water."
    ],
    relatedSlugs: ["sabudana-vada", "kanda-poha", "sabudana-kheer"]
  },
  {
    slug: "sabudana-vada",
    name: "Sabudana Vada",
    category: "Sabudana",
    cookingTimeMinutes: 45,
    difficulty: "Hard",
    shortDescription:
      "Crispy sabudana vadas with a soft center—ideal for evening snacks and fast bites.",
    image:
      "https://images.unsplash.com/photo-1604908177522-7d3e1ef3b3f6?auto=format&fit=crop&w=1200&q=80",
    heroImage:
      "https://images.unsplash.com/photo-1604908177522-7d3e1ef3b3f6?auto=format&fit=crop&w=1600&q=80",
    thumbnail:
      "https://images.unsplash.com/photo-1604908177522-7d3e1ef3b3f6?auto=format&fit=crop&w=600&q=80",
    ingredients: [
      "1 cup sabudana",
      "2 tbsp roasted peanuts (crushed)",
      "1 medium potato (boiled, mashed)",
      "1-2 green chilies (chopped)",
      "1 tsp cumin seeds (optional)",
      "1 tbsp chopped coriander",
      "Salt to taste",
      "Oil for frying"
    ],
    steps: [
      "Soak sabudana for 4-6 hours until it becomes soft and translucent.",
      "Drain completely and mix sabudana with mashed potato, peanuts, green chilies, coriander, and salt.",
      "Form small patties/vadas and flatten slightly.",
      "Heat oil on medium flame and fry until golden and crisp.",
      "Drain on tissue paper and serve hot with chutney."
    ],
    tips: [
      "Oil temperature matters—too hot burns outside, too cold makes them oily.",
      "Ensure sabudana is properly soaked for binding.",
      "Add a pinch of cumin for extra aroma."
    ],
    servings: 2,
    relatedSlugs: ["sabudana-khichdi", "sabudana-kheer", "kanda-poha"]
  },
  {
    slug: "sabudana-kheer",
    name: "Sabudana Kheer",
    category: "Sabudana",
    cookingTimeMinutes: 40,
    difficulty: "Medium",
    shortDescription:
      "Creamy sabudana kheer with a gentle sweetness—comfort dessert with a premium finish.",
    image:
      "/brand-art/2.png",
    heroImage:
      "/brand-art/2.png",
    ingredients: [
      "1 cup soaked sabudana",
      "2 cups milk",
      "1/4 cup sugar (adjust to taste)",
      "1/4 tsp cardamom powder",
      "1 tbsp chopped nuts (optional)",
      "1 tbsp ghee (optional)"
    ],
    steps: [
      "In a heavy pan, bring milk to a gentle boil.",
      "Add soaked sabudana and simmer on low flame.",
      "Cook until sabudana turns translucent and milk thickens.",
      "Add sugar and cardamom powder.",
      "Stir in nuts (optional) and cook for 2 more minutes.",
      "Serve warm or chilled."
    ],
    tips: [
      "Use low flame to avoid milk scorching.",
      "Stir occasionally for a smooth texture.",
      "For richer taste, add a little ghee while simmering."
    ],
    relatedSlugs: ["sabudana-khichdi", "kanda-poha", "vegetable-poha"]
  },

  // ===== Snacks =====
  {
    slug: "snacks-chai-time-mix",
    name: "Chai-Time Snacks Mix",
    category: "Snacks",
    cookingTimeMinutes: 15,
    difficulty: "Easy",
    shortDescription:
      "A quick pantry-to-table snack mix perfect for tea time—crunchy, flavorful, and shareable.",
    image:
      "/brand-art/3.png",
    heroImage:
      "/brand-art/3.png",
    ingredients: [
      "2 cups ready-to-roast snacks (adjust as desired)",
      "1 tbsp oil or ghee (optional)",
      "1/2 tsp salt",
      "1/2 tsp chaat masala",
      "1/4 tsp red chili powder (optional)",
      "Lemon juice (few drops)",
      "Coriander (optional)"
    ],
    steps: [
      "Add snacks to a bowl.",
      "Add salt, chaat masala, and spices.",
      "Drizzle oil/ghee if needed for better coating.",
      "Add a few drops of lemon juice.",
      "Toss well and serve."
    ],
    tips: [
      "Add spices gradually and taste before serving.",
      "Keep it fresh by adding lemon right before eating.",
      "For extra premium flavor, use roasted spices powder."
    ],
    relatedSlugs: ["kanda-poha", "indori-poha", "sabudana-vada"]
  },

  // ===== Pantry products category (kept as Snacks-type for discovery) =====
  {
    slug: "pantry-quick-fry",
    name: "Pantry Quick Fry",
    category: "Snacks",
    cookingTimeMinutes: 18,
    difficulty: "Easy",
    shortDescription:
      "A fast fry using pantry staples—golden, crisp, and made for busy kitchens.",
    image:
      "/brand-art/4.png",
    heroImage:
      "/brand-art/4.png",
    ingredients: [
      "1 pack pantry snacks base (as per your pantry)",
      "2 tbsp oil",
      "1/2 tsp salt",
      "1/2 tsp garam masala (optional)",
      "1-2 tsp chili flakes (optional)"
    ],
    steps: [
      "Heat oil in a pan.",
      "Add pantry snacks base and fry on medium heat.",
      "Stir until golden and crisp.",
      "Add salt and optional spices.",
      "Serve hot."
    ],
    tips: [
      "Fry in batches for even crisping.",
      "Don’t overload the pan.",
      "Add spices after frying to keep crunch."
    ],
    relatedSlugs: ["snacks-chai-time-mix", "vegetable-poha", "sabudana-kheer"]
  },

  // ===== Dummy recipes for UI polish (Poha / Snacks) =====
  {
    slug: "masala-poha",
    name: "Masala Poha",
    category: "Poha",
    cookingTimeMinutes: 25,
    difficulty: "Medium",
    shortDescription:
      "A comforting masala poha with a spiced onion base, peanuts, and a balanced tangy-salty finish.",
    image:
      "https://images.unsplash.com/photo-1549931319-a43f7b9f1f0a?auto=format&fit=crop&w=1200&q=80",
    heroImage:
      "https://images.unsplash.com/photo-1549931319-a43f7b9f1f0a?auto=format&fit=crop&w=1600&q=80",
    thumbnail:
      "https://images.unsplash.com/photo-1549931319-a43f7b9f1f0a?auto=format&fit=crop&w=600&q=80",
    servings: 2,
    ingredients: [
      "2 cups thick poha",
      "1 medium onion (chopped)",
      "1 tbsp oil + 1 tbsp ghee (optional)",
      "1 tsp mustard seeds",
      "1/2 tsp cumin seeds",
      "1/2 tsp ginger (grated, optional)",
      "1–2 green chilies (chopped)",
      "1/4 tsp turmeric powder",
      "1/2 tsp red chili powder (optional)",
      "1/2 cup roasted peanuts",
      "Salt to taste",
      "1 tbsp lemon juice",
      "Coriander leaves (for garnish)"
    ],
    steps: [
      "Rinse poha quickly, drain, and keep aside for 5–7 minutes.",
      "Heat oil (and ghee if using). Add mustard seeds and cumin; let them crackle.",
      "Add onions (and ginger/chilies if using). Sauté until soft and lightly golden.",
      "Stir in turmeric (and red chili powder if using).",
      "Add poha and toss gently. Add salt and cook for 3–4 minutes on low.",
      "Add roasted peanuts and cook 1 minute more so flavors combine.",
      "Finish with lemon juice and coriander. Serve immediately."
    ],
    tips: [
      "Toss poha gently—overmixing makes it mushy.",
      "Add peanuts right before finishing for better crunch.",
      "Adjust chili powder to your spice preference."
    ],
    relatedSlugs: ["kanda-poha", "indori-poha", "vegetable-poha"]
  },
  {
    slug: "peanut-poha",
    name: "Peanut Poha",
    category: "Poha",
    cookingTimeMinutes: 22,
    difficulty: "Easy",
    shortDescription:
      "Nutty, satisfying peanut poha with caramelized onions and crisp roasted peanuts in every bite.",
    image:
      "https://images.unsplash.com/photo-1600891964599-f61ba0e24092?auto=format&fit=crop&w=1200&q=80",
    heroImage:
      "https://images.unsplash.com/photo-1600891964599-f61ba0e24092?auto=format&fit=crop&w=1600&q=80",
    thumbnail:
      "https://images.unsplash.com/photo-1600891964599-f61ba0e24092?auto=format&fit=crop&w=600&q=80",
    servings: 2,
    ingredients: [
      "2 cups thick poha",
      "1 onion (thinly sliced)",
      "2 tbsp oil",
      "1 tsp mustard seeds",
      "1 tsp cumin seeds",
      "8 curry leaves",
      "1–2 green chilies (slit)",
      "1/4 tsp turmeric powder",
      "1/2 cup roasted peanuts",
      "Salt to taste",
      "1 tbsp lemon juice",
      "Coriander leaves"
    ],
    steps: [
      "Rinse poha quickly and drain. Keep aside for 5 minutes.",
      "Heat oil; add mustard and cumin seeds. Add curry leaves and green chilies.",
      "Add onions and cook until caramelized and fragrant.",
      "Sprinkle turmeric and add poha; toss gently.",
      "Cook for 3 minutes on low. Stir in peanuts and cook 1 more minute.",
      "Turn off heat, add lemon juice and coriander.",
      "Serve warm."
    ],
    tips: [
      "Roast peanuts for extra crunch.",
      "Caramelize onions for a sweeter, deeper flavor.",
      "Add lemon after cooking to keep it fresh."
    ],
    relatedSlugs: ["kanda-poha", "masala-poha", "sabudana-khichdi"]
  },
  {
    slug: "poha-cutlet",
    name: "Poha Cutlet",
    category: "Snacks",
    cookingTimeMinutes: 40,
    difficulty: "Medium",
    shortDescription:
      "Crispy poha cutlets with a soft interior—spiced potato, poha, and a golden pan-fry finish.",
    image:
      "https://images.unsplash.com/photo-1546549032-9571cd6b27df?auto=format&fit=crop&w=1200&q=80",
    heroImage:
      "https://images.unsplash.com/photo-1546549032-9571cd6b27df?auto=format&fit=crop&w=1600&q=80",
    thumbnail:
      "https://images.unsplash.com/photo-1546549032-9571cd6b27df?auto=format&fit=crop&w=600&q=80",
    servings: 2,
    ingredients: [
      "2 cups cooked/soaked poha (thick poha)",
      "1 medium potato (boiled, mashed)",
      "1/2 cup finely chopped onion",
      "2 green chilies (chopped)",
      "1 tbsp chopped coriander",
      "1/2 tsp cumin seeds (optional)",
      "1/4 tsp turmeric",
      "1/2 tsp garam masala",
      "Salt to taste",
      "Bread crumbs (as needed for binding)",
      "Oil for shallow frying"
    ],
    steps: [
      "Soak/rinse poha quickly, then squeeze excess water and let it cool.",
      "Mix poha with mashed potato, onion, chilies, coriander, spices, and salt.",
      "Add bread crumbs if needed to bind. Shape into small cutlets.",
      "Shallow fry in medium-hot oil until golden brown on both sides.",
      "Drain on tissue and serve with chutney."
    ],
    tips: [
      "Let the mixture cool before shaping for cleaner edges.",
      "Keep oil medium-hot to avoid burning and raw centers.",
      "Serve immediately for maximum crispiness."
    ],
    relatedSlugs: ["kanda-poha", "peanut-poha", "sabudana-vada"]
  }
];

export function getRecipeBySlug(slug) {
  const normalized = String(slug || "").trim();
  return RECIPES.find((r) => r.slug === normalized) || null;
}

export function getRelatedRecipesBySlugs(relatedSlugs = []) {
  const slugs = relatedSlugs.map((s) => String(s).trim());
  return RECIPES.filter((r) => slugs.includes(r.slug));
}
