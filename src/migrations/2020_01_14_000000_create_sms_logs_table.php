<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSMSLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client_id',80)->nullable();
            $table->string('mobile',20)->nullable();
            $table->text('msg')->nullable();
            $table->integer('length')->nullable();
            $table->integer('count')->nullable();
            $table->string('mask',255)->nullable();
            $table->string('campaign',256)->nullable();
            $table->string('type',30)->nullable();
            $table->text('error_log')->nullable();
            $table->string('status',30)->nullable();
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
