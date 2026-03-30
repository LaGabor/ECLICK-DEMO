<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqDemoSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'question' => 'How do I submit a receipt?',
                'answer' => 'Log in, open the active promotion, upload a clear photo of your receipt, and enter the required purchase details.',
                'sort_order' => 10,
                'active' => true,
            ],
            [
                'question' => 'When will I receive my refund?',
                'answer' => 'After approval, refunds are batched for bank transfer. You will see status updates on your submission in the portal.',
                'sort_order' => 20,
                'active' => true,
            ],
            [
                'question' => 'What if my receipt is rejected?',
                'answer' => 'You will see a reason in your account. You can appeal or provide corrected information when the workflow allows it.',
                'sort_order' => 30,
                'active' => true,
            ],
        ];

        foreach ($rows as $row) {
            Faq::query()->firstOrCreate(
                ['question' => $row['question']],
                $row,
            );
        }
    }
}
