<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Supported: "ollama", "anthropic", "openai"
    | Ollama is the default — it runs locally for FREE with no API key.
    */
    'default_provider' => env('LARAGENT_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'ollama' => [
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3.2'),
            'timeout' => 120,
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
            'timeout' => 60,
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Behavior
    |--------------------------------------------------------------------------
    */
    'max_iterations' => 10,

    /*
    |--------------------------------------------------------------------------
    | Memory Driver
    |--------------------------------------------------------------------------
    | Supported: "database", "cache", "array"
    */
    'memory_driver' => env('LARAGENT_MEMORY', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'log_steps' => true,

    /*
    |--------------------------------------------------------------------------
    | Security: Allowed Eloquent Models for DatabaseTool
    |--------------------------------------------------------------------------
    | Empty array = allow all models in App\Models\
    | Specify class names to restrict: ['User', 'Order', 'Product']
    */
    'allowed_models' => [],

    /*
    |--------------------------------------------------------------------------
    | Security: Safe Artisan Commands for ArtisanTool
    |--------------------------------------------------------------------------
    */
    'safe_commands' => [
        'cache:clear',
        'config:clear',
        'queue:restart',
        'view:clear',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Sandbox
    |--------------------------------------------------------------------------
    | Agents can only read/write files within this directory (storage/app relative)
    */
    'sandbox_path' => 'agent-sandbox',

    /*
    |--------------------------------------------------------------------------
    | Speech-to-Text (CLI Chat)
    |--------------------------------------------------------------------------
    | Drivers:
    |   "localai"     — Recommended. Ollama-like local HTTP server (no Python).
    |                   Install: docker run -p 8080:8080 localai/localai:latest whisper
    |                   Docs:    https://localai.io
    |   "whisper"     — Requires Python: pip install openai-whisper
    |   "whisper_cpp" — Fastest, no Python. Build from: github.com/ggerganov/whisper.cpp
    |
    | Model:    tiny | base | small | medium | large (larger = more accurate, slower)
    | Host:     LocalAI server URL (localai driver only)
    | Seconds:  How long to record audio before transcribing
    | Language: Language code for Whisper (whisper/whisper_cpp drivers)
    */
    'stt' => [
        'driver' => env('LARAGENT_STT_DRIVER', 'localai'),
        'model' => env('LARAGENT_STT_MODEL', 'whisper-1'),
        'host' => env('LARAGENT_STT_HOST', 'http://localhost:8080'),
        'seconds' => (int) env('LARAGENT_STT_SECONDS', 5),
        'language' => env('LARAGENT_STT_LANGUAGE', 'en'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Swarm
    |--------------------------------------------------------------------------
    | Default template used by `laragent:swarm` when no --template is passed.
    | Built-in templates: feature | api | frontend | audit
    */
    'swarm' => [
        'default_template' => env('LARAGENT_SWARM_TEMPLATE', 'feature'),
    ],
];
