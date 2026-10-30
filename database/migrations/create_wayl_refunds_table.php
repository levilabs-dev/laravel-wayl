<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wayl_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->nullable()->constrained('wayl_links')->nullOnDelete();
            $table->string('merchant')->default('default');
            $table->string('refund_id')->unique();
            $table->string('reference_id')->nullable()->index();
            $table->string('wayl_link_id')->nullable()->index();
            $table->text('reason');
            $table->unsignedBigInteger('amount');
            $table->string('initiated_by')->nullable();
            $table->string('status')->default('Requested')->index();
            $table->string('refund_method')->nullable();
            $table->string('account_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wayl_refunds');
    }
};
