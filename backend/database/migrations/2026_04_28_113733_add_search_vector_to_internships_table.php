<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE internships ADD COLUMN IF NOT EXISTS search_vector tsvector');
            DB::statement('CREATE INDEX IF NOT EXISTS internships_search_vector_index ON internships USING gin(search_vector)');

            DB::statement('DROP TRIGGER IF EXISTS internships_search_vector_update ON internships');
            DB::statement("
                CREATE TRIGGER internships_search_vector_update BEFORE INSERT OR UPDATE
                ON internships FOR EACH ROW EXECUTE FUNCTION
                tsvector_update_trigger(search_vector, 'pg_catalog.indonesian', title, description);
            ");

            // Populate existing records.
            DB::statement('UPDATE internships SET updated_at = NOW()');

            return;
        }

        Schema::table('internships', function (Blueprint $table) {
            if (! Schema::hasColumn('internships', 'search_vector')) {
                $table->text('search_vector')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS internships_search_vector_update ON internships');
            DB::statement('DROP INDEX IF EXISTS internships_search_vector_index');
            DB::statement('ALTER TABLE internships DROP COLUMN IF EXISTS search_vector');
        } else {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropColumn('search_vector');
            });
        }
    }
};
