<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserRole;
use App\Models\User;
use App\Models\ContactHasType;

class ChangeRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:change-role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        //
        $userRoles = UserRole::all();

        ContactHasType::where('contact_type_id', 0)->update([
            'contact_type_id' => 7
        ]);
        foreach($userRoles as $userRole)
        {
            if($userRole->role_id == 2 || $userRole->role_id == 8) {
                User::where('id', $userRole->user_id)->update([
                    'role_id' => 7
                ]);    
            } else if ($userRole->role_id == 9) {
                User::where('id', $userRole->user_id)->update([
                    'role_id' => 5
                ]);
            } else if($userRole->role_id == 5) {
                User::where('id', $userRole->user_id)->update([
                    'role_id' => 2
                ]);
            }else {
                User::where('id', $userRole->user_id)->update([
                    'role_id' => $userRole->role_id
                ]);
            }

            $contactUsers = ContactHasType::where('contact_id', $userRole->user_id)->get();
            if(!count($contactUsers)) {
                ContactHasType::create([
                    'contact_id' => $userRole->user_id,
                    'contact_type_id' => 7
                ]);
            }
        }

        echo "success!";
    }
}
