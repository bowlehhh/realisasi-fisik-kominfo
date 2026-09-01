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
        Schema::dropIfExists('microsoft_connections');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('microsoft_connections', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->longText('access_token');
            $table->longText('refresh_token');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
