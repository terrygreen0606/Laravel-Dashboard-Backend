<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyAddress;
use Geocoder\Laravel\Facades\Geocoder;
use Illuminate\Console\Command;

class GeoCodeCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:geocode-companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'GeoCode Company Addresses.';

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
        $companyAddresses = CompanyAddress::whereNotNull('street')->where('street', '<>', '')->get();//->whereNull('latitude')->whereNull('longitude')
        foreach ($companyAddresses as $companyAddress) {
            $geocoder = app('geocoder')->geocode($companyAddress->street . ' ' . $companyAddress->city . ' ' . $companyAddress->state . ' ' . $companyAddress->country . ' ' . $companyAddress->zip)->get()->first();
            if ($geocoder) {
                $coordinates = $geocoder->getCoordinates();
                $companyAddress->latitude = $coordinates->getLatitude();
                $companyAddress->longitude = $coordinates->getLongitude();
                $companyAddress->save();
            }
        }
        $this->line('Everything geocoded :)');
        return true;
    }
}
