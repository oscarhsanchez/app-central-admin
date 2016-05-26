server '201.150.43.109', user: 'gpovallas', roles: %w{app db web}, my_property: :my_value
set :symfony_env,  "dev"
set :deploy_to, '/var/www/vhosts/vallas-admin'
set :tmp_dir, '/var/www/vhosts/vallas-admin'
set :controllers_to_clear, []
set :branch, "devel"