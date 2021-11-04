# Shopware 6 Load-Testing with Locust

This repository contains several components that form our Shopware 6 Load-Testing product
based on the [load-testing tool Locust](https://locust.io).

1. The Locust Scenario (locustfile.py)
2. The "kraken" component that fetches all fixture data from a Shopware shop to run a load-test
3. a Shopware 6 plugin that provides an Admin API endpoint for retrieval of the load-testing fixtures.

## Manual Installation

Currently not much is automated:

* Copy the `plugin/` files to `custom/plugins/TidewaysLoadTesting` in a Shopware 6 shop
* Activate the plugin with:

    ```sh
    php bin/console plugin:refresh
    php bin/console plugin:install TidewaysLoadTesting
    php bin/console plugin:activate TidewaysLoadTesting
    ```
* Go into MySQL to create a role with permissions to access the load testing endpoint:

    ```sql
    insert into acl_role values (unhex('80bbf287ec7c41e5b998eeadacd0973d'), 'tideways.loadtesting', 'Access to Load Testing Fixture Generation API', '["tideways.loadtesting"]', NOW(), NOW());
    ```

* Create an integration under "Settings => Systems" in the admin with the new "tideways.loadtesting" role
* Copy the Access Key and Access Secret into a file `.env` in this repository:

    ```ini
    CLIENT_ID={$accessKey}
    CLIENT_SECRET={$accessSecret}
    ```

* Download the fixtures with `php download_fixtures.php`
* Run locust:

   ```sh
   locust --headless --host=https://shopware6.tideways.io --csv=shopware6.tideways.io --csv-full-history -u 2 -r 1 -t 1m
   ```

### Ansible Setup

For an inventory `digitalocean` with a clean Ubuntu server configured setting up shopware looks like this:

```
ansible-playbook -i ansible/digitalocean ansible/setup_shopware.yml
```
