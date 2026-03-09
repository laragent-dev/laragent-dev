<?php

namespace Laragent\Tools;

use Illuminate\Support\Facades\Storage;

class FilesystemTool extends BaseTool
{
    public function name(): string
    {
        return 'filesystem';
    }

    public function description(): string
    {
        return 'Read and write files in the agent sandbox directory.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'action'  => ['type' => 'string', 'enum' => ['read', 'write', 'list', 'delete', 'exists']],
                'path'    => ['type' => 'string', 'description' => 'Relative file path within sandbox'],
                'content' => ['type' => 'string', 'description' => 'File content (required for write action)'],
            ],
            'required'   => ['action', 'path'],
        ];
    }

    public function execute(array $params): string
    {
        $action = $params['action'] ?? '';
        $path = $params['path'] ?? '';
        $content = $params['content'] ?? '';

        // Security: prevent path traversal
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            return $this->error("Invalid path. Paths cannot contain '..' or start with '/'");
        }

        $sandboxPath = config('laragent.sandbox_path', 'agent-sandbox');
        $fullPath = $sandboxPath . '/' . $path;

        try {
            return match ($action) {
                'read'   => $this->readFile($fullPath),
                'write'  => $this->writeFile($fullPath, $content),
                'list'   => $this->listFiles($sandboxPath, $path),
                'delete' => $this->deleteFile($fullPath),
                'exists' => $this->fileExists($fullPath),
                default  => $this->error("Unknown action: {$action}"),
            };
        } catch (\Exception $e) {
            return $this->error("Filesystem operation failed: " . $e->getMessage());
        }
    }

    private function readFile(string $path): string
    {
        if (!Storage::disk('local')->exists($path)) {
            return $this->error("File not found: {$path}");
        }

        $content = Storage::disk('local')->get($path);
        $truncated = strlen($content) > 10000;

        return substr($content, 0, 10000) . ($truncated ? "\n... (truncated at 10000 chars)" : '');
    }

    private function writeFile(string $path, string $content): string
    {
        Storage::disk('local')->put($path, $content);
        return "File written: {$path}";
    }

    private function listFiles(string $sandbox, string $directory): string
    {
        $listPath = $directory ? $sandbox . '/' . $directory : $sandbox;
        $files = Storage::disk('local')->files($listPath);

        if (empty($files)) {
            return json_encode([]);
        }

        // Strip sandbox prefix from paths
        $files = array_map(fn($f) => str_replace($sandbox . '/', '', $f), $files);
        return json_encode($files);
    }

    private function deleteFile(string $path): string
    {
        if (!Storage::disk('local')->exists($path)) {
            return $this->error("File not found: {$path}");
        }

        Storage::disk('local')->delete($path);
        return "File deleted: {$path}";
    }

    private function fileExists(string $path): string
    {
        $exists = Storage::disk('local')->exists($path) ? 'true' : 'false';
        return "File exists: {$exists}";
    }
}
