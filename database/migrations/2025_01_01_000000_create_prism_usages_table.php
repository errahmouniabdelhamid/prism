<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prism_usages', function (Blueprint $table): void {
            $table->id();
            $table->string('model');
            $table->string('provider')->nullable();
            $table->json('messages')->nullable();
            $table->json('response')->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('tool_results')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prism_usages');
    }
};
