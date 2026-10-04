<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiddingDocumentFileDeletion extends Model
{
    protected $fillable = ['project_information_id', 'storage_disk', 'storage_path', 'attempts', 'last_error'];

    protected $hidden = ['storage_disk', 'storage_path', 'last_error'];
}
