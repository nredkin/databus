<?php

use App\Domain\Enums\MessageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('message_id', 64)->unique();
            $table->enum('message_code', MessageCode::names());
            $table->string('error_message', 255)->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->longText('payload')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
