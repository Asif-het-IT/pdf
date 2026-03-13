# Architecture

## Overview
het PDF Tools is a modular internal utility platform built for cPanel/shared-hosting constraints.

## Layers
- public/: route entrypoints and guarded pages
- app/Controllers: request orchestration
- app/Services: validation, tooling, processing, logging
- app/Core: bootstrap, auth, csrf, response, db
- app/Models: DB persistence
- app/Views: UI templates
- storage/: temp/jobs/logs/exports (non-public)

## Request Flow
1. Request enters public route.
2. Bootstrap starts session, security headers, config.
3. Auth guard validates session/token.
4. Controller validates CSRF and input.
5. Service executes tool pipeline.
6. Job metadata persisted under storage/cache/jobs.
7. Signed token download served via download route.

## Modularity
New tools can be added by:
1. Adding one method in ToolController.
2. Adding processor logic in ToolboxService.
3. Adding dashboard card + tool view input block.
