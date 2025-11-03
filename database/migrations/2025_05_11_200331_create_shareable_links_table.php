<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareableLinksTable extends Migration
{
    public function up()
    {
        Schema::create('shareable_links', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('gallery_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique()->index();
            $table->boolean('download_enabled')->default(false);
            $table->json('restrictions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shareable_links');
    }
}