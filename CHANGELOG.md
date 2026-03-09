# Changelog

All notable changes to Laragent will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2024-03-09

### Added
- Core agent reasoning loop (ReAct pattern: Think, Act, Observe, Respond)
- Three AI providers: Ollama (local, free), Anthropic, OpenAI/compatible
- Tool aliases: `database`, `mailer`, `http`, `artisan`, `filesystem`
- Five built-in tools: DatabaseTool, MailerTool, HttpTool, ArtisanTool, FilesystemTool
- Five pre-built agent personas: Support, Data, Content, Workflow, Dev
- Fluent builder API via `Agent::make()` facade
- Multi-agent pipeline with `passOutputAs()` context injection
- Persistent memory with database, cache, and array drivers
- Async queue support via `RunAgentJob`
- Full Laravel event lifecycle: AgentStarted, AgentThinking, AgentToolCalled, AgentToolResult, AgentCompleted, AgentFailed
- `AgentFake` testing utility with assertion API
- Artisan commands: `laragent:install`, `agent:run`, `agent:sessions`, `agent:logs`
- Comprehensive Pest PHP test suite

[Unreleased]: https://github.com/laragent-dev/laragent/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/laragent-dev/laragent/releases/tag/v0.1.0
