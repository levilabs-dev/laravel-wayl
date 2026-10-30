<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wayl_links', function (Blueprint $table) {
            $table->id();
            $table->string('merchant')->default('default');
            $table->string('reference_id');
            $table->string('wayl_id')->unique();
            $table->string('code')->nullable()->index();
            $table->string('status')->default('Created')->index();
            $table->unsignedBigInteger('total');
            $table->string('currency', 3)->default('IQD');
            $table->string('type')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('reversable')->nullable();
            $table->string('custom_parameter')->nullable();
            $table->text('url')->nullable();
            $table->text('webhook_url')->nullable();
            $table->text('redirection_url')->nullable();
            $table->string('link_expires_in')->nullable();
            $table->string('environment')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('gateway_created_at')->nullable();
            $table->timestamp('gateway_updated_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->nullableMorphs('payable');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['merchant', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wayl_links');
    }
};
