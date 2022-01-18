# Shopware 6 Benchmarking with Locust

This repository contains several components that form our Shopware 6 benchmarking product
based on the [load-testing tool Locust](https://locust.io).

Locust is run wrapped through a PHP 8.1 cli tool that performs the necessary initializations
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

The following fixture data is loaded:

* Crawling the sitemap.xml for products and listings pages
* Fetching individual options for country and salutation from the /account/register page

To generate a useful report, you also have to modify the config file to put in
information about versions, plugins, server hardware and more.