<?php

namespace Laragent\Tools;

class DatabaseTool extends BaseTool
{
    public function name(): string
    {
        return 'database_query';
    }

    public function description(): string
    {
        return 'Query the application database. Use this to retrieve data, count records, or check information. READ-ONLY — cannot modify data.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'model' => ['type' => 'string', 'description' => 'Eloquent model class name, e.g. "User", "Order"'],
                'action' => ['type' => 'string', 'enum' => ['find', 'where', 'count', 'sum', 'avg', 'latest', 'oldest']],
                'conditions' => ['type' => 'object', 'description' => 'Key-value pairs for where clauses'],
                'columns' => ['type' => 'array', 'description' => 'Columns to select (defaults to all)'],
                'limit' => ['type' => 'integer', 'description' => 'Maximum results (max 50)', 'maximum' => 50],
                'order_by' => ['type' => 'string', 'description' => 'Column to order by'],
                'column' => ['type' => 'string', 'description' => 'Column for sum/avg operations'],
            ],
            'required' => ['model', 'action'],
        ];
    }

    public function execute(array $params): string
    {
        $modelName = $params['model'] ?? '';
        $action = $params['action'] ?? 'where';
        $conditions = $params['conditions'] ?? [];
        $columns = $params['columns'] ?? ['*'];
        $limit = min((int) ($params['limit'] ?? 10), 50);
        $orderBy = $params['order_by'] ?? null;

        // Security: validate model
        $modelClass = $this->resolveModel($modelName);
        if (! $modelClass) {
            return $this->error("Model '{$modelName}' not found or not allowed.");
        }

        try {
            $query = $modelClass::query()->select($columns);

            // Apply conditions
            if (! empty($conditions)) {
                foreach ($conditions as $column => $value) {
                    $query->where($column, $value);
                }
            }

            // Apply ordering
            if ($orderBy) {
                $query->orderBy($orderBy);
            }

            // Execute action
            $result = match ($action) {
                'count' => $query->count(),
                'sum' => $query->sum($params['column'] ?? 'id'),
                'avg' => $query->avg($params['column'] ?? 'id'),
                'latest' => $query->latest()->limit($limit)->get()->toArray(),
                'oldest' => $query->oldest()->limit($limit)->get()->toArray(),
                'find' => isset($conditions['id']) ? $query->find($conditions['id'])?->toArray() : null,
                default => $query->limit($limit)->get()->toArray(),
            };

            if ($result === null) {
                return 'No records found matching the criteria';
            }

            if (is_array($result) && empty($result)) {
                return 'No records found matching the criteria';
            }

            return json_encode($result, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return $this->error('Database query failed: '.$e->getMessage());
        }
    }

    private function resolveModel(string $name): ?string
    {
        $allowedModels = config('laragent.allowed_models', []);

        // Try to find the model class
        $candidates = [
            "App\\Models\\{$name}",
            "App\\{$name}",
            $name,
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                // Check allowlist
                if (! empty($allowedModels) && ! in_array($name, $allowedModels)) {
                    return null;
                }

                return $class;
            }
        }

        return null;
    }
}
