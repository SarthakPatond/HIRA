/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        hira: {
          cream: "#FFF7ED",
          yellow: "#FACC15",
          wheat: "#FACC15",
          gold: "#FACC15",
          orange: "#F97316",
          red: "#DC2626",
          green: "#86EFAC",
          forest: "#166534",
          ink: "#292524"
        }
      },
      boxShadow: {
        soft: "0 24px 50px -28px rgba(41, 37, 36, 0.24)"
      },
      fontFamily: {
        display: ['"Cormorant Garamond"', "serif"],
        body: ['"Manrope"', "sans-serif"]
      },
      backgroundImage: {
        "grain-radial":
          "radial-gradient(circle at top left, rgba(217,119,6,0.22), transparent 42%), radial-gradient(circle at bottom right, rgba(73,98,53,0.18), transparent 38%)"
      },
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
