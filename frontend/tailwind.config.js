/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        hira: {
          // Primary Background
          cream: "#FFF9F4",
          // Secondary Surface
          wheat: "#FDF1E7",
          // Soft Peach
          orangeSoft: "#FBE4D3",

          // Accent Orange / Terracotta
          orange: "#F2A365",
          red: "#D97B4D",

          // Button Background / Hover
          button: "#FDD0B1",
          "button-hover": "#F5B68D",

          // Typography
          forest: "#2D241F", // Dark Text
          ink: "#5B5048", // Paragraph
          muted: "#8B7B70",

          // Borders
          border: "#EED9C8",
          // Extra warm surface for cards/panels
          gold: "#F2A365"
        }
      },

      boxShadow: {
        // Softer premium shadow
        soft: "0 18px 45px -30px rgba(45, 36, 31, 0.22)"
      },

      fontFamily: {
        display: ['"Cormorant Garamond"', "serif"],
        body: ['"Manrope"', "sans-serif"]
      },

      backgroundImage: {
        "grain-radial":
          "radial-gradient(circle at top left, rgba(242,163,101,0.18), transparent 45%), radial-gradient(circle at bottom right, rgba(217,123,77,0.12), transparent 42%)"
      },

      // Optional: if any components rely on these animation names
      animation: {
        float: "float 6s ease-in-out infinite",
        fadeUp: "fadeUp 0.8s ease forwards",
        pulseSoft: "pulseSoft 2.8s ease-in-out infinite"
      },
      keyframes: {
        float: {
          "0%, 100%": { transform: "translateY(0px)" },
          "50%": { transform: "translateY(-8px)" }
        },
        fadeUp: {
          "0%": { opacity: "0", transform: "translateY(24px)" },
          "100%": { opacity: "1", transform: "translateY(0)" }
        },
        pulseSoft: {
          "0%, 100%": { opacity: "0.35" },
          "50%": { opacity: "1" }
        }
      }
    }
  },
  plugins: []
};
