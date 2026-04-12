# Versioning and Release Process

This repository uses Semantic Versioning (SemVer):

- MAJOR.MINOR.PATCH
- MAJOR: breaking or incompatible changes
- MINOR: backward-compatible feature additions
- PATCH: backward-compatible bug fixes

## Current Baseline

- Current stable target: v1.0.0
- Primary branches:
- main: stable/demo-ready releases
- Integration: active integration branch

## Release Workflow

1. Build and validate on Integration
- Complete feature work and testing in Integration
- Confirm critical flows (tenant lifecycle, RBAC gates, billing, dashboards)

2. Merge Integration to main
- Merge only when stable for presentation/deployment

3. Tag a release on main
- Create annotated tag:

```bash
git checkout main
git pull
git tag -a v1.0.0 -m "Release v1.0.0: Multi-tenant SaaS baseline"
git push origin main
git push origin v1.0.0
```

4. Publish GitHub Release Notes
- Use CHANGELOG.md as the source for release notes
- Include Added, Changed, Fixed sections for each version

## Version Bump Rules For This Project

- Bump PATCH (x.x.+1):
- UI fixes, small bug fixes, non-breaking controller/view updates

- Bump MINOR (x.+1.0):
- New tenant module capability, new role workflow, non-breaking API/routes additions

- Bump MAJOR (+1.0.0):
- Breaking route contracts, incompatible schema/data behavior, major auth/RBAC contract changes

## Suggested Next Versions

- v1.0.0: Stable baseline for defense/demo
- v1.1.0: Additional tenant modules or analytics enhancements
- v1.1.1: Bug fix updates after QA/demo feedback
