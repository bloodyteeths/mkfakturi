# GitHub Actions CI/CD Workflows

This directory contains GitHub Actions workflows for the InvoiceShelf project.

## Workflows

### `ci.yml` - Comprehensive CI/CD Pipeline

A complete CI/CD pipeline that includes:

- **Code Quality & Linting**: PHP Pint, ESLint, code style checks
- **Static Analysis**: PHPStan and Psalm for code quality analysis
- **Multi-Version Testing**: Tests against PHP 8.1, 8.2, 8.3 with MySQL and PostgreSQL
- **Security Scanning**: Composer audit, npm audit, Semgrep, CodeQL, Trivy
- **Asset Building**: Frontend asset compilation with Node.js 20
- **Docker**: Multi-platform image building and security scanning
- **Performance Tests**: Optional performance test execution
- **Deployment**: Staging and production deployment workflows

#### Triggers
- Push to `main`, `develop`, or `roadmap*` branches
- Pull requests to `main` or `develop`
- Manual workflow dispatch
- Weekly security scans (Mondays at 2 AM UTC)

#### Matrix Testing
- **PHP Versions**: 8.1, 8.2, 8.3
- **Databases**: MySQL 8.0, PostgreSQL 15
- **Platforms**: linux/amd64, linux/arm64 (Docker)

#### Environment Requirements
- Requires secrets for Codecov, Semgrep (optional)
- Staging and Production environments configured in GitHub

### `check.yaml` - Legacy Workflow

Simple workflow for basic testing (can be deprecated in favor of ci.yml).

## Setup Instructions

1. **Configure Secrets** (optional):
   - `CODECOV_TOKEN`: For code coverage reporting
   - `SEMGREP_APP_TOKEN`: For enhanced security scanning

2. **Configure Environments**:
   - Create `staging` and `production` environments in GitHub repository settings
   - Add required reviewers for production deployments

3. **Static Analysis Setup**:
   - PHPStan configuration: `phpstan.neon`
   - Psalm configuration: `psalm.xml`
   - Both configurations are already included

## Usage

### Automatic Triggers
- All pushes and PRs automatically trigger the appropriate jobs
- Security scans run weekly
- Production deployments require manual approval

### Manual Triggers
- Use GitHub Actions UI to manually trigger workflows
- Useful for testing specific branches or debugging issues

### Coverage Reports
- Coverage reports are generated for PHP 8.2 + MySQL combination
- Uploaded to Codecov if token is configured
- Minimum coverage threshold: 60%

### Security Features
- Composer dependency security audit
- npm package vulnerability scanning
- Semgrep static analysis security scanning
- CodeQL analysis for PHP and JavaScript
- Docker image vulnerability scanning with Trivy

## Performance Optimization

The workflow includes several optimizations:
- Dependency caching (Composer, npm)
- Parallel test execution
- Conditional job execution
- Artifact cleanup
- Multi-platform Docker builds with layer caching

## Troubleshooting

### Common Issues
1. **Test failures**: Check specific PHP version and database combination
2. **Security scan failures**: Review and update dependencies
3. **Build failures**: Verify Node.js dependencies and build process
4. **Docker issues**: Check Dockerfile and build context

### Debug Mode
- Enable debug logging in workflow runs for detailed output
- Use `continue-on-error: true` for non-critical steps

## Contributing

When modifying workflows:
1. Validate YAML syntax before committing
2. Test changes on feature branches first
3. Update this README for significant changes
4. Consider impact on CI/CD pipeline performance