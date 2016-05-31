# config valid only for current version of Capistrano
lock '3.5.0'

set :application, 'vallas-admin'
set :repo_url, 'git@bitbucket.org:vallas/vallas-admin.git'
set :scm, :git

set :format, :pretty
set :log_level, :debug

set :keep_releases, 3

set :linked_files, ["app/config/parameters.yml"]
set :linked_dirs, ["var", "web/media", "web/uploads", "tmp"]
set :permission_method, :acl
set :file_permissions_users, ["www-data"]
set :file_permissions_paths, ["var", "web/media", "web/uploads", "tmp"]

set :composer_install_flags, "--prefer-dist --no-interaction --optimize-autoloader"

before "deploy:updated", "deploy:set_permissions:chmod"

namespace :deploy do

  after :restart, :clear_cache do
    on roles(:web), in: :groups, limit: 3, wait: 10 do
      # Here we can do anything such as:
      # within release_path do
      #   execute :rake, 'cache:clear'
      # end
    end
  end

end
