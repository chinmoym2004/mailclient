<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NameSystemSps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_sps', function (Blueprint $table) {
            $table->id();
            $table->string('sp_name')->nullable();
            $table->longText('raw_query')->nullable();
            $table->longText('sp_details')->nullable();
            $table->boolean('migrated')->default(0);
            $table->text('in_fields')->nullable();
            $table->text('out_fields')->nullable();
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
        Schema::dropIfExists('system_sps');
    }
}
