# Fakturino v1 - Roadmap

**⚠️ This roadmap has been consolidated into a single comprehensive document.**

**👉 See [MASTER_ROADMAP.md](./MASTER_ROADMAP.md) for the complete implementation plan.**

---

## What's in MASTER_ROADMAP.md

The master roadmap contains everything in one place:

### 📦 Integration Strategy
- Selected third-party packages (MIT/Apache-2.0 only)
- Repository URLs, licenses, commit hashes
- Integration adapters for easy swapping

### 🚀 9 PR-Sized Implementation Steps
1. **Foundation** - Feature flags (4h)
2. **Accounting** - eloquent-ifrs integration (16h)
3. **Migration Wizard** - Laravel Excel (20h)
4. **Paddle Payments** - Official Laravel package (12h)
5. **CPAY Payments** - Custom driver (8h)
6. **PSD2 Banking** - OAuth + MT940 fallback (24h)
7. **Partner APIs** - Keep mocked data ON (16h)
8. **MCP AI Tools** - TypeScript server (32h)
9. **Monitoring** - Prometheus + Telescope (8h)

### 🚂 Railway Deployment
- Multi-service architecture (api, worker, scheduler, mcp-server)
- PostgreSQL + Redis add-ons
- Health checks and monitoring
- Environment variables
- Volume/storage configuration

### ✅ Staging Validation
- 7 end-to-end test scenarios
- Sign-off criteria before production
- Rollback procedures

### 📋 Integration Tracking
- License compliance
- Commit hash pinning
- Swap-out strategies for each package

---

## Quick Start

1. Read [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)
2. Start with **STEP 0: Foundation** (feature flags)
3. Follow the 9 PRs in order
4. Run staging validation checklist
5. Deploy to Railway

---

## Key Principles

✅ **Fork battle-tested packages** (not green-field builds)
✅ **Feature flags for everything** (safe rollback)
✅ **Keep partner mocked data ON** (until staging sign-off)
✅ **PR-sized chunks** (~500 LOC each)
✅ **Tests for all new code**
✅ **MIT/Apache-2.0 licenses only** (no GPL)

---

**Ready to start?** 👉 [MASTER_ROADMAP.md](./MASTER_ROADMAP.md)
