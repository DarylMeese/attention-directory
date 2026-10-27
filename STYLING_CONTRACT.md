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
| `--attention-badge-bg` | Background for status badges | `#f1f5f9` |
| `--attention-badge-text` | Text color for status badges | `#475569` |

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
