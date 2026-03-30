<?php

declare(strict_types=1);

return [

    'receipt_bank_transfer_failed' => [
        'subject' => 'Your refund transfer could not be completed',
        'greeting' => 'Hello :name,',
        'participant' => 'participant',
        'intro' => 'We attempted to send your promotional refund to the bank account we have on file, but the transfer did not succeed (for example, the account number may be incorrect or rejected by the bank).',
        'bank_line' => 'The account we used: :account',
        'campaign_line' => 'Campaign: :campaign',
        'button' => 'Update your receipt submission',
        'outro' => 'Please sign in to correct your submission or bank details in your profile if needed, then save your changes so we can try the transfer again.',
        'salutation' => 'Kind regards,',
    ],

    'receipt_paid' => [
        'subject' => 'Your promotional refund has been paid',
        'greeting' => 'Hello :name,',
        'participant' => 'participant',
        'intro' => 'We have marked your receipt submission as paid. The refund transfer for this campaign should now be complete.',
        'campaign_line' => 'Campaign: :campaign',
        'button' => 'View your receipt submission',
        'outro' => 'If anything looks wrong, sign in and contact us using the details on the site.',
        'salutation' => 'Kind regards,',
    ],

    'receipt_rejected' => [
        'subject' => 'Your receipt submission was not accepted',
        'greeting' => 'Hello :name,',
        'participant' => 'participant',
        'intro' => 'After review, we could not accept this receipt submission for the campaign.',
        'campaign_line' => 'Campaign: :campaign',
        'note_heading' => 'Message from the team:',
        'button' => 'View your receipt submission',
        'outro' => 'You can sign in to read the full status and any next steps.',
        'salutation' => 'Kind regards,',
    ],

    'receipt_awaiting_information' => [
        'subject' => 'Action needed: more information for your receipt submission',
        'greeting' => 'Hello :name,',
        'participant' => 'participant',
        'intro' => 'We need additional information or corrections before we can continue processing your receipt submission.',
        'campaign_line' => 'Campaign: :campaign',
        'instruction_heading' => 'Instructions:',
        'button' => 'View your receipt submission',
        'outro' => 'Please sign in, update your profile or submission as needed, and reply to any messages from our team.',
        'salutation' => 'Kind regards,',
    ],

    'refund_export_ready' => [
        'subject' => 'Your bank refund export is ready',
        'intro' => 'The refund export you requested has finished processing. You can download the ZIP archive (CSV batches for the bank) using the button below.',
        'period_line' => 'Purchase date range: :from — :to',
        'rows_line' => 'Rows included: :count',
        'button' => 'Download export (signed link)',
        'expires_line' => 'This link expires on :date.',
        'outro' => 'You can also open the export in the admin panel and download it while signed in.',
        'salutation' => 'Kind regards,',
    ],

    'refund_export_failed' => [
        'subject' => 'Refund export failed',
        'intro' => 'Export #:id could not be completed after repeated attempts.',
        'reason_heading' => 'Details',
        'technical_heading' => 'Technical detail',
        'outro' => 'Please review the export record in the admin panel, fix any data issues, and try again.',
        'salutation' => 'Kind regards,',
    ],

];
