<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use Illuminate\Support\Str;

class GenerateMemberCardTokens extends Command
{
    protected $signature = 'members:generate-tokens';
    protected $description = 'Generate card tokens for members without tokens';

    public function handle()
    {
        $members = Member::whereNull('card_token')->get();
        
        if ($members->isEmpty()) {
            $this->info('All members already have card tokens.');
            return;
        }

        $count = 0;
        foreach ($members as $member) {
            $member->card_token = Str::random(32);
            $member->save();
            $count++;
        }

        $this->info("Generated {$count} card tokens.");
    }
}
