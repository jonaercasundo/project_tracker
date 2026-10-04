<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Bidding Document Folders
        |--------------------------------------------------------------------------
        */
        Schema::create('bidding_document_folders', function (Blueprint $table): void {
            $table->id();

            // project_information.id = BIGINT UNSIGNED
            $table->foreignId('project_information_id')
                ->constrained('project_information')
                ->cascadeOnDelete();

            // Self-reference to bidding_document_folders.id = BIGINT UNSIGNED
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('bidding_document_folders')
                ->cascadeOnDelete();

            $table->string('name', 120);

            // Physical storage folder identifier
            $table->uuid('storage_uuid')->unique();

            /*
             * IMPORTANT:
             * users.user_id is INT(11) SIGNED in the existing database.
             * Do NOT use foreignId() here because foreignId() creates
             * BIGINT UNSIGNED.
             */
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

        /*
        |--------------------------------------------------------------------------
        | Bidding Documents
        |--------------------------------------------------------------------------
        */
        Schema::create('bidding_documents', function (Blueprint $table): void {
            $table->id();

            // project_information.id = BIGINT UNSIGNED
            $table->foreignId('project_information_id')
                ->constrained('project_information')
                ->cascadeOnDelete();

            // bidding_document_folders.id = BIGINT UNSIGNED
            $table->foreignId('folder_id')
                ->nullable()
                ->constrained('bidding_document_folders')
                ->nullOnDelete();

            $table->string('display_name');
            $table->string('original_name');
            $table->string('stored_name');

            $table->string('storage_disk', 64);
            $table->string('storage_path')->unique();

            $table->string('mime_type', 150);
            $table->string('extension', 10);

            // Supports large files safely
            $table->unsignedBigInteger('file_size');

            $table->text('description')->nullable();

            // users.user_id = INT(11) SIGNED
            $table->integer('uploaded_by')->nullable();

            $table->unsignedInteger('version')->default(1);

            $table->timestamp('uploaded_at');

            $table->timestamps();

            $table->foreign('uploaded_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['project_information_id', 'folder_id', 'id'],
                'bidding_documents_folder_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Bidding Document Versions
        |--------------------------------------------------------------------------
        |
        | Stores previous versions whenever a document is replaced/uploaded
        | as a newer version.
        |
        */
        Schema::create('bidding_document_versions', function (Blueprint $table): void {
            $table->id();

            // bidding_documents.id = BIGINT UNSIGNED
            $table->foreignId('bidding_document_id')
                ->constrained('bidding_documents')
                ->cascadeOnDelete();

            $table->unsignedInteger('version');

            $table->string('original_name');
            $table->string('stored_name');

            $table->string('storage_disk', 64);
            $table->string('storage_path')->unique();

            $table->string('mime_type', 150);
            $table->string('extension', 10);

            $table->unsignedBigInteger('file_size');

            // users.user_id = INT(11) SIGNED
            $table->integer('uploaded_by')->nullable();

            $table->timestamp('created_at');

            $table->foreign('uploaded_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(
                ['bidding_document_id', 'version'],
                'bidding_document_version_unique'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Failed / Pending Physical File Deletions
        |--------------------------------------------------------------------------
        |
        | Used when the database record is deleted but the physical file could
        | not immediately be removed from storage.
        |
        */
        Schema::create('bidding_document_file_deletions', function (Blueprint $table): void {
            $table->id();

            // project_information.id = BIGINT UNSIGNED
            $table->foreignId('project_information_id')
                ->constrained('project_information')
                ->cascadeOnDelete();

            $table->string('storage_disk', 64);
            $table->string('storage_path');

            $table->unsignedInteger('attempts')->default(0);

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(
                ['storage_disk', 'storage_path'],
                'bidding_file_deletion_path_unique'
            );
        });
    }

    public function down(): void
    {
        /*
         * Drop child tables before parent tables because of foreign keys.
         */
        Schema::dropIfExists('bidding_document_file_deletions');
        Schema::dropIfExists('bidding_document_versions');
        Schema::dropIfExists('bidding_documents');
        Schema::dropIfExists('bidding_document_folders');
    }
};