<?php

namespace App\Console\Commands;

use App\Models\CompanyAddress;
use App\Models\UserAddress;
use App\Models\Vessel;
use Illuminate\Console\Command;

class BulkZoneTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:bulk-zone-test {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the zones for everything using them.';

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
        $type = $this->option('type');
        if($type == 'vessel'){
            $vessels_model = Vessel::whereNotNull('ais_lat')->whereNotNull('ais_long');
            $vessels_count = $vessels_model->count();
            echo $vessels_count;
            $vessels = $vessels_model->get();
            foreach ($vessels as $vessel) {
                usleep(100);
                $vessel->zone_id = getGeoZoneID($vessel->ais_lat, $vessel->ais_long);
                $vessel->save();
                echo $vessel->id . '-' . $vessel->zone_id .  PHP_EOL;
            }
            $this->line('Updated Vessel Zones: ' . $vessels_count);
        }else

        if($type == 'company'){
            $this->updateCompanyapCountry();
            $company_addresses_model = CompanyAddress::whereNotNull('latitude')->whereNotNull('longitude');
            $company_addresses_count = $company_addresses_model->count();
            $company_addresses = $company_addresses_model->get();
            foreach ($company_addresses as $company_address) {

                usleep(100);
                $company_address->zone_id = getGeoZoneID($company_address->latitude, $company_address->longitude);
                $company_address->save();
                echo $company_address->id . '-' . $company_address->zone_id .  PHP_EOL;
            }

            $this->line('Updated Company Addresses: ' . $company_addresses_count);
        }else

        if($type = 'users'){
            $user_addresses_model = UserAddress::whereNotNull('latitude')->whereNotNull('longitude');
            $user_addresses_count = $user_addresses_model->count();
            $user_addresses = $user_addresses_model->get();
            foreach ($user_addresses as $user_address) {
                usleep(100);
                $user_address->zone_id = getGeoZoneID($user_address->latitude, $user_address->longitude);
                $user_address->save();
                echo $user_address->id . '-' . $user_address->zone_id .  PHP_EOL;
            }
            $this->line('Updated User Addresses: ' . $user_addresses_count);
        }
        return true;
    }

    function updateCompanyCountry(){
        $companyAddresses = CompanyAddress::all();
        foreach ($companyAddresses as $company){
            $country = CodeCountryByCountryName($company['country']);
            $company['country'] = isset($country) ? $country : $company['country'];
            $company->save();
            echo $company['country'] . PHP_EOL;
        }
    }
}
