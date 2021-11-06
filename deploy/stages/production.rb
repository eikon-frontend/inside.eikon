set :application, 'eikon.mingus.space'

set :stage, :production
set :branch, :main

set :deploy_to, -> { "/home/jminguely/www/#{fetch(:application)}" }

set :opcache_file_url, "https://eikon.mingus.space/opcache_clear.php"

# Extended Server Syntax
# ======================
server 'ssh-jminguely.alwaysdata.net', user: 'jminguely', roles: %w{web app db}

fetch(:default_env).merge!(wp_env: :production)

SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"
