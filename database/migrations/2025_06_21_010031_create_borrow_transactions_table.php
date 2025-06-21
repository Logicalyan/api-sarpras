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
        Schema::create('borrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('borrow_code')->unique();
            $table->date('borrow_date');
            $table->date('return_date');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('status', ['borrowed', 'partial', 'returned', 'not_applicable'])->default('not_applicable');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('approved_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->datetime('returned_at')->nullable();
            $table->timestamps();

            $table->index('approval_status');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_transactions');
    }
};
