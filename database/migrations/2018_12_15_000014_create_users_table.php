<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('home_number');
            $table->string('mobile_number');
            $table->string('occupation');
            $table->string('photo')->nullable()->default(null);
            $table->string('resume_link')->nullable()->default(null);
            $table->text('description')->nullable()->default(null);
            $table->boolean('active')->default(1);
            $table->unsignedInteger('smff_service_id')->nullable()->default(null);
            $table->unsignedInteger('vendor_id')->nullable()->default(null);
            $table->unsignedInteger('company_id')->nullable()->default(null);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['smff_service_id'], 'smff_service_id');
            $table->index(['vendor_id'], 'vendor_id');
            $table->index(['company_id'], 'company_id');

            $table->foreign('smff_service_id', 'users_smff_service_id')
                ->references('id')->on('smff_services')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('vendor_id', 'users_vendor_id')
                ->references('id')->on('vendors')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('company_id', 'users_company_id')
                ->references('id')->on('companies')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
