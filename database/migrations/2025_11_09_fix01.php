<?php

use App\Domain\Enums\MessageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE messages MODIFY COLUMN message_code ENUM('
            . implode(',', array_map(fn($val) => "'$val'", MessageCode::names()))
            . ')'
        );
    }
};
