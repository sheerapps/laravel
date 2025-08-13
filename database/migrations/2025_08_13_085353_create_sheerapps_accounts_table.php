<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSheerappsAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('sheerapps_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('photo_url')->nullable();
            $table->integer('referrer_id')->unsigned()->nullable();
            $table->string('api_token', 80)->unique()->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sheerapps_accounts');
    }
}
