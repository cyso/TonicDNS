![TonicDNS Logo](http://github.com/Cysource/TonicDNS/raw/master/tonic_dns_logo.png)

TonicDNS RESTful API for PowerDNS
=================================

| :exclamation: This project is dead and will not be updated. It was made at a time that PowerDNS did not have any form of API. |
|-------------------------------------------------------------------------------------------------------------------------------|

| :memo: We recommend using the [internal PowerDNS REST API](https://doc.powerdns.com/authoritative/http-api/index.html). |
|-------------------------------------------------------------------------------------------------------------------------|

This a RESTful API for PowerDNS licensed under GPL. It uses the MIT licensed Tonic RESTful library as its base, see peej/tonic on GitHub. All changes to the core Tonic library are released under the original MIT license. See the files for the appropriate license.

Features:

* Token based communication.
* Option to store tokens in the PowerDNS database, or in a separate SQLite database.
* Should work with any PowerDNS backend database, tested with MySQL.
* Supports adding of zones, records and zone and record templates.
* Atomic commits, all modifications are validated on input and executed in a single transaction.

Requirements:

* Working PowerDNS 2.9 or 3.0 installation, with database schemas similar to those found in the PowerDNS docs.
* Apache 2.0 or 2.2 with your favorite method of PHP processing (has been tested with mod_php5 and mpm_itk/mpm_prefork).
* Apache SSL and Rewrite module.
* Appropriate php modules for the chosen backend. Optionally sqlite support if you choose to store tokens in a sqlite db.

Tested with:

* Ubuntu 10.04 LTS 64-bit with Apache 2.2.14, mod_php / PHP 5.3.2 and MySQL Server 5.1.41.

Quick Install Guide
===================

This installation assumes you already have a working PowerDNS 2.9 or 3.0 installation. This guide also assumes that you use MySQL for both PowerDNS and the users/tokens for TonicDNS. For the relevant SQL table structures, see db/tables.sql.

Clone the repo.

```bash
$ git clone git://github.com/Cysource/TonicDNS.git
```

Create a new VirtualHost in Apache. I recommend using mod_itk and using a separate user account for running TonicDNS. I also recommend running the communication over SSL, as TonicDNS doesn't provide any encryption on its own (nor will it ever). The example below assumes you follow these recommendations. 

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

Unit Test Dependencies
======================

PHPLoc
------
https://github.com/sebastianbergmann/phploc/
```
# pear config-set auto_discover 1
# pear install pear.phpunit.de/phploc
```

Pdepend
-------
http://pdepend.org/download/index.html
```
# pear channel-discover pear.pdepend.org
# pear install pdepend/PHP_Depend-beta
```

PHPmd
-----
http://phpmd.org/download/index.html
```
# pear channel-discover pear.phpmd.org
# pear channel-discover pear.pdepend.org
# pear install --alldeps phpmd/PHP_PMD
```

PHPcs
-----
http://pear.php.net/package/PHP_CodeSniffer
```
# pear install PHP_CodeSniffer
```

PHPcpd
------
https://github.com/sebastianbergmann/phpcpd/
```
# pear config-set auto_discover 1
# pear install pear.phpunit.de/phpcpd
```

PHPdoc
------
http://www.phpdoc.org/
```
# pear channel-discover pear.phpdoc.org
# pear install phpdoc/phpDocumentor-alpha
```

PHPcb
-----
http://blog.thinkphp.de/archives/464-PHP_CodeBrowser-Release-version-0.1.0.html
```
# pear channel-discover pear.phpunit.de
# pear install --alldeps phpunit/PHP_CodeBrowser 
# pear install channel://pear.php.net/Text_Highlighter-0.7.3
```

Running Unit Tests
==================

To run the whole test suite:

```
$ ant
```

To run just the unit tests:

```
$ ant phpunit
```

Check the `build` folder for the results of the test suite.
