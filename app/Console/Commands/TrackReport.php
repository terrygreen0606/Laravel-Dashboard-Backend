<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\App;

class TrackReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdt:track-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'track add, delete, update for our db.';

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
        // $pdf = App::make('snappy.pdf.wrapper');
        // $pdf->setPaper('A4');
        // $pdf->setOption('margin-bottom', '1cm');
        // $pdf->setOption('margin-top', '2cm');
        // $orientation = 'portrait';
        // $pdf->setOption('margin-right', '1cm');
        // $pdf->setOption('margin-left', '1cm');
        // $pdf->setOption('enable-javascript', true);
        // $pdf->setOption('enable-smart-shrinking', true);
        // $pdf->setOption('no-stop-slow-scripts', true);
        $fileName = 'Test.pdf';
        // $pdf->setOrientation($orientation);
        $pdf = PDF::loadView('reports.track');
        $pdf->download($fileName);
    }
}
