<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailFieldsToSheerappsAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sheerapps_accounts', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('username');
            $table->string('password')->nullable()->after('email');
            $table->string('loginMethod')->default('telegram')->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('loginMethod');
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
            $table->dropColumn(['email', 'password', 'loginMethod', 'email_verified_at']);
        });
    }
}
