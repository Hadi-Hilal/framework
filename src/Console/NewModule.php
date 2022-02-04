<?php

namespace Loxi5\Framework\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Core\Models\Module;

class NewModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loxi5:module
        {--folder= : What is the name of the folder of the new module?}
        {--title= : What is the title of the new module?}
        {--description= : What is the description of the new module?}
        {--module-version=1.0.0 : What is the version of the new module?}
        {--license=MIT : What is the license of the new module?}
        {--author= : What is the name of the author of the new module?}
        {--email= : What is the email address of the author of the new module?}
        {--url= : What is the url of the author of the new module?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a new module';

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
        $params = [
            'uuid' => Str::uuid()->toString(),
            'folder' => $this->option('folder') ? $this->option('folder') : $this->ask('What is the name of the folder of the new module?'),
            'title' =>  $this->option('title') ? $this->option('title') : $this->ask('What is the title of the new module?'),
            'description' =>  $this->option('description') ? $this->option('description') : $this->ask('What is the description of the new module?'),
            'version' =>  $this->option('module-version') ? $this->option('module-version') : $this->ask('What is the version of the new module?'),
            'license' =>  $this->option('license') ? $this->option('license') : $this->ask('What is the license of the new module?'),
            'author' =>  $this->option('author') ? $this->option('author') : $this->ask('What is the name of the author of the new module?'),
            'email' =>  $this->option('email') ? $this->option('email') : $this->ask('What is the email address of the author of the new module?'),
            'url' =>  $this->option('url') ? $this->option('url') : $this->ask('What is the url of the author of the new module?'),
            'is_active' => true,
            'is_core' => false
        ];
        $validations = [
            'folder' => ['required', 'unique:modules,folder'],
            'title' => ['required'],
            'description' => ['required'],
            'version' => ['required'],
            'license' => ['required'],
            'author' => ['required'],
            'email' => ['required', 'email'],
            'url' => ['required', 'url'],
        ];
        $messages = [
            'folder.required' => __('Core::admin/module.please_enter_the_folder_name_of_the_module'),
            'folder.unique' => __('Core::admin/module.please_folder_name_you_have_defined_already_exists'),
            'title.required' => __('Core::admin/module.please_enter_the_title_of_the_module'),
            'description.required' => __('Core::admin/module.please_enter_the_description_of_the_module'),
            'version.required' => __('Core::admin/module.please_enter_the_version_info_of_the_module'),
            'license.required' => __('Core::admin/module.please_enter_the_license_info_of_the_module'),
            'author.required' => __('Core::admin/module.please_enter_the_name_of_the_author_of_the_module'),
            'email.required' => __('Core::admin/module.please_enter_the_email_address_of_the_author_of_the_module'),
            'email.email' => __('Core::admin/module.please_write_a_valid_email_address'),
            'url.required' => __('Core::admin/module.please_enter_the_web_address_of_the_author_of_the_module'),
            'url.url' => __('Core::admin/module.please_write_a_valid_web_address'),
        ];
        $validator = Validator::make($params, $validations, $messages);

        if ($validator->fails()) {
            $this->info('New module cannot be created! See error messages below:');

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        $params['folder'] = Str::studly($params['folder']);

        if (!File::exists(base_path('modules/' . $params['folder']))) {
            File::makeDirectory(base_path('modules/' . $params['folder']));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Controllers'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Models'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Views'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Routes'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Config'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Lang'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets/Default'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets/Default/css'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets/Default/scss'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets/Default/sass'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets/Default/js'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets/Default/fonts'));
            File::makeDirectory(base_path('modules/' . $params['folder'] . '/Assets/Default/images'));

            $route = File::get(__DIR__ . '/../../stubs/routes.stub');

            File::put(base_path('modules/' . $params['folder'] . '/Routes/frontend.php'), $route);
            File::put(base_path('modules/' . $params['folder'] . '/Routes/admin.php'), $route);
            File::put(base_path('modules/' . $params['folder'] . '/Routes/widget.php'), $route);
            File::put(base_path('modules/' . $params['folder'] . '/Routes/api.php'), $route);



            File::put(base_path('modules/' . $params['folder'] . '/Config/config.php'), File::get(__DIR__ . '/../../stubs/config.stub'));
            File::put(base_path('modules/' . $params['folder'] . '/Config/group.php'), File::get(__DIR__ . '/../../stubs/config.stub'));


            File::put(base_path('modules/' . $params['folder'] . '/Assets/Default/css/frontend.css'), '');
            File::put(base_path('modules/' . $params['folder'] . '/Assets/Default/css/backend.css'), '');


            File::put(base_path('modules/' . $params['folder'] . '/Assets/Default/js/frontend.js'), '');
            File::put(base_path('modules/' . $params['folder'] . '/Assets/Default/js/backend.js'), '');
        }

        Module::insert($params);

        $module = Module::where('folder', $params['folder'])->first();

        File::put(base_path('modules/' . $params['folder'] . '/module.json'), $module->toJson(JSON_PRETTY_PRINT));

        $this->alert('New module has been successfully created!');
    }
}
