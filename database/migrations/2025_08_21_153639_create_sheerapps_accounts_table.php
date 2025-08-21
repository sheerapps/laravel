<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSheerappsAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sheerapps_accounts', function (Blueprint $table) {
            $table->increments('id'); // Laravel 5.8 uses increments() instead of id()
            $table->string('referral_code')->nullable()->unique();
            $table->integer('telegram_id')->nullable()->unique();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('loginMethod')->default('telegram');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('api_token')->nullable()->unique();
            $table->unsignedInteger('referrer_id')->nullable(); // Use unsignedInteger for Laravel 5.8
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_ip_address')->nullable();
            $table->text('login_history')->nullable(); // Use text instead of json for Laravel 5.8
            $table->timestamps();
            
            // Foreign key constraint for self-referencing referral system
            $table->foreign('referrer_id')->references('id')->on('sheerapps_accounts')->onDelete('set null');
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
