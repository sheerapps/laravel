<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompleteSheerappsAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sheerapps_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('referral_code', 50)->nullable()->unique();
            $table->bigInteger('telegram_id')->nullable()->unique();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('loginMethod')->default('telegram');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('api_token')->nullable()->unique();
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending_verification'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_ip_address')->nullable();
            $table->json('login_history')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('referrer_id')->references('id')->on('sheerapps_accounts')->onDelete('set null');
            
            // Indexes
            $table->index('referral_code');
            $table->index('telegram_id');
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sheerapps_accounts');
    }
}
