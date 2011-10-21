TonicDNS RESTful API for PowerDNS
=================================

This a RESTful API for PowerDNS licensed under GPLv2. It uses the MIT licensed Tonic RESTful library as its base, see peej/tonic on GitHub.

Features:

* Token based communication.
* Option to store tokens in the PowerDNS database, or in a seperate SQLite database.
* Should work with any PowerDNS backend database, tested with MySQL.
* Supports adding of zones, records and zone and record templates.
* Atomic commits, all modifications are validated on input and executed in a transaction.

Requirements:

* Working PowerDNS 2.9 or 3.0 installation, with schemas similar to those found in the PowerDNS docs.
* Apache 2.0 or 2.2 with your favorite method of PHP processing (has been tested with mod_php5 and mpm_itk/mpm_prefork).
* Apache SSL and Rewrite module.
* Appropriate php modules for the chosen backend. Optionally sqlite support if you choose to store tokens in a sqlite db.

Quick Install Guide
===================

This installation assumes you already have a working PowerDNS 2.9 or 3.0 installation.

Clone the repo.

```bash
$ git clone git://github.com/Cysource/TonicDNS.git
```

Create a new VirtualHost in Apache. I recommend using mod_itk and using a seperate user account for running TonicDNS. I also recommend running the communication over SSL, as TonicDNS doesn't provide any encryption on its own (nor will it ever). The example below assumes you follow these recommendations. 

```
<VirtualHost *:80>
        ServerName      <hostname>
		AssignUserId	tonicdns tonicdns

        RedirectPermanent / https://<hostname>

        ErrorLog        <tonicdns location>/log/error.log
        CustomLog       <tonicdns location>/log/access.log combined
</VirtualHost>

<VirtualHost *:443>
        ServerName      <hostname>
		AssignUserId	tonicdns tonicdns

        ErrorLog        <tonicdns location>/log/error.log
        CustomLog       <tonicdns location>/log/access.log combined

        SSLEngine On
        SSLCertificateFile      <path to SSL certificate>

        DocumentRoot    <tonicdns location>/docroot
        <Directory <tonicdns location>>
                AllowOverride All
                Options +FollowSymLinks
                Order allow,deny
                Allow from all
        </Directory>
</VirtualHost>
```

Configure TonicDNS.

```bash
$ cd conf
conf $ cp database.conf.php.default database.conf.php
conf $ cp logging.conf.php.default logging.conf.php
conf $ cp validator.conf.php.default validator.conf.php
conf $ vim *.conf.php
```

