# Liberu Settings

Owns typed application/organization/team/user definitions, explicit precedence, validation, encrypted secret separation, durable values, audit integration points, and cache invalidation boundaries.

Typed settings owned by the foundation. The host registers module settings migrations and may compose application, organization, team/site, and user scopes. Secrets are intentionally excluded from generic settings and belong in protected credential storage. Updates must be authorized, audited, validated, and invalidate caches deterministically.
