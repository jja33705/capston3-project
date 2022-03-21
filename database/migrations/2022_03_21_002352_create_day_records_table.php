<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDayRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('day_records', function (Blueprint $table) {
            $table->id();
            $table->foreignID('user_id')  //Users의 user_id를 참조한다
                ->constrained()
                ->onDelete('cascade');  //같이 삭제 요청
            $table->float("Mon")->nullable();
            $table->float("Tue")->nullable();
            $table->float("Wed")->nullable();
            $table->float("Tur")->nullable();
            $table->float("Fri")->nullable();
            $table->float("Sat")->nullable();
            $table->float("Sun")->nullable();
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
        Schema::dropIfExists('day_records');
    }
}
