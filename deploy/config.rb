set :repo_url, 'git@github.com:jminguely/inside.eikon.git'

set :branch, :main

set :log_level, :info

set :linked_files, fetch(:linked_files, []).push('.env', 'web/.htaccess')
set :linked_dirs, fetch(:linked_dirs, []).push('web/content/uploads')
set :linked_dirs, fetch(:linked_dirs, []).push('storage/framework/sessions')

# In your config/deploy.rb file add the following task
namespace :deploy do
	desc 'Create a temporary PHP file to clear the opcache.'
	task :clear_cache do
	 	on roles(:app) do
	 		within fetch(:release_path) do
				opcache_file = "#{fetch(:release_path)}/web/opcache_clear.php"
				execute :touch, "#{opcache_file}"
				execute :chmod, "-R 644 #{opcache_file}"
				execute :echo, "'<?php opcache_reset(); ?>' > #{opcache_file}"
				execute :curl, "-s #{fetch(:opcache_file_url)} "
			end
		end
	end
  task :yarn_build do
	 	on roles(:app) do
      execute "yarn --cwd '#{fetch(:release_path)}/web/app/themes/inside'"
      execute "yarn --cwd '#{fetch(:release_path)}/web/app/themes/inside' build"
    end
  end
end
after 'composer:run', 'deploy:yarn_build'
after 'deploy:finished', 'deploy:clear_cache'





