set :application, 'inside.eikon.ch'

set :stage, :production
set :branch, :main

set :deploy_to, -> { "/#{fetch(:application)}" }

set :opcache_file_url, "https://inside.eikon.ch/opcache_clear.php"

set :ssh_options, { forward_agent: true, user: "deploy", auth_methods: ['publickey'], keys: %w(~/.ssh/id_rsa) }

set :composer_install_flags, '--no-dev --prefer-dist --no-interaction --quiet --optimize-autoloader --ignore-platform-reqs'

# Extended Server Syntax
# ======================
server 'eikon.ch', user: 'ftpeik78749', roles: %w{web app db}, port: 2121

fetch(:default_env).merge!(wp_env: :production)

SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"
