# Security Policy - Liberu Boilerplate

## Reporting a Vulnerability

We take security seriously at Liberu Boilerplate. If you discover a security vulnerability, please report it responsibly.

**Do not open a public GitHub issue for security vulnerabilities.**

Instead, please report vulnerabilities through GitHub's private advisory feature:
- Navigate to the repository's **Security** tab
- Click **"Report a vulnerability"**
- Provide a detailed description of the issue, including steps to reproduce

You will receive an acknowledgment of your report within a reasonable timeframe.

## What to Expect

- **Volunteer-maintained**: Liberu Boilerplate is maintained by volunteers. There is no guaranteed response time or SLA for security reports.
- **Disclosure**: We follow responsible disclosure practices. Once a fix is released, we will credit reporters unless they prefer to remain anonymous.
- **Scope of response**: We will investigate all legitimate reports and release patches for confirmed vulnerabilities in a timely manner.

## Scope

The following components are in scope for security reports:

- `app/` - Application code (Models, Controllers, Services, Jobs, Events, Listeners)
- `routes/` - Route definitions and middleware
- `database/` - Migrations, seeders, and factories
- `config/` - Configuration files
- `resources/` - Views, frontend assets, and language files
- `tests/` - Test infrastructure
- Publicly accessible API endpoints
- Module registration and discovery logic
- Service provider architecture
- Authentication and authorization scaffolding

## Out of Scope

The following are not considered vulnerabilities:

- **Third-party dependencies**: Issues in Composer or npm packages should be reported to the respective maintainers. We monitor and update dependencies regularly.
- **Production misconfigurations**: Issues caused by running the application with `APP_DEBUG=true` in production, or other insecure configurations that are documented as such.
- **Known issues**: Issues already documented as known limitations in the repository.
- **Theoretical issues**: Issues that cannot be demonstrated to have a real-world security impact.
- **Social engineering**: Attacks targeting users through phishing, credential theft, or other social engineering vectors.
- **Denial of service**: Volumetric DDoS attacks or other resource exhaustion attacks against the hosting infrastructure.

## Supported Versions

Security updates are applied to the latest release. We recommend always running the most recent version of Liberu Boilerplate to ensure you receive all security patches.

If you are running an older version, please upgrade to the latest release before reporting a vulnerability that may have already been fixed.
