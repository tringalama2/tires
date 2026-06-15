# Tread Mark — Favicons

A complete favicon set generated from the Trail Tracks mark, covering every browser, iOS,
Android, and PWA install surface. The brand mark sits on the pine `#0F1410` tile with the
top chevron in blaze `#FF7A2E`.

## Files

| File | Size | Use |
|------|------|-----|
| `favicon.ico` | 16·32·48 (multi-res) | Legacy browsers, address bar, bookmarks |
| `favicon.svg` | scalable | Modern browsers (crisp at any DPI; preferred) |
| `favicon-16x16.png` | 16 | Fallback |
| `favicon-32x32.png` | 32 | Fallback, taskbar |
| `favicon-48x48.png` | 48 | Windows site tiles |
| `favicon-96x96.png` | 96 | Android/desktop shortcuts |
| `apple-touch-icon.png` | 180 | iOS home-screen (full-bleed; iOS rounds it) |
| `android-chrome-192x192.png` | 192 | Android home-screen |
| `android-chrome-512x512.png` | 512 | PWA splash |
| `maskable-512x512.png` | 512 | Android adaptive (safe-zone padded) |
| `site.webmanifest` | — | PWA manifest referencing the above |

## Install — paste into your `<head>`

```html
<link rel="icon" href="/assets/favicon/favicon.ico" sizes="any">
<link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png">
<link rel="manifest" href="/assets/favicon/site.webmanifest">
<meta name="theme-color" content="#0F1410">
```

For Laravel/Blade, drop the files in `public/assets/favicon/` and use the same paths (or
`{{ asset('assets/favicon/...') }}`). `favicon.ico` is conventionally also copied to the web
root so default `/favicon.ico` requests resolve.

## Regenerate

The raster files are drawn from the mark in a canvas script (background `#0F1410`, chevrons
`#FFFFFF`/`#FFFFFF`/`#FF7A2E`, round caps). To change the look, re-run that generation step;
keep `favicon.svg` and these PNGs in sync.
