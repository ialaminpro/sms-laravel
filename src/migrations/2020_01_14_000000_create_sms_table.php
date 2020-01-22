<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSMSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client_id',80)->unique();
            $table->enum('type',['amount','sms'])->default('sms');
            $table->enum('mask',['enable','disable'])->default('disable');
            $table->double('balance',12,2)->default(1);
            $table->double('masking_rate',3,2)->default(1);
            $table->double('no_masking_rate',3,2)->default(1);
            $table->enum('status',['Active','Inactive','Banned'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms');
    }
}
