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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->string('type'); // WFH, Office, Hybrid
            $table->string('location')->nullable();
            $table->string('salary_range')->nullable();
            $table->date('deadline_at')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->timestamps();
            $table->softDeletes();

            // PostgreSQL Full-Text Search Vector
            if (config('database.default') === 'pgsql') {
                $table->rawIndex("(to_tsvector('simple', title || ' ' || description))", 'internships_search_index');
            }
        });

        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->json('education')->nullable();
            $table->json('skills')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('portfolio_path')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('internship_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, reviewing, accepted, rejected
            $table->text('cover_letter')->nullable();
            $table->json('timeline')->nullable();
            $table->string('cv_snapshot')->nullable();
            $table->string('portfolio_snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_tables_batch_5_6');
    }
};
