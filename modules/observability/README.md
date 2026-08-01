# Liberu Observability

Owns correlation propagation across HTTP/context, structured redaction, replaceable metrics, SLO registration hooks, release-aware readiness integration points, exception/trace adapters, and securely gated Horizon, Telescope, and Pulse dashboards. The default metrics adapter is intentionally no-op until deployment infrastructure is bound.

Owns Horizon, Telescope, and Pulse integration, monitoring storage, production redaction, selective capture, and authorized dashboard gates. Host actors implement `ObservabilityActor`; telemetry must never expose credentials or unrestricted personal data. Release metadata, correlation IDs, metrics/traces, SLO hooks, and operational runbooks remain stable extension points.
