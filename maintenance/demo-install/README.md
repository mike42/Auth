Auth demo install
-------------------

This directory contains an Ansible playbook for installing MariaDB, OpenLDAP and
the Auth web stack on a single machine for demonstrating & testing Auth.

The demo setup uses TLS with a self-signed certificate.

## Customise

Before you begin, you will need:

- Root access to a target machine over SSH (Debian Jessie or Ubuntu Trusty)
- A copy of Ansible installed on your local workstation

## Customise

Copy `inventory.example` to a new file called `inventory`, and update some
values:

- The hostname of the target box
- The install-time passwords

If you have not used ansible before, then start by running
`ssh-copy-id root@target.example` to avoid password prompts.

## Install

```
ansible-playbook -i inventory site.yml
```

## Use

Access the application over HTTPS in a web browser.

Log in as any of the users that you set in the inventory.

To get started quickly, locate "Utilities" -> "Directory Cleanup Tools" ->
"Create dummy data".

