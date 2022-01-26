# Setup Shopware 6 Demostore with Ansible

This Ansible setup can be used as a basis to automatically setup a new and
clean Shopware 6 based on the
[shopware/production](https://github.com/shopware/production/) template.

You need Ansible 2.9+ to use this and create an Ansible inventory that defines your server
structure:

```
benchmark.shopware6-1 ansible_ssh_host=167.172.104.85 ansible_ssh_user=root

[web]
benchmark.shopware6-1

[mysql]
benchmark.shopware6-1

[redis]
benchmark.shopware6-1

[elastic]
benchmark.shopware6-1

[all:vars]
shopware_version="6.4.7.0"
shopware_url="https://mydemo.example.com"
tideways.api_key=foo
```

The hosts must be reachable via SSH either through configuration of
`ansible_ssh*` variables in the inventory or by setting them up in
`.ssh/config`.

This example runs the whole Shopware 6 shop on a single server
"benchmark.shopware6-1". But you can also create multi server setups by
assigning different hosts to the four Ansible groups "web", "mysql", "redis"
and "elastic".

DNS Resolving from the configured `shopware_url` domain to the servers is not
part of this setup.
