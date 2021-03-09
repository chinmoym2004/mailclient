<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailTrackers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('platform')->nullable();
            $table->string('provider_token')->nullable();
            $table->string('expires_at')->nullable();
            $table->string('provider_refresh_token')->nullable();
            $table->boolean('enable_tracking')->default(0);
            $table->string('last_pulled')->nullable();
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
        Schema::dropIfExists('email_trackers');
    }
}
