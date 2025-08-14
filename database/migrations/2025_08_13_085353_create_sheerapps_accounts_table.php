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
            $table->string('telegram_id', 191)->unique();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('api_token', 64)->nullable();
            $table->unsignedBigInteger('referrer_id')->nullable();
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('sheerapps_accounts')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sheerapps_accounts');
    }
}
