# Liberu Roles and Permissions

Owns contextual role and permission models, migrations, declaration registry, policies, cache boundaries, time-bounded strongly authenticated break-glass grants, and separation-of-duty checks. Permissions declared by consuming modules use `{module}.{resource}.{action}`. Authorization remains server-side and deny-by-default across every delivery path; UI visibility is never sufficient. Super-admin and impersonation exceptions are explicit and separately audited.
