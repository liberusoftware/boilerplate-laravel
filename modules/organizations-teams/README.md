# Liberu Organizations and Teams

Owns durable organizations, active/effective membership, address-bound expiring single-use invitations, trusted current-context resolution, ownership transfer, lifecycle status, and revocation-ready boundaries. Switching context never grants membership or permission.

Owns team, membership, invitation, ownership, and policy models behind the `OrganizationActor` boundary. Host identity models implement that boundary. Context switching never grants permissions; consuming jobs and requests must validate active membership at their trusted boundary.
