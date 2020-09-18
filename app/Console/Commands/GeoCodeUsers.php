<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Console\Command;

class GeoCodeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:geocode-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GeoCode User Addresses.';

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
        $userAddresses = UserAddress::whereNotNull('street')
            ->where('street', '<>', '')
            ->orderBy('id', 'desc')
            ->whereNull('latitude')->whereNull('longitude')
            ->get(); 
        foreach ($userAddresses as $userAddress) {
            $geocoder = app('geocoder')->geocode($userAddress->street . ' ' . $userAddress->city . ' ' . $userAddress->state . ' ' . $userAddress->country . ' ' . $userAddress->zip)->get()->first();
            if ($geocoder) {
                print_r($userAddress->user_id);
                $coordinates = $geocoder->getCoordinates();
                print_r($coordinates);
                $userAddress->latitude = $coordinates->getLatitude();
                $userAddress->longitude = $coordinates->getLongitude();
                $userAddress->save();
            }
        }
        $this->line('Everything geocoded :)');
        return true;
    }
}
