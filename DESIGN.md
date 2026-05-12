# UI/UX Guide - Zubuz Design System

This comprehensive guide documents all UI components, styles, and patterns available in the **Berbaris App** project, based on the **Zubuz** template.

---

## 1. Core Design Tokens

### Color Palette
Defined in `assets/css/app.css`.

| Token | Variable | Value | Usage |
| :--- | :--- | :--- | :--- |
| **Primary** | `--primary-color` | `#2332DD` | Main brand color, primary actions. |
| **Secondary** | `--secondary-color` | `#ABFB4F` | Accent color, neon highlights. |
| **Heading** | `--heading-color` | `#000000` | All titles and headings. |
| **Body** | `--body-color` | `#0C0C0C` | Standard text content. |
| **Dark BG** | `--dark-bg` | `#000000` | Dark section backgrounds. |
| **Light BG** | `--light-bg` | `#FAFAFA` | Default light section backgrounds. |
| **Gray** | `--gray-color` | `#414141` | Borders, meta text, muted labels. |

### Typography
- **Primary Font:** `DM Sans`, sans-serif (Weights: 400, 500, 600, 700)
- **Usage:** Applied globally to both Headings and Body content for a unified modern look.

---

## 2. Navigation & Layout

### Site Header
- **Menu Center:** `.site-header--menu-center`
- **Sticky Menu:** `#sticky-menu` (requires `assets/js/menu/menu.js`)
- **Transparent/Dark Mode Header:** Add `.dark-bg.white-menu` classes.

### Breadcrumbs
```html
<div class="zubuz-breadcrumb">
  <div class="container">
    <h1 class="post__title">Page Title</h1>
    <nav class="breadcrumbs">
      <ul>
        <li><a href="index.html">Home</a></li>
        <li aria-current="page"> Current Page</li>
      </ul>
    </nav>
  </div>
</div>
```

### Footer
Standard multi-column footer with subscription form.
```html
<footer class="zubuz-footer-section main-footer">
  <div class="container">
    <div class="zubuz-footer-top">...</div>
    <div class="zubuz-footer-bottom">
      <div class="zubuz-social-icon">...</div>
      <div class="zubuz-copywright">...</div>
    </div>
  </div>
</footer>
```

---

## 3. Interaction Components

### Buttons
- **Default:** `.zubuz-default-btn`
- **Header Variant:** `.zubuz-header-btn`
- **Login Variant:** `.zubuz-login-btn`
- **Pricing Variant:** `.zubuz-pricing-btn` (add `.active` for highlighted plan)

### Accordions (FAQ)
Uses `assets/js/faq.js`.
```html
<div class="zubuz-accordion-wrap" id="zubuz-accordion">
  <div class="zubuz-accordion-item open">
    <div class="zubuz-accordion-header">
      <h3>Question?</h3>
      <div class="zubuz-active-icon"><svg>...</svg></div>
    </div>
    <div class="zubuz-accordion-body"><p>Answer...</p></div>
  </div>
</div>
```

### Pricing Tables
- **Container:** `.zubuz-pricing-four-column`
- **Card:** `.zubuz-pricing-wrap` (add `.active` for the featured plan)
- **Feature Comparison Table:** `.zubuz-table-wrap` wrapping a standard `<table>`.

---

## 4. Content Components

### Icon Boxes
Versatile components for features or contact info.
- **Center Aligned:** `.zubuz-iconbox-wrap.center`
- **Left Aligned:** `.zubuz-iconbox-wrap-left`
- **Variants:** Add `.light` for dark backgrounds, `.data-small` for compact data.

### Testimonials
```html
<div class="zubuz-testimonial-wrap">
  <div class="zubuz-testimonial-rating"><ul><li><img src="star.svg"></li>...</ul></div>
  <div class="zubuz-testimonial-data"><h3>Review Title</h3><p>"Content"</p></div>
  <div class="zubuz-testimonial-author">
    <div class="zubuz-testimonial-author-thumb"><img src="user.png"></div>
    <div class="zubuz-testimonial-author-data"><span>Name</span><p>Role</p></div>
  </div>
</div>
```

### Blog Cards
```html
<div class="zubuz-blog-wrap">
  <div class="zubuz-blog-thumb">
    <img src="blog.png">
    <div class="zubuz-blog-categorie">Category</div>
  </div>
  <div class="zubuz-blog-data">
    <p>Date</p>
    <h3>Title</h3>
    <a class="zubuz-blog-btn" href="#"><svg>...</svg></a>
  </div>
</div>
```

---

## 5. Forms & Inputs

### Standard Form
```html
<div class="zubuz-form-wrap">
  <form>
    <div class="zubuz-main-form"><input type="text" placeholder="Name"></div>
    <div class="zubuz-main-form"><textarea placeholder="Message"></textarea></div>
    <button id="zubuz-submit-btn" type="submit"><span>Submit</span></button>
  </form>
</div>
```

### Subscribe Form (Footer)
```html
<div class="zubuz-subscribe-one">
  <form>
    <input type="email" placeholder="Email Address">
    <button class="zubuz-default-btn zubuz-subscription-btn one" type="submit">
      <span>Subscribe</span>
    </button>
  </form>
</div>
```

---

## 6. Utilities & Helpers

### Spacing Utilities
- **Padding:** `.zubuz-section-padding`, `.zubuz-section-padding2` (standard), `.zubuz-section-padding3` (large).
- **Margin Bottom:** `.rt-mb-24`, `.rt-mb-15`, `.rt-mb-8`.

### Text Utilities
- **Font Size:** `.f-size-12` to `.f-size-40`
- **Font Weight:** `.font-bold` (700), `.font-semibold` (600), `.font-medium` (500), `.font-normal` (400).
- **Line Height:** `.line-height-10` to `.line-height-40`.

### Miscellaneous
- **Preloader:** `.zubuz-preloader-wrap` (automated via `assets/js/app.js`).
- **Dividers:** `.zubuz-divider` (subtle horizontal line).
- **Social Icons:** `.zubuz-social-icon` with `<ul>` and FontAwesome icons.

---

## 7. Assets Reference

| Type | Path |
| :--- | :--- |
| **Main CSS** | `public/templates/zubaz/assets/css/main.css` |
| **Base CSS** | `public/templates/zubaz/assets/css/app.min.css` |
| **Main JS** | `public/templates/zubaz/assets/js/app.js` |
| **Bootstrap** | `public/templates/zubaz/assets/css/bootstrap.min.css` |
| **Icons** | FontAwesome 5 & Icomoon |
