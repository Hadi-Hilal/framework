<?php

namespace Loxi5\Framework\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Core\Models\Module;
use Modules\Core\Models\Theme;

class NewTheme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loxi5:theme
        {--folder= : What is the name of the folder of the new theme?}
        {--title= : What is the title of the new theme?}
        {--description= : What is the description of the new theme?}
        {--theme-version=1.0.0 : What is the version of the new theme?}
        {--license=MIT : What is the license of the new theme?}
        {--author= : What is the name of the author of the new theme?}
        {--email= : What is the email address of the author of the new theme?}
        {--url= : What is the url of the author of the new theme?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a new theme pack';

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
            'folder' => $this->option('folder') ? $this->option('folder') : $this->ask('What is the name of the folder of the new theme?'),
            'title' =>  $this->option('title') ? $this->option('title') : $this->ask('What is the title of the new theme?'),
            'description' =>  $this->option('description') ? $this->option('description') : $this->ask('What is the description of the new theme?'),
            'version' =>  $this->option('theme-version') ? $this->option('theme-version') : $this->ask('What is the version of the new theme?'),
            'license' =>  $this->option('license') ? $this->option('license') : $this->ask('What is the license of the new theme?'),
            'author' =>  $this->option('author') ? $this->option('author') : $this->ask('What is the name of the author of the new theme?'),
            'email' =>  $this->option('email') ? $this->option('email') : $this->ask('What is the email address of the author of the new theme?'),
            'url' =>  $this->option('url') ? $this->option('url') : $this->ask('What is the url of the author of the new theme?'),
            'is_default' => false
        ];
        $validations = [
            'folder' => ['required', 'unique:themes,folder'],
            'title' => ['required'],
            'description' => ['required'],
            'version' => ['required'],
            'license' => ['required'],
            'email' => ['nullable', 'email'],
            'url' => ['nullable', 'url'],
        ];
        $messages = [
            'folder.required' => __('Core::admin/theme.please_enter_the_folder_name_of_the_theme'),
            'folder.unique' => __('Core::admin/theme.please_folder_name_you_have_defined_already_exists'),
            'title.required' => __('Core::admin/theme.please_enter_the_title_of_the_theme'),
            'description.required' => __('Core::admin/theme.please_enter_the_description_of_the_theme'),
            'version.required' => __('Core::admin/theme.please_enter_the_version_info_of_the_theme'),
            'license.required' => __('Core::admin/theme.please_enter_the_license_info_of_the_theme'),
            'author.required' => __('Core::admin/theme.please_enter_the_name_of_the_author_of_the_theme'),
            'email.required' => __('Core::admin/theme.please_enter_the_email_address_of_the_author_of_the_theme'),
            'email.email' => __('Core::admin/theme.please_write_a_valid_email_address'),
            'url.required' => __('Core::admin/theme.please_enter_the_web_address_of_the_author_of_the_theme'),
            'url.url' => __('Core::admin/theme.please_write_a_valid_web_address'),
        ];
        $validator = Validator::make($params, $validations, $messages);

        if ($validator->fails()) {
            $this->info('New theme cannot be created! See error messages below:');

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        if (File::exists(base_path('themes/' . $params['folder']))) {
            File::deleteDirectory(base_path('themes/' . $params['folder']));
        }

        File::copyDirectory(base_path('themes/Default'), base_path('themes/' . $params['folder']));

        $modules = Module::get();

        foreach ($modules as $module) {
            if (File::exists(base_path('modules/' . $module->folder . '/Components/' . $params['folder']))) {
                File::deleteDirectory(base_path('modules/' . $module->folder . '/Components/' . $params['folder']));
            }
            if (File::isDirectory(base_path('modules/' . $module->folder . '/Components/Default'))) {
                File::copyDirectory(base_path('modules/' . $module->folder . '/Components/Default'), base_path('modules/' . $module->folder . '/Components/' . $params['folder']));
            }
            if (File::isDirectory(base_path('modules/' . $module->folder . '/Assets/Default'))) {
                File::copyDirectory(base_path('modules/' . $module->folder . '/Assets/Default'), base_path('modules/' . $module->folder . '/Assets/' . $params['folder']));
            }
        }

        if (File::exists(public_path('assets/Default'))) {
            File::copyDirectory(public_path('assets/Default'), public_path('assets/' . $params['folder']));
        }

        $webpack = File::get(base_path('themes/' . $params['folder'] . '/webpack.config.js'));
        $webpack = str_replace('const themeFolder = \'Default\';', 'const themeFolder = \'' . $params['folder'] . '\';', $webpack);
        $webpack = File::put(base_path('themes/' . $params['folder'] . '/webpack.config.js'), $webpack);

        $id = Theme::insertGetId($params);

        $theme = Theme::find($id);

        File::put(base_path('themes/' . $params['folder'] . '/theme.json'), $theme->toJson(JSON_PRETTY_PRINT));

        $this->alert('New theme has been successfully created!');
    }
}
