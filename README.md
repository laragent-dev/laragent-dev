<p align="center">
  <img src="art/logo-wordmark.svg" alt="Laragent" width="280">
</p>

<p align="center">
  <a href="https://packagist.org/packages/laragent-dev/laragent-dev"><img src="https://img.shields.io/packagist/v/laragent-dev/laragent-dev" alt="Packagist Version"></a>
  <a href="https://packagist.org/packages/laragent-dev/laragent-dev"><img src="https://img.shields.io/packagist/dt/laragent-dev/laragent-dev" alt="Total Downloads"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4" alt="PHP"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%2B-FF2D20" alt="Laravel"></a>
  <a href="https://github.com/laragent-dev/laragent-dev/actions"><img src="https://github.com/laragent-dev/laragent-dev/workflows/Tests/badge.svg" alt="Tests"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-38BDF8" alt="License"></a>
</p>

<p align="center"><strong>Autonomous AI Agents for Laravel. Runs locally for free. No API keys required.</strong></p>

---

## Why Laragent?

- **Free by default** — runs on [Ollama](https://ollama.ai) locally, no API key needed
- **Laravel-native** — uses facades, events, queues, and Eloquent you already know
- **Ships fast** — `composer require` + `php artisan laragent:install` and you're running

---

## Requirements

### For local AI (free, recommended)
- [Ollama](https://ollama.ai) installed and running

| Model | RAM | Disk | Best For |
|---|---|---|---|
| `llama3.2` | 8GB | 3GB | General tasks |
| `qwen2.5-coder` | 16GB | 4GB | Code generation |
| `mistral` | 8GB | 4GB | Reasoning |
| `deepseek-r1:7b` | 16GB | 5GB | Complex reasoning |

### For cloud AI (optional, bring your own key)
- Anthropic API key — [console.anthropic.com](https://console.anthropic.com)
- OpenAI API key — [platform.openai.com](https://platform.openai.com)
- Or any OpenAI-compatible API (Groq, Together AI, Mistral — all have free tiers)

---

## Installation

```bash
composer require laragent-dev/laragent-dev
php artisan laragent:install
```

The installer guides you through everything: choosing a provider, installing Ollama if needed, pulling a model, running migrations, and verifying it works.

---

## Quick Start

**Ask anything:**
```php
use Laragent\Facades\Agent;

$result = Agent::run('Summarize what our app does based on the database structure');
echo $result->answer;
```

**With database access:**
```php
$result = Agent::tools(['database'])
    ->run('How many users signed up this week compared to last week?');
```

**Automate email outreach:**
```php
$result = Agent::tools(['database', 'mailer'])
    ->run('Find users who haven\'t logged in for 30 days and send them a re-engagement email');
```

**With persistent memory (multi-turn):**
```php
// Turn 1
Agent::tools(['database'])->withMemory('analysis-001')
    ->run('Give me an overview of our sales performance');

// Turn 2 — agent remembers turn 1
Agent::withMemory('analysis-001')
    ->run('Now drill into the top-performing product category');
```

**Multi-agent pipeline:**
```php
$result = Agent::pipeline()
    ->step('analyst')
        ->task('Pull our top 10 customers by revenue')
        ->tools(['database'])
        ->passOutputAs('customers')
    ->step('writer')
        ->task('Write a VIP appreciation email for each: {customers}')
        ->tools(['mailer'])
    ->run();

echo $result->finalOutput;
```

---

## Built-in Agent Personas

Thirteen pre-built agents with tuned system prompts and sensible tool defaults:

| Persona | Method | Best For |
|---|---|---|
| SupportAgent | `Agent::support()` | Customer issue resolution |
| DataAgent | `Agent::data()` | Business intelligence queries |
| ContentAgent | `Agent::content()` | Writing and content creation |
| WorkflowAgent | `Agent::workflow()` | Multi-step automation |
| DevAgent | `Agent::dev()` | Code generation and dev tasks |
| CodingAgent | `Agent::coding()` | Implementation and code writing |
| TestingAgent | `Agent::testing()` | Test generation and QA |
| PlanningAgent | `Agent::planning()` | Architecture and planning |
| DocumentationAgent | `Agent::docs()` | Writing docs and API references |
| DeploymentAgent | `Agent::deploy()` | Deployments and DevOps tasks |
| ResearchAgent | `Agent::research()` | Gathering information and facts |
| DesignAgent | `Agent::design()` | System and component design |
| UiUxAgent | `Agent::uiux()` | UI/UX design specifications |

```php
// Data analyst — queries DB, returns numbers with context
Agent::data()->run('What\'s our month-over-month user growth rate?');

// Customer support — looks up accounts, sends emails
Agent::support()->run('User ID 42 says they were charged twice last month');

// Content writer — uses HTTP + filesystem
Agent::content()->run('Write 3 subject line options for our Black Friday campaign');

// Developer assistant — filesystem, artisan, HTTP
Agent::dev()->run('Generate a complete CRUD controller for a Product model');

// Workflow automation — database, email, artisan, filesystem
Agent::workflow()->run('Run end-of-month: tally orders, compute revenue, email report to admin@company.com');
```

Extend any persona:

```php
Agent::data()
    ->withTools(['mailer'])                          // add tools to defaults
    ->withSystem('Always format numbers with commas.') // append to system prompt
    ->run('Email me the weekly revenue summary');
```

---

## Provider Configuration

### Ollama — Local, Free (default)

```bash
# Install: https://ollama.ai
ollama pull llama3.2
ollama serve
```

```env
LARAGENT_PROVIDER=ollama
OLLAMA_MODEL=llama3.2
OLLAMA_HOST=http://localhost:11434
```

### Anthropic Claude

```env
LARAGENT_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-haiku-4-5
```

### OpenAI and Compatible APIs

```env
# OpenAI
LARAGENT_PROVIDER=openai
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini

# Groq (free tier available!)
LARAGENT_PROVIDER=openai
OPENAI_API_KEY=gsk_...
OPENAI_BASE_URL=https://api.groq.com/openai/v1
OPENAI_MODEL=llama-3.3-70b-versatile

# Together AI
OPENAI_BASE_URL=https://api.together.xyz/v1

# Mistral
OPENAI_BASE_URL=https://api.mistral.ai/v1
```

---

## Built-in Tools

| Tool | Short name | Description | Security |
|---|---|---|---|
| `DatabaseTool` | `database` | Read-only Eloquent queries | Model allowlist, no raw SQL |
| `MailerTool` | `mailer` | Send via Laravel Mail | Email format validation |
| `HttpTool` | `http` | External API calls | SSRF protection, HTTP/HTTPS only |
| `ArtisanTool` | `artisan` | Safe Artisan commands | Strict allowlist |
| `FilesystemTool` | `filesystem` | Read/write sandbox | Path traversal blocked |

You can use either the short name (`database`) or the full tool name (`database_query`) — both work:

```php
Agent::tools(['database', 'mailer'])->run('...');
// same as
Agent::tools(['database_query', 'send_email'])->run('...');
```

### Security defaults

**DatabaseTool** — read-only, allowlist support:
```php
// config/laragent.php
'allowed_models' => ['User', 'Order', 'Product'], // empty = allow all App\Models
```

**HttpTool** — blocks SSRF targets: `127.x`, `10.x`, `192.168.x`, `172.16-31.x`, `169.254.x`, and non-HTTP schemes (`file://`, `ftp://`).

**ArtisanTool** — only commands in the allowlist:
```php
'safe_commands' => ['cache:clear', 'config:clear', 'queue:restart', 'view:clear'],
```

**FilesystemTool** — sandboxed to `storage/app/agent-sandbox/`. Path traversal (`..`) and absolute paths are blocked.

---

## Creating Custom Tools

```php
use Laragent\Tools\BaseTool;
use Illuminate\Support\Facades\Http;

class StripeChargeLookupTool extends BaseTool
{
    public function name(): string
    {
        return 'stripe_charge_lookup';
    }

    public function description(): string
    {
        return 'Look up a Stripe charge by ID or customer email.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'charge_id' => ['type' => 'string', 'description' => 'Stripe charge ID (ch_...)'],
                'email'     => ['type' => 'string', 'description' => 'Customer email to search'],
            ],
        ];
    }

    public function execute(array $params): string
    {
        // SECURITY: only accept charge IDs or email, never raw queries
        $chargeId = $params['charge_id'] ?? null;
        $email    = $params['email'] ?? null;

        if (!$chargeId && !$email) {
            return $this->error('Provide either a charge_id or an email.');
        }

        $response = Http::withToken(config('services.stripe.secret'))
            ->get("https://api.stripe.com/v1/charges", array_filter([
                'customer' => $email,
                'limit'    => 5,
            ]));

        if ($response->failed()) {
            return $this->error('Stripe lookup failed: ' . $response->body());
        }

        return json_encode($response->json('data'), JSON_PRETTY_PRINT);
    }
}
```

Register it in a service provider:

```php
app(\Laragent\Tools\ToolRegistry::class)->register(new StripeChargeLookupTool());
```

Use it:

```php
Agent::tools(['stripe_charge_lookup'])->run('Did user john@example.com get charged twice in November?');
```

---

## Testing with AgentFake

Laragent ships a testing fake — no real AI calls in tests:

```php
use Laragent\Facades\Agent;
use Laragent\Testing\AgentFake;

it('sends re-engagement emails to inactive users', function () {
    Agent::fake();
    AgentFake::returns('Found 42 inactive users and sent them re-engagement emails.');

    // Trigger your app code that uses the agent internally
    $this->artisan('app:send-reengagement-emails');

    AgentFake::assertRan();
    AgentFake::assertRanWith('inactive');
    AgentFake::assertToolWasCalled('mailer');
    AgentFake::assertRunCount(1);
});
```

### Assertion API

| Method | Description |
|---|---|
| `AgentFake::returns(string $answer)` | Queue a fake response |
| `AgentFake::assertRan()` | Assert agent ran at least once |
| `AgentFake::assertNotRan()` | Assert agent never ran |
| `AgentFake::assertRanWith(string $substring)` | Assert task contained substring |
| `AgentFake::assertToolWasCalled(string $tool)` | Assert tool was requested |
| `AgentFake::assertToolNotCalled(string $tool)` | Assert tool was NOT requested |
| `AgentFake::assertRunCount(int $n)` | Assert agent ran exactly N times |
| `AgentFake::assertCompleted()` | Assert at least one run completed |
| `AgentFake::reset()` | Clear all recorded calls |

---

## Interactive CLI

Laragent ships a full interactive CLI — like Claude Code but for your own Laravel app:

```bash
php artisan laragent:chat
```

Features:
- Persistent conversation memory across turns
- Real-time tool call display
- Slash commands: `/help`, `/exit`, `/clear`, `/tools`, `/agent`, `/provider`, `/swarm`, `/status`
- Switch personas mid-session: `/agent coding`
- Launch swarms: `/swarm Build a REST API for products`
- Speech-to-text input: `/speak` (requires LocalAI)

```bash
# Start with a specific provider and tools
php artisan laragent:chat --provider=ollama --tools=database --tools=filesystem

# Start as a specific persona
php artisan laragent:chat --agent=coding

# Resume a previous session
php artisan laragent:chat --session=your-session-id
```

---

## Multi-Agent Swarm

Launch multiple specialized agents collaboratively on complex tasks:

```bash
php artisan laragent:swarm "Build a products inventory management feature"
```

Built-in templates:

| Template | Agents | Use Case |
|---|---|---|
| `feature` (default) | planning, coding, testing, docs | Full feature from spec to tests |
| `api` | planning, coding, testing, docs | REST API with feature tests |
| `frontend` | planning, uiux, coding, testing | Frontend component with tests |
| `audit` | research, docs | Code audit and report |

```bash
# Choose a template
php artisan laragent:swarm "Add a subscription billing system" --template=api

# Use specific agents in order
php artisan laragent:swarm "Review database schema" --agents=research --agents=documentation

# Or from PHP
use Laragent\Swarm\SwarmOrchestrator;

$orchestrator = new SwarmOrchestrator(app('laragent'));
$swarm = $orchestrator->plan('Build a REST API for orders', 'api');
$result = $swarm->run();

echo $result->finalOutput;
echo $result->summary(); // "Swarm completed: 4 agents, 12 iterations, 8420ms"
```

---

## Speech-to-Text (CLI)

Use your voice as input in `laragent:chat` — completely free, runs locally.

**Recommended: LocalAI** (Ollama-like HTTP server, no Python required):

```bash
# Install LocalAI with Whisper support
docker run -p 8080:8080 localai/localai:latest whisper

# OR install the binary: https://localai.io
```

```env
LARAGENT_STT_DRIVER=localai
LARAGENT_STT_HOST=http://localhost:8080
LARAGENT_STT_MODEL=whisper-1
```

**Alternative: Whisper CLI** (Python-based):

```bash
pip install openai-whisper
sudo apt install sox  # Linux — for microphone capture
brew install sox      # macOS
```

```env
LARAGENT_STT_DRIVER=whisper
LARAGENT_STT_MODEL=tiny
```

Once configured, use `/speak` in the CLI chat to record and transcribe your voice.

---

## Artisan Commands

| Command | Description |
|---|---|
| `php artisan laragent:install` | Interactive setup wizard |
| `php artisan laragent:chat` | Interactive CLI chat session |
| `php artisan laragent:swarm "task"` | Multi-agent swarm for complex tasks |
| `php artisan agent:run "task"` | Run a single agent task from the terminal |
| `php artisan agent:sessions` | List recent agent sessions |
| `php artisan agent:logs {id}` | Inspect a session step-by-step |

```bash
# Run with tools and custom provider
php artisan agent:run "How many orders are pending?" --tools=database --provider=ollama

# Resume a previous session
php artisan agent:run "Continue the analysis" --memory=session-abc-123
```

---

## AgentResponse

Every `->run()` returns an `AgentResponse`:

```php
$result = Agent::run('...');

$result->answer;       // The agent's final answer
$result->sessionId;    // UUID of the agent session
$result->toolCalls;    // Array of [{tool, params, result}]
$result->iterations;   // Reasoning loop iterations used
$result->tokensUsed;   // Total tokens (input + output)
$result->durationMs;   // Wall-clock time in milliseconds
$result->success;      // true / false

$result->wasSuccessful();  // bool
$result->usedTools();      // bool
$result->summary();        // "Completed in 3 iteration(s), 2 tool call(s), 1240ms"
$result->toArray();        // array
$result->toJson();         // JSON string
```

---

## Events

```php
use Laragent\Events\AgentStarted;
use Laragent\Events\AgentThinking;
use Laragent\Events\AgentToolCalled;
use Laragent\Events\AgentToolResult;
use Laragent\Events\AgentCompleted;
use Laragent\Events\AgentFailed;

Event::listen(AgentCompleted::class, function (AgentCompleted $event) {
    Log::info('Agent completed', [
        'session' => $event->session->id,
        'tokens'  => $event->response->tokensUsed,
        'ms'      => $event->response->durationMs,
    ]);
});
```

---

## Configuration Reference

```php
// config/laragent.php
return [
    'default_provider' => env('LARAGENT_PROVIDER', 'ollama'),

    'providers' => [
        'ollama'    => ['host' => env('OLLAMA_HOST', 'http://localhost:11434'), 'model' => env('OLLAMA_MODEL', 'llama3.2'), 'timeout' => 120],
        'anthropic' => ['api_key' => env('ANTHROPIC_API_KEY'), 'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'), 'timeout' => 60],
        'openai'    => ['api_key' => env('OPENAI_API_KEY'), 'model' => env('OPENAI_MODEL', 'gpt-4o-mini'), 'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), 'timeout' => 60],
    ],

    'max_iterations' => 10,                          // Reasoning loop limit
    'memory_driver'  => env('LARAGENT_MEMORY', 'database'), // database|cache|array
    'log_steps'      => true,                        // Write every step to agent_logs

    'allowed_models' => [],                          // Eloquent model allowlist for DatabaseTool
    'safe_commands'  => ['cache:clear', 'config:clear', 'queue:restart', 'view:clear'],
    'sandbox_path'   => 'agent-sandbox',             // FilesystemTool sandbox (storage/app relative)
];
```

---

## Sponsorship

Laragent is MIT licensed and free forever. If it saves you hours on a client project, consider sponsoring:

| Tier | Amount | Benefit |
|---|---|---|
| Supporter | $5/mo | Name in README |
| Backer | $25/mo | Logo in README |
| Silver Sponsor | $100/mo | Logo on laragent.dev |
| Gold Sponsor | $500/mo | Logo + blog post about your product |

- [Sponsor on GitHub](https://github.com/sponsors/laragent-dev)
- [Laragent Pro](https://laragent.dev) — visual flow builder, analytics, Pro tool library ($10/mo)

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full guide. Quick start:

```bash
git clone https://github.com/laragent-dev/laragent-dev.git
cd laragent
composer install
./vendor/bin/pest      # run tests
./vendor/bin/pint      # fix code style
```

- Bug reports and feature requests: [open an issue](https://github.com/laragent-dev/laragent-dev/issues)
- Security vulnerabilities: email `security@laragent.dev` (do not open a public issue)
- Questions and discussion: [GitHub Discussions](https://github.com/laragent-dev/laragent-dev/discussions)

---

## Community

- [GitHub Discussions](https://github.com/laragent-dev/laragent-dev/discussions) — questions, ideas, show and tell
- [GitHub Issues](https://github.com/laragent-dev/laragent-dev/issues) — bugs and feature requests
- [laragent.dev](https://laragent.dev) — website and Pro plan

---

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating you agree to abide by its terms.

---

## Security

See [SECURITY.md](SECURITY.md) for the vulnerability disclosure policy and an overview of Laragent's security model.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

---

## License

MIT — free to use in commercial projects.
Copyright (c) 2024 Laragent ([laragent.dev](https://laragent.dev))
