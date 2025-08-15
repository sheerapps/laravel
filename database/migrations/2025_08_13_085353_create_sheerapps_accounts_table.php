<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSheerappsAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('sheerapps_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('telegram_id');
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('api_token', 64)->nullable();
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->text('login_history')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sheerapps_accounts');
    }
}
