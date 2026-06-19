# Propitix Tooling Website

A Dockerized PHP web application that serves as a DevOps dashboard, linking to internal tooling (Jenkins, Grafana, Rancher, Prometheus, Kubernetes metrics, Kibana, Artifactory). It includes login and admin user management backed by a MySQL database.

## Stack

| Component | Details |
|---|---|
| Runtime | PHP 8.2 + Apache (`php:8.2-apache`) |
| Database | MySQL (via `mysqli` extension) |
| Container registry | AWS ECR (`eu-central-1`) |
| CI/CD | Jenkins (multibranch pipeline) |
| Target platform | Kubernetes (DB reached via `mysql.tooling.svc.cluster.local`) |

## Architecture

```
Browser → Apache (port 80) → PHP app → MySQL (K8s service)
```

The app is designed to run as a container in Kubernetes. The database host is resolved via Kubernetes service DNS.

## Environment Variables

Configure these at runtime (via K8s Secret or Docker `-e`):

| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `mysql.tooling.svc.cluster.local` | MySQL hostname |
| `DB_USER` | `admin` | MySQL username |
| `DB_PASS` | `admin` | MySQL password |
| `DB_NAME` | `tooling` | MySQL database name |
| `PORT` | `80` | Port Apache listens on |

## Database Setup

Import the schema and seed data:

```bash
mysql -u <user> -p <database> < tooling-db.sql
```

**Important:** The seed admin user password in `tooling-db.sql` is a placeholder. Before deploying, generate a real bcrypt hash and update the INSERT:

```php
echo password_hash('your_password', PASSWORD_BCRYPT);
```

## Running Locally

```bash
docker build -t tooling-app .
docker run -p 8080:80 \
  -e DB_HOST=<mysql_host> \
  -e DB_USER=<user> \
  -e DB_PASS=<password> \
  -e DB_NAME=tooling \
  tooling-app
```

Then open `http://localhost:8080`.

## CI/CD Pipeline

The `Jenkinsfile` defines a multibranch pipeline that builds a Docker image and pushes it to AWS ECR:

| Branch / Tag | ECR tag |
|---|---|
| `dev` | `dev-<BUILD_NUMBER>` |
| `staging` or `master` | `staging-<BUILD_NUMBER>` |
| `release-*` | `prod-<BUILD_NUMBER>` |

**Required Jenkins credentials:**
- `GITHUB_CREDENTIALS` — GitHub personal access token

**Required Jenkins plugins:**
- Docker Pipeline
- AWS CLI (configured with ECR push permissions)

## Security Notes

- Passwords are hashed with bcrypt (`password_hash` / `password_verify`)
- All SQL queries use prepared statements
- DB credentials are injected via environment variables, not hardcoded
- Apache is configured with `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, and `Referrer-Policy` headers
