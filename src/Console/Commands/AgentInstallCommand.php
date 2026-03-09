<?php

namespace Laragent\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laragent\Providers\OllamaProvider;

class AgentInstallCommand extends Command
{
    protected $signature = 'laragent:install';
    protected $description = 'Install and configure Laragent with your preferred AI provider';

    public function handle(): int
    {
        $this->displayBanner();

        $provider = $this->askProvider();

        match ($provider) {
            'ollama'    => $this->setupOllama(),
            'anthropic' => $this->setupAnthropic(),
            'openai'    => $this->setupOpenAI(),
        };

        $this->publishAssets();

        $this->runTest($provider);

        $this->showNextSteps();

        return self::SUCCESS;
    }

    private function displayBanner(): void
    {
        $this->line('');
        $this->line('  ╔════════════════════════════════════════╗');
        $this->line('  ║          Laragent Installer            ║');
        $this->line('  ║   Autonomous AI Agents for Laravel     ║');
        $this->line('  ╚════════════════════════════════════════╝');
        $this->line('');
    }

    private function askProvider(): string
    {
        $choice = $this->choice(
            'Which AI provider would you like to use?',
            [
                'ollama'    => 'Ollama (Local, FREE — recommended)',
                'anthropic' => 'Anthropic Claude (API key required)',
                'openai'    => 'OpenAI / Compatible (API key required)',
            ],
            'ollama'
        );

        return $choice;
    }

    private function setupOllama(): void
    {
        $this->info('Setting up Ollama (local, free)...');

        // Check if ollama is installed
        $version = shell_exec('ollama --version 2>&1');
        if (!$version || str_contains($version, 'not found') || str_contains($version, 'command not found')) {
            $this->warn('Ollama is not installed. Install it first:');
            $this->line('');
            $this->line('  macOS:   brew install ollama');
            $this->line('           OR download from https://ollama.ai');
            $this->line('');
            $this->line('  Linux:   curl -fsSL https://ollama.ai/install.sh | sh');
            $this->line('');
            $this->line('  Windows: Download installer from https://ollama.ai');
            $this->line('');

            if (!$this->confirm('Have you installed Ollama? Continue anyway?', false)) {
                $this->error('Please install Ollama and run this command again.');
                return;
            }
        } else {
            $this->info('Ollama is installed: ' . trim($version));
        }

        // Check if ollama is running
        $ollamaConfig = ['host' => 'http://localhost:11434', 'model' => 'llama3.2'];
        $ollamaProvider = new OllamaProvider($ollamaConfig);

        if (!$ollamaProvider->isRunning()) {
            $this->warn('Ollama is not running. Start it with:');
            $this->line('  ollama serve');
            $this->line('');

            if (!$this->confirm('Start Ollama and continue?', true)) {
                $this->error('Please start Ollama and run this command again.');
                return;
            }
        } else {
            $this->info('Ollama is running');
        }

        // Ask which model to use
        $model = $this->choice(
            'Which model would you like to use?',
            [
                'llama3.2'       => 'llama3.2 (3GB — fast, good for most tasks)',
                'qwen2.5-coder'  => 'qwen2.5-coder (4GB — best for code generation)',
                'mistral'        => 'mistral (4GB — strong reasoning)',
                'deepseek-r1:7b' => 'deepseek-r1:7b (5GB — excellent reasoning)',
            ],
            'llama3.2'
        );

        // Check if model is available
        $installedModels = $ollamaProvider->models();
        $modelName = explode(' ', $model)[0];

        if (!in_array($modelName, $installedModels)) {
            $this->info("Pulling model '{$modelName}'... (this may take a few minutes)");
            passthru("ollama pull {$modelName}");
        } else {
            $this->info("Model '{$modelName}' is already installed");
        }

        // Write to .env
        $this->writeEnv([
            'LARAGENT_PROVIDER' => 'ollama',
            'OLLAMA_MODEL'      => $modelName,
        ]);

        $this->info('Ollama configured successfully');
    }

    private function setupAnthropic(): void
    {
        $this->info('Setting up Anthropic Claude...');

        $apiKey = $this->secret('Enter your Anthropic API key');

        if (empty($apiKey)) {
            $this->error('API key cannot be empty.');
            return;
        }

        $this->info('Validating API key...');

        // Write to .env
        $this->writeEnv([
            'LARAGENT_PROVIDER' => 'anthropic',
            'ANTHROPIC_API_KEY' => $apiKey,
        ]);

        $this->info('Anthropic configured successfully');
    }

    private function setupOpenAI(): void
    {
        $this->info('Setting up OpenAI / Compatible API...');
        $this->line('Note: Works with Groq, Together AI, Mistral, and any OpenAI-compatible API');
        $this->line('');

        $apiKey = $this->secret('Enter your API key');

        if (empty($apiKey)) {
            $this->error('API key cannot be empty.');
            return;
        }

        $baseUrl = $this->ask(
            'Base URL (press Enter for OpenAI default)',
            'https://api.openai.com/v1'
        );

        // Write to .env
        $this->writeEnv([
            'LARAGENT_PROVIDER' => 'openai',
            'OPENAI_API_KEY'    => $apiKey,
            'OPENAI_BASE_URL'   => $baseUrl,
        ]);

        $this->info('OpenAI configured successfully');
    }

    private function publishAssets(): void
    {
        $this->info('Publishing config and migrations...');

        $this->call('vendor:publish', [
            '--tag'   => 'laragent-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag'   => 'laragent-migrations',
            '--force' => true,
        ]);

        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }
    }

    private function runTest(string $provider): void
    {
        if (!$this->confirm('Run a quick test to verify everything works?', true)) {
            return;
        }

        $this->info('Running test...');

        try {
            $start = microtime(true);
            $result = \Laragent\Facades\Agent::run('Say hello in exactly 5 words');
            $ms = round((microtime(true) - $start) * 1000);

            $this->info('');
            $this->info('Agent response: ' . $result->answer);
            $this->info('');
            $this->info("Laragent is working! Agent responded in {$ms}ms");
        } catch (\Exception $e) {
            $this->error('Test failed: ' . $e->getMessage());
            $this->warn('Please check your configuration and try again.');
        }
    }

    private function showNextSteps(): void
    {
        $this->line('');
        $this->info('Laragent installed successfully!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('  1. Try it in tinker:    php artisan tinker');
        $this->line("                          Agent::run('What can you help me with?')");
        $this->line('  2. Run the demo:        php artisan agent:run "Describe our database schema"');
        $this->line('  3. Read the docs:       https://github.com/laragent-dev/laragent-dev');
        $this->line('  4. Star us on GitHub:   github.com/laragent-dev/laragent-dev');
        $this->line('');
    }

    private function writeEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $this->warn('.env file not found, skipping environment variable setup.');
            return;
        }

        $env = File::get($envPath);

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, "{$key}={$value}", $env);
            } else {
                $env .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $env);
    }
}
