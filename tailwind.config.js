/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: "#9c0f2f",
        "primary-hover": "#7a0c25",
        "primary-light": "#be1239",
        "sidebar-bg": "#FFFFFF",
        "main-bg": "#F8FAFC",
        "card-bg": "#FFFFFF",
        "card-border": "#E2E8F0",
        "text-main": "#1E293B",
        "text-muted": "#64748B",
        "chart-red": "#EF4444",
        "chart-blue": "#3B82F6",
        "chart-orange": "#F97316",
        "chart-green": "#22C55E",
        "chart-yellow": "#EAB308",
        "chart-purple": "#9c0f2f",
        "status-risk": "#EF4444",
        "status-warning": "#EAB308",
        "status-safe": "#22C55E",
      },
      fontFamily: {
        display: ["Inter", "sans-serif"],
      },
      boxShadow: {
        card: "0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03)",
      },
    },
  },
  plugins: [],
}
