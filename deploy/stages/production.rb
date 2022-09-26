set :application, 'inside.eikon.ch'

set :stage, :production
set :branch, :main

set :deploy_to, -> { "/home/eikon/www/#{fetch(:application)}" }

set :opcache_file_url, "https://inside.eikon.ch/opcache_clear.php"

# Extended Server Syntax
# ======================
server 'ssh-eikon.alwaysdata.net', user: 'eikon', roles: %w{web app db}

fetch(:default_env).merge!(wp_env: :production)

SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"
