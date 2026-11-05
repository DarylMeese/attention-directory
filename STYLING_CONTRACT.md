# Styling Contract: Attention Directory

The Attention Directory plugin is designed to be "Easily Stylable" by utilizing CSS Variables at the `:root` level. Themes and site builders can override these values without writing complex CSS selectors.

## Available CSS Variables

| Variable | Description | Default Value |
|----------|-------------|---------------|
| `--attention-gap` | Spacing between cards and elements | `1.5rem` |
| `--attention-card-radius` | Border radius for organization cards | `0.75rem` |
| `--attention-accent` | Primary accent color (buttons, links) | `#0f172a` |
| `--attention-card-bg` | Background color for cards | `#ffffff` |
| `--attention-card-border` | Border color for cards | `#e2e8f0` |
| `--attention-text-main` | Primary text color | `#1e293b` |
| `--attention-text-muted` | Muted/Secondary text color | `#64748b` |
| `--tenant-badge-open-bg` | Open-status badge background | `rgba(16,185,129,.1)` |
| `--tenant-badge-open-text` | Open-status text | `#059669` |
| `--tenant-badge-closed-bg` | Closed-status badge background | `rgba(239,68,68,.1)` |
| `--tenant-badge-closed-text` | Closed-status text | `#dc2626` |

## How to Override

Add the following to your WordPress Customizer (**Appearance > Customize > Additional CSS**) or your theme's `style.css`:

```css
:root {
    --attention-accent: #0073aa; /* WordPress Blue */
    --attention-card-radius: 0;   /* Square corners */
    --attention-card-bg: #fdfdfd;
}
```

## HTML Structure

| Component | Selector | Notes |
|-----------|----------|-------|
| Main Container | `.attention-directory-container` | Wraps all directory outputs |
| Grid Layout | `.attention-grid` | Uses dynamic grid Columns |
| Individual Card | `.attention-card` | Handles hover effects and padding |

Cards shrink to the available width on narrow screens. Search fields have explicit labels, action targets have a 44-pixel minimum height, and keyboard focus remains visible. No animation or dependency on a particular WordPress theme is required.
