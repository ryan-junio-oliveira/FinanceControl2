<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->string('type'); // email | ip
            $table->timestamp('created_at')->nullable();
            $table->unique(['identifier', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_blacklist');
    }
};