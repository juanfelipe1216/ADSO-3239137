<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE lab_equipment.equipment ADD CONSTRAINT ck_equipment_total_positive CHECK (total_units >= 1)');
        DB::statement("ALTER TABLE lab_equipment.\"user\" ADD CONSTRAINT ck_user_role CHECK (role IN ('admin', 'lab_technician'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE lab_equipment.equipment DROP CONSTRAINT ck_equipment_total_positive');
        DB::statement('ALTER TABLE lab_equipment."user" DROP CONSTRAINT ck_user_role');
    }
};
