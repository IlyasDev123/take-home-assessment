You are a Senior Product Engineer with deep expertise in Laravel, PHP 8.4, Vue 3, Inertia.js, distributed systems, API integrations, payment systems, and scalable SaaS architecture.

I need you to help me build a production-quality application for a technical assessment.

The goal is to build a professional AI image generation platform using:

Backend:
- Laravel 13
- PHP 8.4
- MySQL/PostgreSQL
- Laravel Queue
- Laravel Events & Listeners
- Laravel Scheduler
- Laravel Notifications
- Laravel Policies
- Laravel Service Container
- Dependency Injection
- REST API best practices

Frontend:
- Vue 3 Composition API
- Inertia.js
- TypeScript
- Tailwind CSS
- Modern responsive UX

Development standards:
- SOLID principles
- PSR-12 coding standards
- Clean Architecture principles
- Domain-driven thinking
- DRY principles
- Separation of concerns
- Repository pattern where useful
- Service layer architecture
- Strong typing
- Proper exception handling
- Meaningful naming conventions
- Production-ready code quality

Do NOT create a simple demo application.
Build it like a real SaaS product that could go into production.

====================================================

PROJECT REQUIREMENT:

Build an AI image generation platform integrated with a fictional provider called Pixelforge.

Users should:

1. Register/login
2. Create image generations
3. Enter:
   - Prompt
   - Image size
   - Quality
   - Reference image URLs

Before submitting:
- Show estimated generation cost
- Explain that final cost may change after completion

The backend sends requests to Pixelforge API.

Pixelforge generation process:

- Requests are asynchronous.
- API returns generation ID.
- Generation completes later.
- Completion happens through:
    - Webhooks
    - Polling fallback

The application must correctly handle:

- Duplicate webhooks
- Out-of-order webhooks
- Failed generations
- Rate limits
- API failures
- Timeouts
- Provider downtime

====================================================

ARCHITECTURE REQUIREMENTS:

Use a professional Laravel architecture.

Suggested structure:

app/

Domain/
    Generation/
        Models/
        Services/
        Actions/
        DTOs/
        Events/
        Exceptions/

Domain/
    Billing/
        Services/
        ValueObjects/

Infrastructure/
    Pixelforge/
        Client/
        DTO/
        Webhooks/

Application/
    Services/

Http/
    Controllers/
    Requests/
    Resources/

Jobs/
Listeners/
Policies/

====================================================

DATABASE DESIGN:

Design proper migrations.

Tables:

users

generations

Fields:

id
user_id
provider_generation_id
prompt
size
quality
reference_images JSON
status

Statuses:

queued
processing
succeeded
failed

estimated_provider_cost
actual_provider_cost

estimated_user_cost
actual_user_cost

image_url

completed_at

timestamps


generation_webhook_events

Fields:

id

provider_event_id

generation_id

event_type

payload JSON

processed_at

timestamps


credits (stretch goal)

Fields:

user_id

balance

transactions


====================================================

MONEY HANDLING:

This is extremely important.

Never use floating point for money.

Use:

- Decimal database fields
- Money value objects
- Integer cents where appropriate

Requirements:

Provider cost:
$0.00123

User charge:

provider cost + 25%

Example:

Provider:
$0.0245

Markup:
25%

User:
$0.030625


Handle rounding consistently.

Create:

MoneyService

responsible for:

- calculations
- rounding
- formatting

====================================================

PIXELFORGE INTEGRATION:

Create a dedicated integration layer.

Example:

PixelforgeClient

Methods:

generate()

getGeneration()

getUsage()

verifyWebhookSignature()


Do not put API calls inside controllers.


Use:

Interface:

ImageGenerationProviderInterface


Implementation:

PixelforgeProvider


So another AI provider can easily be added later.

====================================================

WEBHOOK SYSTEM:

Implement secure webhook handling.

Requirements:

- Verify signature
- Store raw event
- Prevent duplicate processing
- Support retries
- Handle events arriving in wrong order

Use:

WebhookController

WebhookProcessorService

Database transaction

Idempotency handling


Example:

Webhook arrives:

generation.completed


Check:

Has this event already been processed?

If yes:
ignore safely.

If no:

store event

update generation state

calculate final cost

save result


====================================================

QUEUE SYSTEM:

Generation requests should not block users.

Flow:

User submits generation

↓

Create database record

↓

Dispatch Job:

SendGenerationToPixelforge


↓

Pixelforge returns ID

↓

Update status queued


Use:

Laravel Jobs

Retries

Backoff strategy

Failed jobs handling


====================================================

UX REQUIREMENTS:

Create a beautiful SaaS dashboard.

Pages:

1. Dashboard

Show:

- Total generations
- Credits spent
- Successful generations


2. Create Generation Page

Features:

- Prompt textarea
- Size selector
- Quality selector
- Reference URL input

Live cost estimation.

Clear explanation:

"Estimated cost. Final cost calculated after generation completes."


3. History Page

Display:

Cards/table:

Image

Prompt

Status badge

Estimated cost

Final cost

Created date


Auto refresh processing generations.


4. Generation Detail Page

Show:

Timeline:

Created

Submitted

Processing

Completed


====================================================

STRETCH FEATURES:

Implement if architecture allows:

ADMIN DASHBOARD

Show:

Daily:

- User charges
- Provider costs
- Profit

Per user:

- Total spend
- Number of generations


Credits:

Users can:

- Buy fake credits
- View balance
- Cannot generate if insufficient balance


====================================================

TESTING REQUIREMENTS:

Write automated tests.

Feature tests:

- User can create generation
- Cost calculation works
- Failed generation costs zero
- Duplicate webhook ignored
- Invalid webhook rejected
- Out-of-order events handled


Unit tests:

- Money calculations
- Pixelforge signature verification
- Status transitions


Use:

PHPUnit / Pest


====================================================

SECURITY:

Implement:

- Authentication
- Authorization policies
- Validation Form Requests
- API key protection
- Environment variables
- Rate limiting
- CSRF protection


Never expose:

API keys

Webhook secrets


====================================================

CODE QUALITY:

For every implementation:

Explain:

1. Why this design was chosen
2. What SOLID principles are applied
3. Alternative approaches
4. Trade-offs


Before writing code:

First create:

1. System architecture diagram
2. Database schema
3. Folder structure
4. Development roadmap

Then implement step by step.


Do not generate everything at once.

Work like a senior engineer reviewing production code.