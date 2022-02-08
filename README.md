# Shopware 6 Benchmarking with Locust

> NOTE: This project is currently in beta and it is not recommended to run it
> against live production environments yet.

This repository contains several components for Shopware 6 performance
benchmarking based on the [load-testing tool Locust](https://locust.io).

Locust is run wrapped through a PHP 8.1 CLI tool that performs the necessary initializations
and manages the input and output data to the scenario. In addition Docker can be used to run
Locust in an isolated environment.

## Usage

```
composer install
php8.1 bin/sw-bench init "https://shopware-demo.example.com" -c default.json
php8.1 bin/sw-bench run -c default.json
```

sw-bench stores all fixture data necessary to run a benchmark in `$HOME/.swbench`
using a subdirectory for each configuration file.

Then after running the test you can generate a report with:

```
php8.1 bin/sw-bench generate-report -c default.json
```

The following fixture data is loaded:

* Crawling the sitemap.xml for products and listings pages
* Fetching individual options for country and salutation from the /account/register page

To generate a useful report, you also have to modify the config file to put in
information about versions, server hardware and more.

## Setup Locust Worker machine

A Locust worker machine has the following requirements:

* Locust + locust-plugins (pip package)
* PHP 8.1 + optionally [hdrhistogram-php](https://github.com/beberlei/hdrhistogram-php) package
* wkhtmltopdf

Alternatively you can skip Locust requirements if you use Docker as execution mode.

To install these components on Ubuntu:

```
sudo pip3 install locust locust-plugins
sudo apt install wkhtmltopdf
sudo apt install php8.1-cli php8.1-zip php8.1-curl php8.1-xml
```

### Execution Mode

You can run locust in two different execution modes: docker or local. By
default the locust command is run through a Docker container to provide
isolation and greatest possible support accross Linux and Mac platforms.

Change the execution mode:

```
php8.1 bin/sw-bench global-config executionMode local
php8.1 bin/sw-bench global-config executionMode docker
```

## LICENSE

This project is multi licenses.

- All PHP and Twig code in the `src/`, `tests/` and `templates/` folders is licensed AGPL 3.0 or later.
- All Python code in `locustfile.py` and `locusthelpers/` folder is MIT licensed.
