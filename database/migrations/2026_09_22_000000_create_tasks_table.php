<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Task', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Title');
            $table->text('Description')->nullable();
            $table->date('DueDate');
            $table->boolean('IsCompleted')->default(false);
            $table->unsignedBigInteger('UserId')->nullable()->index();
            $table->unsignedBigInteger('CustomerId')->nullable()->index();
            $table->unsignedBigInteger('DocumentId')->nullable()->index();
            $table->timestamps();

            $table->index(['IsCompleted', 'DueDate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Task');
    }
};
