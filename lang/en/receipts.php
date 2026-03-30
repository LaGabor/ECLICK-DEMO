<?php

declare(strict_types=1);

return [

    'show' => [
        'title' => 'Receipt submission',
        'status_label' => 'Current status:',
        'note_heading' => 'Message from the team',
        'profile_link' => 'Update your profile or bank details',
        'receipt_image_heading' => 'Receipt scan',
        'upload_heading' => 'Replace receipt image',
        'upload_hint' => 'JPEG or PNG only. Maximum 10 MB upload; the server compresses the stored file.',
        'upload_submit' => 'Upload',
        'upload_invalid_type' => 'Please choose a JPEG or PNG image.',
        'upload_too_large' => 'File is too large (max 10 MB).',
    ],

    'upload' => [
        'image_queued' => 'Your receipt image was received and is being processed. Refresh in a few seconds if it does not appear yet.',
    ],

];
