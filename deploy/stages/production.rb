set :application, 'inside-2024.eikon.ch'

set :stage, :production
set :branch, :'refresh-2024'

set :deploy_to, -> { "/home/eikon/www/#{fetch(:application)}" }

set :opcache_file_url, "https://inside-2024.eikon.ch/opcache_clear.php"

set :ssh_options, {
  keys: %w(/.ssh/github-actions),
  forward_agent: true,
}

# Extended Server Syntax
# ======================
server 'ssh-eikon.alwaysdata.net', user: 'eikon', roles: %w{web app db}

fetch(:default_env).merge!(wp_env: :production)

SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"
