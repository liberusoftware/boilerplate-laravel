# Upgrading

## 1.x → 2.0

**The theme is now named `base`. It was `liberu-base`.**

The Composer package name is unchanged — it was already `liberusoftware/theme-base`, and that is
what 2.0 makes the manifest agree with. `standards/THEMES.md` derives both the repository name and
the install path from one `{name}`, and this theme carried three different ones.

A theme's name is a stable manifest surface, so this is a major release.

### What changes for you

| | Before | After |
| --- | --- | --- |
| Install path | `themes/liberu-base` | `themes/base` |
| `theme.json` `name` | `liberu-base` | `base` |
| `composer.json` `extra.liberu.name` | `liberu-base` | `base` |
| Composer package | `liberusoftware/theme-base` | unchanged |

### What you must update

1. **Any theme declaring this one as its parent:**

   ```json
   { "parent": "base" }
   ```

2. **Theme selection by name** — `config/theme.php` (`default`, `fallback`, per-surface entries),
   the `THEME_*` environment variables, and any stored site setting whose value is `liberu-base`.

3. **Relative asset imports.** A child theme importing the shared root by path crosses the install
   directory, so the path moves with it:

   ```css
   @import '../../../base/resources/css/app.css';
   ```

4. **Rebuild.** `vite.config.js` derives its inputs from `themes/*/theme.json`, so the directory
   rename changes the input set: run `npm run build`.

### While the wave is landing

`ThemeDiscovery` merges the tracked tree with Composer's installed theme packages and dedupes by
`realpath`. Between updating this package and updating a child theme, the installed copy sits at
`themes/base` while a stale tracked copy may still sit at `themes/liberu-base` — they will not
dedupe, so expect two themes until the whole wave is installed. Remove the stale directory.
