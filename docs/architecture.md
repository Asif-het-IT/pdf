# Architecture (v2)

## Product Structure
het Document Platform follows modular enterprise layering while preserving cPanel compatibility.

## Layers
- Frontend UI layer: PHP views + JS polling UX
- Backend API layer: `/public/api/jobs/*`
- Processing layer: `DocumentProcessorService`
- Queue layer: `JobQueueModel` + `QueueService`
- Storage layer: DB `job_files` + private filesystem
- Admin module: diagnostics, analytics, user governance

## Core Runtime Flow
1. Authenticated user submits tool form
2. Request stored as queued job in DB
3. Worker tick claims queued job
4. Processor runs Ghostscript/ImageMagick pipeline
5. Progress and stage updated in DB
6. Output persisted + mapped in `job_files`
7. User polls status and receives secure download token

## Compatibility Strategy
- Current shared hosting: DB queue + poll-triggered tick
- Future VPS: dedicated worker daemons using same service contracts
- Future cloud: replace local path adapter with object storage key adapter

## Security Boundaries
- Auth guard on all tool/admin routes
- Per-user ownership checks in queue/download path
- CSRF checks on state-changing requests
- Signed, expiring download tokens
