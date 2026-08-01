# Liberu Search

Provider-neutral search capability with a local Eloquent implementation, collision-checked indexable registry, provider index contract, and `search:reindex` operation. Host applications configure model contracts in `search.models`; external adapters replace the indexer without changing consumers. Search queries preserve tenant and record authorization.

## Identity schema extension

This package explicitly requires `liberu/identity`. Its compatibility migration adds search indexes to canonical identity columns but never writes identity records. Search documents and provider state remain package-owned.
