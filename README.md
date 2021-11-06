# Inside eikon

Website for the eikon 2021 open house. Using Wordpress / Bedrock / Timber / ACF and Webpack.

More info:

- https://wordpress.org/
- https://roots.io/bedrock/
- https://github.com/timber/timber
- https://www.advancedcustomfields.com/
- https://webpack.js.org/

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

## Dev

Most of the work will be done in the theme located `at web/app/themes/inside`.

To edit and compile the frontend (css/js) go there:

```
  cd web/app/themes/inside
```

Before you can compile the assets, you need to install the depenencies with yarn:

```
  yarn
```

then you can use the following commands to build or watch the assets

### Build

```
  yarn build
```

### Watch

```
  yarn watch
```

### Deployment

Deploys are automatically launched using github actions when commit are pushed on the branch `main`

You can manually deploy the project using:

```
cap production deploy
```
