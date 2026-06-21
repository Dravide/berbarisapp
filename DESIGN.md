---
name: Vibrant Momentum
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#424656'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#737687'
  outline-variant: '#c2c6d9'
  surface-tint: '#0053da'
  primary: '#004cca'
  on-primary: '#ffffff'
  primary-container: '#0062ff'
  on-primary-container: '#f3f3ff'
  inverse-primary: '#b4c5ff'
  secondary: '#446900'
  on-secondary: '#ffffff'
  secondary-container: '#b2f746'
  on-secondary-container: '#496f00'
  tertiary: '#006168'
  on-tertiary: '#ffffff'
  tertiary-container: '#007c84'
  on-tertiary-container: '#d7fbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#b2f746'
  secondary-fixed-dim: '#98da27'
  on-secondary-fixed: '#121f00'
  on-secondary-fixed-variant: '#334f00'
  tertiary-fixed: '#7df4ff'
  tertiary-fixed-dim: '#00dbe9'
  on-tertiary-fixed: '#002022'
  on-tertiary-fixed-variant: '#004f54'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
  electric-blue: '#0062FF'
  neon-lime: '#A3E635'
  cyber-cyan: '#00F0FF'
  deep-slate: '#111827'
  cool-gray: '#4B5563'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '800'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '800'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.05em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  grid-margin: 24px
  grid-gutter: 24px
  section-gap-lg: 120px
  section-gap-md: 80px
  container-max: 1280px
---

## Brand & Style

The design system is engineered to transform a standard event management utility into a high-energy, tech-forward platform. The brand personality is **Professional**, **Efficient**, and **Futuristic**, yet maintains a **Welcoming** atmosphere for organizers and participants alike.

We employ a **Modern Corporate** style infused with **Glassmorphism** and **High-Contrast** accents. This approach uses clean, structured layouts to signal reliability, while leveraging vibrant "Electric Blue" and "Neon Lime" to inject energy and excitement—mimicking the thrill of live competitions. The interface relies on generous whitespace and layered depth to ensure clarity even when managing complex data like live scoreboards and participant registrations.

## Colors

The palette revolves around **Electric Blue**, a high-saturation primary that commands attention and establishes authority. We pair this with **Neon Lime** as a functional accent for primary actions (CTAs) and success states, creating a "tech-native" look.

- **Primary (Electric Blue):** Used for branding, main navigation, and primary interactive states.
- **Secondary (Neon Lime):** Reserved for high-priority conversion points and "Live" status indicators.
- **Tertiary (Cyber Cyan):** Utilized for secondary accents, data visualization, and interactive links.
- **Surface & Neutrals:** We use a hierarchy of off-whites (`#F8F9FA`) and sophisticated grays to create "tonal layering." Deep Slate (`#111827`) is used for high-contrast typography to ensure maximum readability.

## Typography

The typography system uses **Plus Jakarta Sans** for headings to provide a modern, friendly, and slightly geometric personality. High-level displays utilize tight letter spacing and heavy weights to create a "bold" editorial feel.

**Inter** is the workhorse for body copy and UI labels, chosen for its exceptional legibility in data-dense environments like registration lists and scoreboards. 

**Usage Notes:**
- Use `display-lg` for hero sections with tight leading.
- Use `label-lg` for section overlines or small categories, always in uppercase with increased tracking for a technical, organized appearance.

## Layout & Spacing

This design system utilizes an **8px linear grid** to ensure mathematical harmony across all components. 

### Grid Philosophy
- **Desktop:** 12-column fluid grid with a 1280px max-width. Large 24px gutters provide breathing room for complex data cards.
- **Tablet:** 8-column grid with 20px margins.
- **Mobile:** 4-column grid with 16px margins.

### Rhythm
We prioritize "Generous Whitespace." Sections should be separated by significant vertical gaps (`section-gap-lg`) to prevent cognitive overload. Within components, use consistent padding (e.g., 24px or 32px for cards) to create a sense of premium quality and organization.

## Elevation & Depth

We convey depth through a combination of **Tonal Layers** and **Ambient Shadows**.

1.  **Background (Level 0):** The primary canvas uses our neutral off-white.
2.  **Surface (Level 1):** White cards with a very soft, diffused shadow (15% opacity Electric Blue tint) to make them feel like they are floating just above the canvas.
3.  **Glassmorphism (Overlays):** For navigation bars and modal headers, use a backdrop blur (12px) with a semi-transparent white fill (80% opacity). This maintains context while providing a futuristic, high-fidelity look.
4.  **Interactive States:** On hover, elements should lift slightly (increase shadow spread) and may incorporate a subtle gradient stroke to emphasize the "Electric" brand nature.

## Shapes

The shape language is defined by **Soft Geometricism**. We avoid sharp corners to keep the platform welcoming, but use structured radii to maintain a professional SaaS feel.

- **Standard Components:** 0.5rem (8px) for buttons and inputs.
- **Large Containers (Cards):** 1rem (16px) to 1.5rem (24px) for event cards and feature containers.
- **Special Elements:** Use pill-shapes (full rounding) for status badges (e.g., "Live", "U15") to distinguish them from interactive buttons.

## Components

### Buttons
- **Primary:** Neon Lime background with Deep Slate text. Bold weight. This is the "Hero" action.
- **Secondary:** Electric Blue background with White text.
- **Ghost:** Electric Blue outline or text-only for less critical actions.

### Cards
Cards are the primary vehicle for event data. They must feature:
- 16px+ corner radius.
- Subtle 1px border in a slightly darker neutral to define edges on white backgrounds.
- Intentional grouping: Image at the top, metadata in small uppercase labels, and the title in Plus Jakarta Sans.

### Input Fields
Inputs should be clean with a 1px cool-gray border. On focus, the border transitions to Electric Blue with a soft blue glow (outer shadow).

### Status Badges
Small, high-contrast pills. For example, a "Live Now" badge uses a pulse animation with a Neon Lime dot to signify real-time activity.

### Chips & Tags
Used for categories (e.g., "Sports", "Academic"). Use a light tint of the Primary color with 60% opacity to keep them subordinate to main CTAs.