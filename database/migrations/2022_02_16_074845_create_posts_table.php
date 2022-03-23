<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignID('user_id')  //Users의 user_id를 참조한다
                ->constrained()
                ->onDelete('cascade');  //같이 삭제 요청
            $table->string('title');
            $table->string('event');
            $table->integer('time');
            $table->float('calorie');
            $table->float('average_speed');
            $table->integer('altitude');
            $table->float('distance');
            $table->string('img')->nullable();
            $table->string('content')->nullable();
            $table->string('range');
            $table->string('track_id')->nullable();
            $table->string('gps_id');
            $table->integer('mmr');
            $table->string('kind');
            $table->date('date');
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
        Schema::dropIfExists('posts');
    }
}
