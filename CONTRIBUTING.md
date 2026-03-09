# Contributing to Laragent

Thank you for your interest in contributing. Laragent is MIT licensed and open to contributions of all kinds.

## Ways to Contribute

- **Bug reports** — open an issue with steps to reproduce
- **Feature requests** — open an issue describing the use case
- **Bug fixes** — open a PR with a failing test that your fix makes pass
- **New tools** — see [Adding a Tool](#adding-a-tool)
- **New providers** — see [Adding a Provider](#adding-a-provider)
- **Documentation** — fix typos, improve examples, add clarity
- **Tests** — improve coverage or add missing edge cases

## Getting Started

```bash
git clone https://github.com/laragent-dev/laragent.git
cd laragent
composer install
```

Run tests:

```bash
./vendor/bin/pest
```

Fix code style:

```bash
./vendor/bin/pint
```

## Development Rules

- All new code must have tests in `tests/Unit/` or `tests/Feature/`
- Never hit real APIs in tests — use `Http::fake()` or `AgentFake`
- Tools must return error strings on failure, never throw exceptions (agents must continue)
- Security implications must be documented in comments on every tool
- All public methods need PHPDoc (`@param`, `@return`, `@throws`)
- PHP 8.2+ features preferred (readonly, enums, match, named arguments)

## Adding a Tool

1. Create `src/Tools/{Name}Tool.php` extending `BaseTool`
2. Implement `name()`, `description()`, `parameters()`, `execute()`
3. Document the security model in inline comments
4. Register it in `LaraAgentServiceProvider::register()`
5. Write tests in `tests/Unit/Tools/{Name}ToolTest.php`
6. Add it to the tools table in `README.md`

```php
use LaraAgent\Tools\BaseTool;

class MyTool extends BaseTool
{
    public function name(): string { return 'my_tool'; }

    public function description(): string
    {
        return 'One sentence description shown to the LLM.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'input' => ['type' => 'string', 'description' => '...'],
            ],
            'required' => ['input'],
        ];
    }

    public function execute(array $params): string
    {
        // Always return a string. Use $this->error('message') on failure.
        return 'result';
    }
}
```

## Adding a Provider

1. Create `src/Providers/{Name}Provider.php` extending `BaseProvider`
2. Implement `complete()`, `models()`, `validateConfig()`
3. Add it to `ProviderFactory::make()` and `config/laragent.php`
4. Update `AgentInstallCommand` to offer it during setup
5. Write tests in `tests/Unit/{Name}ProviderTest.php`
6. Document it in `README.md` under Provider Configuration

## Commit Convention

```
feat:     new feature
fix:      bug fix
docs:     documentation only
test:     tests only
refactor: no feature/fix changes
chore:    build, tooling, config
```

## Pull Request Process

1. Fork the repository and create a feature branch from `main`
2. Write your code and tests
3. Ensure `./vendor/bin/pest` passes with no failures
4. Ensure `./vendor/bin/pint` reports no style errors
5. Update `CHANGELOG.md` under `[Unreleased]`
6. Open a PR with a clear description of what and why

PRs that break existing tests or skip the test requirement will not be merged.

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md).
By participating you agree to abide by its terms.

## Security Vulnerabilities

Do **not** open a public issue for security vulnerabilities. Email `security@laragent.dev` instead. See [SECURITY.md](SECURITY.md) for the full policy.
