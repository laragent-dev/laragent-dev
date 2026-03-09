<?php

namespace LaraAgent\Agent;

use LaraAgent\Models\AgentSession;
use LaraAgent\Providers\BaseProvider;
use Illuminate\Support\Facades\Cache;

class AgentMemory
{
    private AgentSession $session;
    private string $driver;
    private array $memoryStore = [];  // For array driver
    private array $messageStore = []; // For array driver

    public function __construct(
        string $sessionId,
        string $driver = 'database',
        array $sessionData = [],
    ) {
        $this->driver = $driver;

        if ($driver === 'database') {
            $this->session = AgentSession::firstOrCreate(
                ['id' => $sessionId],
                array_merge([
                    'context'  => [],
                    'messages' => [],
                    'status'   => 'pending',
                ], $sessionData)
            );
        } else {
            $this->session = new AgentSession(array_merge([
                'id'       => $sessionId,
                'context'  => [],
                'messages' => [],
                'status'   => 'pending',
            ], $sessionData));
            $this->session->id = $sessionId;

            if ($driver === 'cache') {
                $this->memoryStore = Cache::get("laragent:context:{$sessionId}", []);
                $this->messageStore = Cache::get("laragent:messages:{$sessionId}", []);
            }
        }
    }

    public function remember(string $key, mixed $value): void
    {
        if ($this->driver === 'database') {
            $context = $this->session->context ?? [];
            $context[$key] = $value;
            $this->session->update(['context' => $context]);
        } elseif ($this->driver === 'cache') {
            $this->memoryStore[$key] = $value;
            Cache::put("laragent:context:{$this->session->id}", $this->memoryStore, now()->addDays(7));
        } else {
            $this->memoryStore[$key] = $value;
        }
    }

    public function recall(string $key, mixed $default = null): mixed
    {
        if ($this->driver === 'database') {
            return ($this->session->context ?? [])[$key] ?? $default;
        }
        return $this->memoryStore[$key] ?? $default;
    }

    public function forget(string $key): void
    {
        if ($this->driver === 'database') {
            $context = $this->session->context ?? [];
            unset($context[$key]);
            $this->session->update(['context' => $context]);
        } else {
            unset($this->memoryStore[$key]);
        }
    }

    public function addMessage(string $role, string $content): void
    {
        if ($this->driver === 'database') {
            $messages = $this->session->messages ?? [];
            $messages[] = ['role' => $role, 'content' => $content];
            $this->session->update(['messages' => $messages]);
        } elseif ($this->driver === 'cache') {
            $this->messageStore[] = ['role' => $role, 'content' => $content];
            Cache::put("laragent:messages:{$this->session->id}", $this->messageStore, now()->addDays(7));
        } else {
            $this->messageStore[] = ['role' => $role, 'content' => $content];
        }
    }

    public function getMessages(): array
    {
        if ($this->driver === 'database') {
            return $this->session->fresh()->messages ?? [];
        }
        return $this->messageStore;
    }

    public function getContext(): array
    {
        if ($this->driver === 'database') {
            return $this->session->fresh()->context ?? [];
        }
        return $this->memoryStore;
    }

    public function clear(): void
    {
        if ($this->driver === 'database') {
            $this->session->update(['context' => [], 'messages' => []]);
        } elseif ($this->driver === 'cache') {
            $this->memoryStore = [];
            $this->messageStore = [];
            Cache::forget("laragent:context:{$this->session->id}");
            Cache::forget("laragent:messages:{$this->session->id}");
        } else {
            $this->memoryStore = [];
            $this->messageStore = [];
        }
    }

    public function summarizeIfNeeded(BaseProvider $provider): void
    {
        $messages = $this->getMessages();

        if (count($messages) <= 20) {
            return;
        }

        // Keep last 5 messages, summarize the rest
        $toSummarize = array_slice($messages, 0, -5);
        $toKeep = array_slice($messages, -5);

        $summaryMessages = array_merge($toSummarize, [
            [
                'role'    => 'user',
                'content' => 'Please provide a brief summary of the conversation above, focusing on key decisions, data found, and actions taken.',
            ],
        ]);

        try {
            $summaryResponse = $provider->complete($summaryMessages);
            $summary = [
                ['role' => 'system', 'content' => 'Previous conversation summary: ' . $summaryResponse->content],
            ];
            $newMessages = array_merge($summary, $toKeep);

            if ($this->driver === 'database') {
                $this->session->update(['messages' => $newMessages]);
            } elseif ($this->driver === 'cache') {
                $this->messageStore = $newMessages;
                Cache::put("laragent:messages:{$this->session->id}", $this->messageStore, now()->addDays(7));
            } else {
                $this->messageStore = $newMessages;
            }
        } catch (\Exception $e) {
            // If summarization fails, just trim old messages
        }
    }

    public function getSession(): AgentSession
    {
        return $this->session;
    }

    public function export(): array
    {
        return [
            'session_id' => $this->session->id,
            'context'    => $this->getContext(),
            'messages'   => $this->getMessages(),
        ];
    }

    public static function resume(string $sessionId): static
    {
        $driver = config('laragent.memory_driver', 'database');
        return new static($sessionId, $driver);
    }

    public static function new(?string $sessionId = null): static
    {
        $sessionId = $sessionId ?? \Illuminate\Support\Str::uuid()->toString();
        $driver = config('laragent.memory_driver', 'database');
        return new static($sessionId, $driver);
    }
}
