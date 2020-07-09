<?php

use Illuminate\Database\Migrations\Migration;

class CreateRejectReasonMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('tbl_menu')->insert(
            ['name' => 'RejectReason', 'link' => '/rejectreasons', 'icon' => 'repeat', 'created_at' => now()]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('tbl_menu')->where('name', 'RejectReason')->delete();
    }
}