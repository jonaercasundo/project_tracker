<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidding_document_folders', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('project_information_id')
                ->constrained('project_information')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('bidding_document_folders')
                ->cascadeOnDelete();

            $table->string('name', 120);

            $table->uuid('storage_uuid')->unique();

            // users.user_id is signed INT(11)
            $table->integer('created_by')->nullable();

            $table->timestamps();

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['project_information_id', 'parent_id', 'name'],
                'bidding_folders_parent_name_index'
            );
        });

        Schema::create('bidding_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_information_id')->constrained('project_information')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('bidding_document_folders')->nullOnDelete();
            $table->string('display_name');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('storage_disk', 64);
            $table->string('storage_path')->unique();
            $table->string('mime_type', 150);
            $table->string('extension', 10);
            $table->unsignedBigInteger('file_size');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->index(['project_information_id', 'folder_id', 'id'], 'bidding_documents_folder_index');
        });

        Schema::create('bidding_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bidding_document_id')->constrained('bidding_documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('storage_disk', 64);
            $table->string('storage_path')->unique();
            $table->string('mime_type', 150);
            $table->string('extension', 10);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('created_at');
            $table->unique(['bidding_document_id', 'version'], 'bidding_document_version_unique');
        });

        Schema::create('bidding_document_file_deletions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_information_id')->index();
            $table->string('storage_disk', 64);
            $table->string('storage_path');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['storage_disk', 'storage_path'], 'bidding_file_deletion_path_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidding_document_file_deletions');
        Schema::dropIfExists('bidding_document_versions');
        Schema::dropIfExists('bidding_documents');
        Schema::dropIfExists('bidding_document_folders');
    }
};
