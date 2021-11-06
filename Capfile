# default deploy_config_path is 'config/deploy.rb'
set :deploy_config_path, 'deploy/config.rb'
# default stage_config_path is 'config/deploy'
set :stage_config_path, 'deploy/stages'

# Load DSL and Setup Up Stages
require 'capistrano/setup'

# Includes default deployment tasks
require 'capistrano/deploy'
require "capistrano/scm/git"
install_plugin Capistrano::SCM::Git

# Load tasks from gems
require 'capistrano/composer'
