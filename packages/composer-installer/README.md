# Liberu Composer Installer

Composer 2 plugin that validates stable kebab-case package installer names and deterministically places `liberu-module` packages in `/modules/{name}` and `liberu-theme` packages in `/themes/{name}`. Absolute paths and traversal cannot be expressed, target collisions fail, and normal dependencies remain under `/vendor`.
