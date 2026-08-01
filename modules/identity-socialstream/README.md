# Liberu Identity Socialstream

Provider adapter for social identity linking. It owns connected-account persistence, policy, actions, migrations, and factory. Provider credentials remain protected and provider-specific behavior does not leak into product modules. The host identity composition implements `ConnectedAccountOwner` and supplies its configured authentication model.

## Identity schema extension

This adapter explicitly requires `liberu/identity`. Social identities remain in its owned `connected_accounts` table; its compatibility migration only relaxes the canonical password field for provider-only accounts. Disabling the adapter preserves linked-account data.
