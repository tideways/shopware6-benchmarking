# Shopware 6 Benchmarking with Locust

This repository contains several components that form our Shopware 6 benchmarking product
based on the [load-testing tool Locust](https://locust.io).

Locust is run wrapped through a PHP 8.1 cli tool that performs the necessary initializations
and manages the input and output data to the scenario.

## Installation

```
composer install
php8.1 bin/sw-bench init "https://shopware-demo.example.com" -c default.json
php8.1 bin/sw-bench run -c default.json
```