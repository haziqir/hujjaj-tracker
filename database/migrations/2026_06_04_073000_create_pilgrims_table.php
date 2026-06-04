<?php

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
        Schema::create('pilgrims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('passport_no')->unique();
            $table->string('gender');
            $table->integer('age');
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->onDelete('set null');
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('set null');
            $table->string('emergency_contact');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilgrims');
    }
};
