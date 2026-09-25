<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Task', function (Blueprint $table) {
            $table->unsignedBigInteger('CompletedByUserId')->nullable()->after('IsCompleted')->index();
            $table->dateTime('CompletedAt')->nullable()->after('CompletedByUserId');
        });
    }

    public function down(): void
    {
        Schema::table('Task', function (Blueprint $table) {
            $table->dropIndex(['CompletedByUserId']);
            $table->dropColumn(['CompletedByUserId', 'CompletedAt']);
        });
    }
};
