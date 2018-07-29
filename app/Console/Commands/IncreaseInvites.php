<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Models\Account;
use App\Models\InviteCode;

class IncreaseInvites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stationhead:increase_invite_codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'up the invite codes!';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $accounts = Account::all();

        $accounts->each(function($account) {
            $this->generateSingleInvites($account);
            $this->updateGroupInvite($account);
        });
    }

    private function generateSingleInvites($account)
    {
        InviteCode::insert(array_map(function($inviteCode) use($account) {
            return [
                'code' => $inviteCode,
                'account_id' => $account->id,
                'initial_amount' => 1,
                'amount' => 1,
                'generated' => true
            ];
        }, $this->singleInviteCodes($account)));
    }

    private function generateGroupInvite($account)
    {
        InviteCode::create([
            'code' => $this->groupInviteCode($account),
            'account_id' => $account->id,
            'initial_amount' => config('stationhead.default_invites.group_invite'),
            'amount' => config('stationhead.default_invites.group_invite'),
            'generated' => true
        ]);
    }

    private function singleInviteCodes($account)
    {
        return array_map(function ($num) use($account) {
            return $account->handle.substr(hash("md4", ($account->inviteCodes()->count() + $num - 1)), 0, 4);
        }, range(1,10));
    }

    private function updateGroupInvite($account)
    {
        $groupInvite = $account->inviteCodes()
            ->where('initial_amount', '>', 1)->where('generated', true)->first();

        if (is_null($groupInvite)) { return; }

        $groupInvite->update(['initial_amount' => 20, 'amount' => 20]);
    }
}
