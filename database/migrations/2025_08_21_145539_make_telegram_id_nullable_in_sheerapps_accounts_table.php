<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeTelegramIdNullableInSheerappsAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sheerapps_accounts', function (Blueprint $table) {
            $table->integer('telegram_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sheerapps_accounts', function (Blueprint $table) {
            $table->integer('telegram_id')->nullable(false)->change();
        });
    }
}
