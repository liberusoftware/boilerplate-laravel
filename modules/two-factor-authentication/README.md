# Liberu Two-Factor Authentication

Provides role-aware TOTP enforcement configuration, hashed single-use recovery codes, replaceable audited administrator recovery, and opaque revocable trusted-device credentials with expiry. Enrollment screens and Fortify compatibility remain adapter responsibilities; material changes should revoke sessions and all trusted devices.

## Identity schema extension

This package explicitly requires `liberu/identity`. Laravel Fortify's two-factor trait requires encrypted secret, recovery-code, and confirmation fields on the authenticatable identity record. The extension is retained when disabled and is never removed by an ordinary uninstall.
