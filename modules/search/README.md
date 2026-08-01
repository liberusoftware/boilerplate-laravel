# Liberu Search

Provider-neutral search capability with a local Eloquent implementation, collision-checked indexable registry, provider index contract, and `search:reindex` operation. Host applications configure model contracts in `search.models`; external adapters replace the indexer without changing consumers. Search queries preserve tenant and record authorization.
