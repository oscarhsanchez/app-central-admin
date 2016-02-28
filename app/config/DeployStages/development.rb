set :domain,      "201.150.43.109"
set :server,      "201.150.43.109"
set :deploy_to,   "/var/www/vhosts/vallas-admin"
set :backup_path, "/var/www/vhosts/vallas-admin"

set :branch,      "devel"

## SSH
set :user, 'gpovallas'
set :webserver_user, "www-data"

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain, :primary => true       # This may be the same as your `Web` server
role :db,         domain, :primary => true       # This may be the same as your `Web` server

# before "symfony:cache:warmup", "print_migrations_title"
# after "print_migrations_title", "symfony:doctrine:migrations:migrate"
# after "symfony:doctrine:migrations:migrate", "print_migrations_ok"
# task :print_migrations_title do
#     capifony_pretty_print '--> Migrating database if it\'s necessary'
# end
# task :print_migrations_ok do
#     capifony_puts_ok
# end

namespace :db do
  desc "Load development data into local database"
  task :load_development_data, :roles => :db, :only => { :primary => true }, :except => { :no_release => true } do
    require 'yaml'
    # Gets db yml from server, because we don't store it on dev boxes!
    get "#{current_path}/app/config/parameters.yml", "tmp/remote_parameters.yml"
    remote_config = YAML::load_file('tmp/remote_parameters.yml')
    local_config = YAML::load_file('app/config/parameters.yml')

    # Dump server sql
    filename = "dump.#{Time.now.strftime '%Y-%m-%d_%H:%M:%S'}.sql"
    server_dump_file = "#{current_path}/tmp/#{filename}"
    on_rollback { delete server_dump_file }
    run "mysqldump -u #{remote_config['parameters']['database_user']} --password=#{remote_config['parameters']['database_password']} #{remote_config['parameters']['database_name']} > #{server_dump_file}" do |channel, stream, data|
      puts data
    end

    # Compress file for quicker transfer
    run "gzip #{server_dump_file}"
    get "#{server_dump_file}.gz", "tmp/#{filename}.gz"

    puts "Uncompressing local db dump file"
    `gunzip tmp/#{filename}.gz`
    puts "Loading locally..."
    `mysql -u #{remote_config['parameters']['database_user']} --password=#{local_config['parameters']['database_password']} #{local_config['parameters']['database_name']} < tmp/#{filename}`
    puts "Cleaning up temp files"
    `rm -f tmp/#{filename}`
    `rm -f tmp/remote_parameters.yml`
  end
end
