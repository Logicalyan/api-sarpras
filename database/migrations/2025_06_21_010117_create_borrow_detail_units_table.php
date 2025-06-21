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
        Schema::create('borrow_detail_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrow_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_unit_id')->constrained()->onDelete('cascade');
            $table->enum('return_status', ['not_returned', 'pending_approval', 'returned', 'rejected'])->default('not_returned');
            $table->datetime('returned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_detail_units');
    }
};
