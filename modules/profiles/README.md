# Liberu Profiles

Owns controlled profile updates and preference fields. The host identity model remains the composition point; presentation adapters may supply avatar/contact UI.

## Identity schema extension

This package explicitly requires `liberu/identity`. Its compatibility migrations add locale, timezone, and theme-preference fields to the canonical identity record. Disabling the package retains these fields and uninstall never drops production data automatically.
