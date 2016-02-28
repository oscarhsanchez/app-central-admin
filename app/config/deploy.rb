# deploy.rb
set :stages,        %w(production development)
set :default_stage, "error"
set :stage_dir,     "app/config/DeployStages"
require 'capistrano/ext/multistage'

set   :application,   "Vallas Admin"
set   :app_path,      "app"

## GIT
set   :scm,           :git
set   :repository,    "git@bitbucket.org:vallas/vallas-admin.git"

## Speed Up
# Fetching only the changes since last deploy, not cloning all repository
set :deploy_via, :remote_cache
set :copy_exclude, [ '.git' ]

## Composer
set :use_composer, true # Run composer after the deploy
set :vendors_mode, "install"
set :update_vendors, false
set :composer_options,  "--verbose --optimize-autoloader --no-progress --ignore-platform-reqs"

# We want independent releases and we do not share vendors but we want fast deploy: http://capifony.org/cookbook/speeding-up-deploy.html
before 'symfony:composer:install', 'composer:copy_vendors'

namespace :composer do
  task :copy_vendors, :except => { :no_release => true } do
    capifony_pretty_print "--> Copy vendor file from previous release"

    run "vendorDir=#{current_path}/vendor; if [ -d $vendorDir ] || [ -h $vendorDir ]; then cp -a $vendorDir #{latest_release}/vendor; fi;"
    capifony_puts_ok
  end
end

## Permissions
set :writable_dirs,       ["app/cache", "app/logs", "web/temp", "web/uploads", "tmp"]
set :permission_method,   :acl
set :use_set_permissions, true
set :webserver_user, "www-data"
set :use_sudo,      false
after "deploy:set_permissions", "rb:permissions"
after "symfony:cache:clear", "rb:permissions"

## Database
set :model_manager, "doctrine"

namespace :rb do
    namespace :dev do
        task :enable do
            capifony_pretty_print '--> Enabling access to dev envirnoment'
            run "cp #{latest_release}/../../shared/cached-copy/web/app_dev.php #{latest_release}/web"
            capifony_puts_ok
        end

        task :disable do
            capifony_pretty_print '--> Disabling access to dev envirnoment'
            run "rm #{latest_release}/web/app_dev.php"
            capifony_puts_ok
        end
    end
    task :permissions do
        capifony_pretty_print '--> Force cache and log permissions'
        run "chmod -R 777 #{latest_release}/app/cache"
        run "chmod -R 777 #{latest_release}/app/logs"
        run "chmod -R 777 #{latest_release}/web/temp"
        run "chmod -R 777 #{latest_release}/web/uploads"
        capifony_puts_ok
    end
    task :reload_apache do
        run "service apache2 reload";
    end
    task :echo_params do
        capifony_pretty_print '--> Show parameters.yml'
        run "more #{latest_release}/app/config/parameters.yml"
    end
end

set :shared_files,        ["app/config/parameters.yml"]
set :shared_children,     [app_path + "/logs", web_path + "/temp", web_path + "/uploads", "tmp"]




set   :keep_releases, 5
after "deploy", "rb:reload_apache"
after "rb:reload_apache", "deploy:cleanup"

set :interactive_mode,      false

# IMPORTANT = 0
# INFO      = 1
# DEBUG     = 2
# TRACE     = 3
# MAX_LEVEL = 3
logger.level = 3