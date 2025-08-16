<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferralCodeToSheerappsAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sheerapps_accounts', function (Blueprint $table) {
            $table->string('referral_code', 50)->nullable()->unique()->after('id');
            $table->index(['referral_code']);
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
            $table->dropIndex(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
}
