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
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            // Foreign key linking to the pilgrims table
            $table->foreignId('pilgrim_id')->constrained('pilgrims')->onDelete('cascade');

            // Coordinates for the emergency location
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            // Status tracking
            $table->string('status')->default('pending'); // Options: pending, assigned, resolved
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sos_alerts');
    }
};
