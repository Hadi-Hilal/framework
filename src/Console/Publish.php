<?php

namespace Loxi5\Framework\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Core\Models\Module;

class Publish extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loxi5:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes module translations and configurations';

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
     * @return int
     */
    public function handle()
    {
        $modules = Module::get();
        foreach ($modules as $module) {
            $this->info('Publishing language files of module: ' . $module->folder);
            if (!File::isDirectory(base_path('modules/' . $module->folder . '/Lang'))) {
                $this->info('No language files have been found for module: ' . $module->folder);
            } else {
                $files = File::allFiles(base_path('modules/' . $module->folder . '/Lang'));
                foreach ($files as $file) {
                    $params = explode('/', $file->getRelativePath(), 2);
                    $phrases = __($module->folder . '::' . (isset($params[1]) ? $params[1] . '/' : '') . $file->getFilenameWithoutExtension(), [], $params[0]);
                    if (!is_array($phrases)) {
                        $phrases = [];
                    }
                    if (!File::isDirectory(base_path('lang/vendor/' . $module->folder . '/' . $file->getRelativePath()))) {
                        File::makeDirectory(base_path('lang/vendor/' . $module->folder . '/' . $file->getRelativePath()), 0755, true);
                    }
                    File::put(base_path('lang/vendor/' . $module->folder . '/' . $file->getRelativePath() . '/' . $file->getFilename()), "<?php\n\nreturn json_decode('" . json_encode($phrases, JSON_HEX_APOS) . "', true);\n");
                }
            }
            $this->info('Publishing configuration files of module: ' . $module->folder);
            File::put(config_path($module->folder . '.php'), "<?php\n\nreturn json_decode('" . json_encode(config($module->folder), JSON_HEX_APOS) . "', true);\n");
        }

        $this->info('Phrases and configurations published successfully...');
    }
}
