# Inside eikon

Website for the [eikon](https://eikon.ch) website.
Using Wordpress / Bedrock / ACF and Webpack.
Currently only headless, the front is served through GraphQL with NuxtJS, see [eikon.ch](https://github.com/eikon-frontend/eikon.ch)

## Pre-requist

- Composer -> https://getcomposer.org/download/
- Nodejs / npm -> https://nodejs.org/en/
- Yarn -> https://yarnpkg.com/getting-started/install

## Setup

Duplicate and edit the .env file to adapt the website to your configuration.

```
  cp .env.exemple .env
  vim .emv
```

Edit your host/vhost and database to reflect this configuration.

You must then install all php dependencies using composer:

```
  composer install
```

# Gutenberg blocks

The blocks are located in the `/web/app/mu-plugins` folder. Each dir begining with `eikonblocks-` is a folder containing the block's code and assets.

### Build all the block

```
  node build-all-blocks.js
```

### Deployment

Deploys are automatically launched using github actions when commit are pushed on the branch `main`

See more info at the deploy.yml file.
