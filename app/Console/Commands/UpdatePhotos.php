<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Models\Vessel;
use DateTime;
use Illuminate\Console\Command;
use Intervention\Image\ImageManagerStatic as Image;

use Illuminate\Support\Facades\Storage;

class UpdatePhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:update-photos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create rectangle versions of photos with square versions';

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
    {/*
        $items = User::whereNotNull('photo')->get();
        echo "USERS: " . count($items);
        foreach ($items as $item) {
            $this->processImage('individuals', $item->id);
        }
        $items = Company::whereNotNull('photo')->get();
        echo "Company: " . count($items);
        foreach ($items as $item) {
            $this->processImage('companies', $item->id);
        }
        */
        $items = Vessel::whereNotNull('photo')->get();
        echo "Vessel: " . count($items);
        foreach ($items as $item) {
                echo $item->id . "\n";
            $this->processImage('vessels', $item->id);
        }
    }

    public function processImage($folder, $id) {
        if (Storage::disk('gcs')->exists('pictures/' . $folder . '/' . $id . '/cover.png' ) &&
            !Storage::disk('gcs')->exists('pictures/' . $folder . '/' . $id . '/cover_rect.jpg' )) {
            $file = Storage::disk('gcs')->get('pictures/' . $folder . '/' . $id . '/cover.png');
            $imageSqr = Image::make( $file );
            $imageSqr->fit(360, 290, function ($constraint) {
                    $constraint->upsize();
                }, 'bottom');
            $imageRect = Image::make($file  );
            $imageRect->fit(472, 265, function ($constraint) {
                    $constraint->upsize();
                }, 'bottom');
            if (Storage::disk('gcs')->put('pictures/' . $folder . '/' . $id . '/cover_sqr.jpg', (string)$imageSqr->encode('jpg'), 'public') &&
                Storage::disk('gcs')->put('pictures/' . $folder . '/' . $id . '/cover_rect.jpg', (string)$imageRect->encode('jpg'), 'public'))     {
                return true;
            }
        }
        return false;
    }
}
