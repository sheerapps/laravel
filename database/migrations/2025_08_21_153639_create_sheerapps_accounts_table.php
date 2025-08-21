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
            $table->increments('id');
            $table->string('referral_code', 50)->nullable()->unique(); // Specify length
            $table->integer('telegram_id')->nullable()->unique();
            $table->string('name', 255);
            $table->string('username', 255)->nullable();
            $table->string('email', 191)->nullable()->unique(); // Use 191 for MySQL compatibility
            $table->string('password', 255)->nullable();
            $table->string('loginMethod', 50)->default('telegram'); // Specify length
            $table->timestamp('email_verified_at')->nullable();
            $table->string('photo_url', 500)->nullable(); // Specify length
            $table->string('api_token', 80)->nullable()->unique(); // Specify length
            $table->unsignedInteger('referrer_id')->nullable();
            $table->string('status', 50)->default('active'); // Specify length
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_ip_address', 45)->nullable(); // Specify length
            $table->text('login_history')->nullable();
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