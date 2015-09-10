<?php

namespace Imamuseum\Harvester\Console\Commands;

use Illuminate\Console\Command;
use Imamuseum\Harvester\Commands\HarvestImages;
use Imamuseum\Harvester\Models\Object;
use Imamuseum\Harvester\Contracts\HarvesterInterface;

class HarvestObjectCommand extends Command
{
    use \Illuminate\Foundation\Bus\DispatchesCommands;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'harvest:object
                            {--id= : The Laravel database id of the object.}
                            {--uid= : The unique id of object from source data.}
                            {--imagesOnly : Set  if you only want to update images.}
                            {--source=null : Option for multi source data sync.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harvest images and/or data for a specific object.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(HarvesterInterface $harvester)
    {
        $this->harvester = $harvester;
        parent::__construct();
    }

    public function handle()
    {
        // if id is set find object
        if ($this->option('id')) {
            $id =  $this->option('id');
            $object = Object::findOrFail($id);
        }

        // if accession is set find object
        if ($this->option('uid')) {
            $object_uid =  $this->option('object');
            $object = Object::where('object_uid', '=', $object_uid)->firstOrFail();
        }

        // if data is set to true harvest data
        if (! $this->option('imagesOnly')) {
            $source = $this->option('source');
            $this->info('Processing data and images for "' . $object->object_title . '".');
            $this->harvester->initialOrUpdateObject($object->object_uid, 'sync', $source);
        }

        // if images is set to true harvest images
        if ($this->option('imagesOnly')) {
            $this->info('Processing images for "' . $object->object_title . '".');
            config(['queue.default' => 'sync']);
            // Queue command to process images
            $command = new HarvestImages($object->id);
            $this->dispatch($command);
        }
    }
}