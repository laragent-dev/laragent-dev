<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_session_id');
            $table->foreign('agent_session_id')
                ->references('id')
                ->on('agent_sessions')
                ->cascadeOnDelete();
            $table->enum('type', ['think', 'tool_call', 'tool_result', 'final_answer', 'error', 'system']);
            $table->longText('content');
            $table->string('tool_name')->nullable();
            $table->json('tool_parameters')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->float('duration_ms')->nullable();
            $table->timestamps();

            $table->index('agent_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_logs');
    }
};
