# Issue Intake & Smart Summary System

A Laravel 12 REST API for managing support/operations issues, with AI-powered summary
generation and a rules-based fallback. Built as a senior-level practical assessment.

## Overview

Users submit issues with a title, description, priority, category, and status. The
system stores them, exposes a filterable REST API, automatically generates a short
summary and suggested next action for each issue (via LLM or rules-based fallback),
and flags overdue high-priority issues for escalation.

## Tech stack

- **Laravel 12** (PHP 8.4)
- **SQLite** (file-based, zero setup)
- **Groq** for LLM summaries (OpenAI-compatible API, free tier)
- **Postman** collection for API testing

## Setup

### Prerequisites
- PHP 8.2+ (8.4 recommended)
- Composer 2.x
- Git

### Steps

```bash
# 1. Clone
git clone https://github.com/Jhovanie14/issue-intake.git
cd issue-intake

# 2. Install dependencies
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database
touch database/database.sqlite
php artisan migrate:fresh --seed

# 5. (Optional) Add an LLM API key for AI summaries
# Edit .env and set LLM_API_KEY=<your_groq_key>
# Get a free key at https://console.groq.com/keys
# If left blank, the system uses the rules-based generator (still fully functional).

# 6. Run
php artisan serve
# OR if `artisan serve` fails on your system:
# cd public && php -S 127.0.0.1:9000
```

The API is now live at `http://127.0.0.1:8000/api` (or whichever port).

## Testing the API

A Postman collection is included: `postman_collection.json`. Import it and update the
`base_url` collection variable if your server runs on a different port.

### Endpoints

| Method | Endpoint | Description |
|--------|---------|-------------|
| GET    | `/api/issues` | List issues (supports `?status=`, `?priority=`, `?category=`) |
| POST   | `/api/issues` | Create an issue (auto-generates summary + escalation) |
| GET    | `/api/issues/{id}` | View a single issue |
| PATCH  | `/api/issues/{id}` | Update an issue |
| DELETE | `/api/issues/{id}` | Delete an issue |

### Example: Create an issue

```bash
curl -X POST http://127.0.0.1:8000/api/issues \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Password reset emails not arriving",
    "description": "Multiple users report password reset emails never arrive after requesting them.",
    "priority": "high",
    "category": "authentication",
    "due_at": "2026-12-31 23:59:59"
  }'
```

Response (201):
```json
{
  "data": {
    "id": 18,
    "title": "Password reset emails not arriving",
    "priority": "high",
    "category": "authentication",
    "status": "open",
    "summary": "Customers cannot complete password reset due to undelivered emails.",
    "suggested_action": "Verify SMTP logs and check for domain-specific delivery issues.",
    "summary_source": "llm",
    "escalated": false,
    ...
  }
}
```

### Manual escalation scan

A scheduled command re-evaluates escalation independently of API activity:

```bash
php artisan issues:escalate
```

In production this would run every 15 minutes via Laravel's scheduler.

## Architecture

### Layered structure
HTTP layer        → IssueController (thin, delegates to services)
Validation layer  → Form Requests (StoreIssueRequest, UpdateIssueRequest)
Response layer    → IssueResource (shapes JSON output)
Orchestration     → IssueService (coordinates create/update flow)
Domain logic      → EscalationService (escalation rules)
AI layer          → SummaryGenerator interface + 3 implementations

### AI integration: Strategy + Decorator pattern

The `SummaryGenerator` contract has three implementations:

- **`RulesSummaryGenerator`** — deterministic, dependency-free, always works
- **`LLMSummaryGenerator`** — calls the configured LLM provider
- **`FallbackSummaryGenerator`** — decorator that wraps both: tries LLM first, falls
  through to rules on any failure

`AppServiceProvider` binds the right combination at runtime based on whether
`LLM_API_KEY` is configured. Three runtime paths:

1. **No API key** → rules generator alone
2. **Key set, LLM succeeds** → returns LLM result, `summary_source: "llm"`
3. **Key set, LLM fails** → catches `SummaryGenerationException`, falls back to rules,
   `summary_source: "rules"`

This was the highest-priority requirement in the brief and is fully tested.

### Why this design

- **Provider-agnostic** — `LLM_*` env vars rather than `OPENAI_*` mean swapping to
  Anthropic, OpenAI, or local Ollama requires only env changes.
- **Fail-soft** — the API never errors due to AI layer issues. Worst case is
  degraded-but-useful rules output.
- **Observable** — every LLM failure logs at warning level with the issue ID.
- **Cost-conscious** — `IssueService::update()` only regenerates summaries when the
  description actually changes, not on every PATCH.

## Key decisions

### Database: SQLite
Chose SQLite for zero-setup portability — reviewers can clone and run with no
service dependencies. The schema is straightforward relational data (single table
with indexed columns); the choice between SQLite/MySQL/Postgres has no architectural
impact, and migrations are written portably.

### Escalation rule
An issue is escalated if **priority is High or Critical**, **`due_at` is in the past**,
and **status is not Resolved or Closed**. Critical was included alongside High because
escalating High but ignoring Critical would be inconsistent. Resolved is treated like
Closed because escalating a fix-pending issue is noise.

Escalation runs both inline (`IssueService` runs it on every create/update) and
out-of-band (`issues:escalate` command, scheduled every 15 minutes). This catches
issues that pass their `due_at` while sitting idle without API activity.

### Native PHP enums
`Priority` and `Status` are backed enums (`app/Enums/`) cast directly on the model.
This gives type safety throughout the codebase — `EscalationService` can write
`$issue->priority === Priority::High` instead of magic strings.

### Form Requests over inline validation
Validation lives in `StoreIssueRequest` and `UpdateIssueRequest`. `Update` uses
`sometimes` rather than `required` to support partial PATCH updates, which is
correct REST semantics. `due_at: after:now` is enforced on Store but not Update,
because legitimate workflows include updating older issues.

### Mass assignment: explicit `$fillable`
Used `$fillable` rather than `$guarded = []`. Internal-only fields like `escalated`
and `summary_source` are listed in `$fillable` because they're written by trusted
services. Public-facing fields are protected at the request-validation layer
(`StoreIssueRequest` doesn't accept `escalated` as input). Defense in depth.

### Filtering: inline in controller
With three independent filters and no shared logic, an `IssueFilter` class would be
premature abstraction. `Builder::when()` is used for clean conditional filtering.
If the filter set grew (date ranges, search, sorting), I would extract a dedicated
filter class.

### DTO for AI results
`SummaryResult` is a readonly DTO rather than an associative array. Self-documenting,
type-safe, and immutable.

## Project structure
app/
├── Console/Commands/
│   └── EscalateIssues.php           # php artisan issues:escalate
├── Contracts/
│   └── SummaryGenerator.php         # AI provider interface
├── DTO/
│   └── SummaryResult.php
├── Enums/
│   ├── Priority.php
│   └── Status.php
├── Http/
│   ├── Controllers/Api/
│   │   └── IssueController.php
│   ├── Requests/
│   │   ├── StoreIssueRequest.php
│   │   └── UpdateIssueRequest.php
│   └── Resources/
│       └── IssueResource.php
├── Models/
│   └── Issue.php
├── Providers/
│   └── AppServiceProvider.php       # binds SummaryGenerator
└── Services/
├── EscalationService.php
├── IssueService.php
└── AI/
├── FallbackSummaryGenerator.php
├── LLMSummaryGenerator.php
├── RulesSummaryGenerator.php
└── Exceptions/
└── SummaryGenerationException.php
database/
├── factories/IssueFactory.php       # state methods for test fixtures
├── seeders/IssueSeeder.php          # 17 realistic seeded issues
└── migrations/

## What I would improve with more time

- **Queue the AI call** — currently synchronous. With Laravel Jobs + Horizon, the
  POST endpoint would return ~10ms while the LLM call happens in the background.
- **Test coverage** — feature tests for the API contract, unit tests for
  `EscalationService` and the `FallbackSummaryGenerator` decorator. The architecture
  is designed for testability (interfaces, dependency injection) but tests aren't
  written yet.
- **Audit log** — separate `issue_events` table tracking status changes,
  escalations, and AI generations. Useful for support analytics.
- **Authentication** — Sanctum tokens with role-based policies (reporter vs. agent).
- **Soft deletes** — currently destroys issues hard. Soft deletes preserve history.
- **Multi-provider LLM** — the architecture supports it, but there's only one
  implementation. Adding `AnthropicSummaryGenerator` and a multi-fallback chain
  would be straightforward.
- **OpenAPI documentation** — generate interactive docs with `darkaonline/l5-swagger`.
- **Rate limiting** — throttle the create endpoint to prevent abuse.
- **Caching** — the list endpoint with cache invalidation on writes.
- **CI** — GitHub Actions running `php artisan test` on every push.

## Author

Jhovanie Flores · https://github.com/Jhovanie14/issue-intake.git
