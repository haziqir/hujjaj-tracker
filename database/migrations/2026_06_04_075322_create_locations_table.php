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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            // Link to the pilgrim (using 'pilgrims' table)
            $table->foreignId('pilgrim_id')->constrained('pilgrims')->onDelete('cascade');

            // Use decimal for high precision coordinates
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            // recorded_at is your custom timestamp for when the GPS coordinate was taken
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
