<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $collection): void {
            $collection->sparse('code', ['unique' => true]);
        });

        Schema::create('profiles', function (Blueprint $collection): void {
            $collection->unique('code');
            $collection->index('section_ids');
        });

        Schema::create('sections', function (Blueprint $collection): void {
            $collection->unique('code');
            $collection->unique('key');
        });

        Schema::create('products', function (Blueprint $collection): void {
            $collection->unique('code');
        });

        Schema::create('audit_logs', function (Blueprint $collection): void {});

        Schema::create('api_tokens', function (Blueprint $collection): void {
            $collection->unique('token_hash');
            $collection->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('profiles');

        Schema::table('users', function (Blueprint $collection): void {
            $collection->dropIndexIfExists('code_1');
        });
    }
};
