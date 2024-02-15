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
            $table->unsignedBigInteger('user_id')->unique();

            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('address');
            $table->string('phone_number')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_person');
            $table->string('logo_path')->nullable()->default('N/A');
            $table->string('banner_path')->nullable()->default('N/A');
            $table->json('social_networks')->nullable();
            $table->enum('status', ['Active', 'Blocked']);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
