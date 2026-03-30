<?php

declare(strict_types=1);

return [

    'navigation' => [
        'promotion' => 'Promotion',
        'catalog' => 'Catalog',
        'operations' => 'Operations',
        'refunds' => 'Refunds',
        'public_site' => 'Public site',
    ],

    'receipts' => [
        'label' => 'Receipt submission',
        'plural' => 'Receipt submissions',
        'actions' => [
            'manage' => 'Manage',
            'under_review' => 'Move to under review',
            'approve' => 'Approve submission',
            'reject' => 'Reject submission',
            'awaiting_user' => 'Request information from participant',
            'payment_failed' => 'Record failed bank transfer',
            'mark_paid' => 'Confirm transfer completed',
            'confirm_mark_paid_heading' => 'Mark this submission as paid?',
            'confirm_mark_paid_description' => 'Only confirm after the bank transfer to the participant has completed successfully.',
        ],
        'workflow_header' => [
            'payment_failed_message_label' => 'Bank / processor message',
        ],
        'workflow_footer' => [
            'section_heading' => 'Review before decision',
            'section_description' => 'Use these actions after you have checked the receipt image, AP code, and purchase summary above.',
            'rejection_reason_label' => 'Rejection reason (visible to participant)',
            'awaiting_user_message_label' => 'Instructions for the participant',
        ],
        'infolist' => [
            'purchase_section' => 'Purchase on receipt',
            'purchase_section_description' => 'AP code and purchased product lines as declared by the participant (quantity, unit price, line total, and campaign refund rules).',
            'purchased_products' => 'Purchased products',
            'col_on_promotion' => 'On campaign',
            'col_on_promotion_hint' => 'Product is attached to this submission’s promotion',
            'col_product_code' => 'Product code',
            'col_quantity' => 'Qty',
            'col_product_price' => 'Unit price',
            'col_line_subtotal' => 'Line total',
            'col_refund_per_unit' => 'Refund / unit',
            'col_expected_refund' => 'Expected refund',
            'footer_purchase_total' => 'Total paid for products',
            'footer_refund_total' => 'Total expected refund',
            'no_lines' => 'No product lines were declared.',
            'on_promotion_yes' => 'On this campaign',
            'on_promotion_no' => 'Not on this campaign',
            'no_receipt_image' => 'No receipt image was uploaded.',
            'image_placeholder' => 'Image not available',
        ],
        'workflow' => [
            'paid_only_from_payment_pending' => 'Paid status can only be confirmed while the receipt is waiting for bank transfer.',
            'unsupported_admin_transition' => 'The selected status is not available from the Filament quick action set.',
            'invalid_source_status' => 'This receipt is not in a state that allows the requested transition.',
            'note_required' => 'An administrator note is required for this action.',
        ],
        'workflow_error' => [
            'title' => 'Unable to update submission',
            'body' => 'Something went wrong while updating this submission. Please try again or contact support if the problem continues.',
        ],
        'status' => [
            'pending' => 'Pending',
            'under_review' => 'Under review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'appealed' => 'Appealed',
            'awaiting_user_information' => 'Awaiting user information',
            'payment_pending' => 'Payment pending',
            'paid' => 'Paid',
            'payment_failed' => 'Payment failed',
        ],
        'columns' => [
            'uploaded_at' => 'Uploaded at',
        ],
        'filters' => [
            'campaign_scope' => 'Campaign',
            'campaign_all' => 'All (historical)',
            'campaign_active_window' => 'Active (upload end + 7 days processing)',
        ],
        'workflow_form' => [
            'section_context' => 'Submission context',
            'section_context_description' => 'Read-only snapshot for daily processing. Open “Manage” for the receipt image and full detail.',
            'section_context_description_edit' => 'Read-only snapshot of the participant and campaign. Purchase lines and receipt image are below; use Manage for workflow actions.',
            'section_notes' => 'Notes',
            'section_notes_description' => 'Administrator note. Save the form to persist changes without changing status.',
            'section_receipt_image' => 'Receipt image',
            'section_correction' => 'Corrections',
            'section_correction_description' => 'Change status and notes freely for data fixes. Prefer the Manage page for normal workflow transitions.',
            'admin_note_helper' => 'Internal or participant-facing note depending on how you use it.',
        ],
    ],

    'refund_exports' => [
        'label' => 'Refund export',
        'plural' => 'Refund exports',
        'generate' => 'Generate bank export',
        'download_zip' => 'Download ZIP',
        'last_error' => 'Last error',
        'queued' => [
            'title' => 'Export queued',
            'body' => 'We are building the bank CSV batches and ZIP in the background. When it is ready we will email you a secure download link. Watch for the notification above; until the export finishes you can open View on the new row only — Download ZIP appears after completion.',
        ],
        'status' => [
            'label' => 'Status',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'done' => 'Done',
            'failed' => 'Failed',
        ],
        'items' => [
            'recipient_name' => 'Recipient name',
            'bank_account' => 'Bank account',
            'refund_amount' => 'Refund amount',
        ],
        'no_eligible_before_queue' => [
            'title' => 'Nothing to export for this period',
        ],
        'generate_failed' => [
            'title' => 'Could not start export',
            'body' => 'An unexpected error occurred while preparing the export. Please try again. Contact support if this keeps happening.',
        ],
        'generator' => [
            'no_eligible_receipts' => 'No approved receipts are waiting for export in the selected purchase date range (or they are already included in a pending/paid batch).',
            'attach_failed' => 'The export could not be prepared. Please try again or contact support if the problem continues.',
        ],
        'form' => [
            'period_start' => 'Purchase date from',
            'period_end' => 'Purchase date to',
        ],
        'item_payment' => [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
        ],
    ],

    'promotions' => [
        'label' => 'Promotion',
        'plural' => 'Promotions',
        'products_relation' => 'Promotional products',
        'attach_product' => 'Attach product',
        'currency_suffix' => 'USD',
        'configured_refund_value' => 'Configured refund value',
        'refund_value' => [
            'helper_percent' => 'Enter a percent between 0 and 100 (exclusive of 0; maximum 100%). Shown with a % suffix.',
            'helper_fixed' => 'Amount per purchased unit in USD. Cannot exceed the product list price; must be greater than zero.',
            'must_be_positive' => 'The refund value must be greater than zero.',
            'percent_not_above_100' => 'Percent refunds cannot exceed 100%.',
            'product_price_missing' => 'The selected product has no valid list price; fixed refunds cannot be validated.',
            'fixed_above_price' => 'Fixed refund per unit cannot exceed the product list price (:max USD).',
        ],
        'refund_kind' => [
            'fixed' => 'Fixed amount per purchased unit',
            'percent' => 'Percent of line subtotal',
        ],
        'validation' => [
            'purchase_end_after_or_equal' => 'The purchase period end must be on or after the purchase period start.',
            'upload_start_after_or_equal' => 'The upload period start must be on or after the purchase period start.',
            'upload_end_after_or_equal' => 'The upload period end must be on or after :date.',
        ],
    ],

    'products' => [
        'label' => 'Product',
        'plural' => 'Products',
    ],

    'faqs' => [
        'label' => 'FAQ',
        'plural' => 'FAQs',
    ],

    'contact_messages' => [
        'label' => 'Contact request',
        'plural' => 'Contact requests',
        'columns' => [
            'asked_at' => 'Question asked at',
            'answered_at' => 'Question answered at',
            'replied_by' => 'Answered by',
            'user_phone' => 'Phone number',
        ],
        'confirm_send' => [
            'heading' => 'Send this reply?',
            'description' => 'The visitor will be able to see this message as your official response. This action cannot be undone from the contact form.',
            'submit' => 'Submit reply',
        ],
        'infolist' => [
            'section_from' => 'From',
            'section_from_description' => 'Who sent this request and when it arrived.',
            'section_message' => 'Message',
            'section_message_description' => 'Subject and full text from the public contact form.',
            'section_reply' => 'Your reply',
            'section_reply_description' => 'The response sent to the visitor.',
        ],
        'filter' => [
            'reply_status' => 'Reply status',
            'all' => 'All',
            'unanswered' => 'Unanswered',
            'answered' => 'Answered',
        ],
        'actions' => [
            'view' => 'View',
            'answer' => 'Answer',
            'back' => 'Back',
            'answer_question' => 'Answer question',
        ],
    ],

];
