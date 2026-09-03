# uhifadhi/fixtures-module

Dev and demo data seeding for uhifadhi installations: the demo accounts and positions,
a fixed-uuid area from a GeoJSON boundary, the generic demo departments, and
the one-shot orchestrator that lays the baseline down in the right order. A
[uhifadhi](https://github.com/uhifadhilabs) module bundle.

> **Development tool.** This bundle exists to make a fresh checkout usable in
> one command. It seeds demo credentials from environment variables and is not
> intended to run against a production database.

## Contents

- [Charter](#charter)
- [Commands](#commands)
- [Installation](#installation)
- [Configuration](#configuration)
- [The demo baseline](#the-demo-baseline)
- [Development](#development)
- [License](#license)

## Charter

**A baseline, not a dataset.** This module seeds only what every installation needs
before anything else can be seen: people to log in as, one area to hang data
on, a module catalogue and the departments that read it. Domain data belongs
to the module that owns it — each installed module ships its own
`<module>:seed:*` commands and is run after the baseline.

**Idempotent and non-destructive.** Every command can be re-run. Nothing is
purged, renamed or detached, and a decision a human already made — a position
an admin placed, a department they renamed — is never overwritten.

**Never a real deployment.** The built-in demo area is an imaginary protected
area on the Antarctic coast, and the eight demo departments are ordinary
conservation vocabulary. No real organisation's boundary, org chart or domain
appears here. An installation that wants its own passes them in explicitly.

## Commands

| Command | What it seeds |
| --- | --- |
| `fixtures:all` | The whole baseline in dependency order — accounts, area, catalogue, departments. |
| `fixtures:accounts` | The demo accounts and the positions they hold. |
| `fixtures:area` | One area with a **fixed uuid**, so config addressing it by uuid survives a wipe. |
| `fixtures:departments` | The eight generic demo departments, their modules and the demo positions. |

`fixtures:all` runs the steps in the only order that works: accounts → area →
the host's module catalogue (which needs the area to backfill its modules) →
departments (which need the catalogue, to attach modules by slug).

Seed a real area instead of the imaginary one by passing an explicit boundary:

```console
$ php bin/console fixtures:area --uuid=<uuid> --name='<name>' --file=boundary.geojson
```

`fixtures:all` accepts the same options as `--area-uuid`, `--area-name` and
`--area-file`.

## Installation

```console
$ composer require --dev uhifadhi/fixtures-module
```

Register the bundle for the dev and test environments in `config/bundles.php`:

```php
Uhifadhi\Fixtures\UhifadhiFixturesBundle::class => ['dev' => true, 'test' => true],
```

## Configuration

`config/packages/fixtures.yaml`:

```yaml
fixtures:
    demo_password:        '%env(DEMO_PASSWORD)%'
    super_admin_password: '%env(DEMO_SUPER_ADMIN_PASSWORD)%'
    email_domain:         'uhifadhi.test'
```

| Key | Default | Meaning |
| --- | --- | --- |
| `demo_password` | `%env(DEMO_PASSWORD)%` | Shared password for the demo tier accounts. The command refuses placeholder values. |
| `super_admin_password` | `%env(DEMO_SUPER_ADMIN_PASSWORD)%` | Distinct password for the Super Admin, which can impersonate anyone. |
| `email_domain` | `uhifadhi.test` | Domain of the generated demo emails. |

**Passwords are never stored in this repository.** Both are read from the
environment, and `fixtures:accounts` refuses to run until they are set to
something real.

## The demo baseline

`fixtures:accounts` creates one account per tier, each at
`<local-part>@<email_domain>`:

| Email | Tier |
| --- | --- |
| `superadmin@` | Super Admin — its own distinct password |
| `admin@` | Admin |
| `manager@` | Manager |
| `ranger@` | Staff |
| `analyst@` | Staff |

Log in with any demo email and the `DEMO_PASSWORD` value.

## Development

```console
$ composer install
$ composer check   # cs:check + phpstan + test
```

The suite is unit-level and needs no database: the host's entities,
repositories and services are stubbed under `tests/Fixtures/Uhifadhi/`, so the
commands are exercised against the seam they actually depend on.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
