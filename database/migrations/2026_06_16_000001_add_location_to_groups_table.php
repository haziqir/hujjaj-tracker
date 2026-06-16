<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->decimal('group_latitude', 10, 8)->nullable()->after('leader_id');
            $table->decimal('group_longitude', 11, 8)->nullable()->after('group_latitude');
            $table->timestamp('group_location_recorded_at')->nullable()->after('group_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn([
                'group_latitude',
                'group_longitude',
                'group_location_recorded_at',
            ]);
        });
    }
};
