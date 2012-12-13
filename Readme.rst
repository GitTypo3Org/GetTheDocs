Necessary packages on the server that need to be installed:

* aptitude install zip - command zip
* RestTools.git
* aptitude install openoffice.org - command soffice
* daemon soffice converter listening to 8100

IMPORTANT: make sure apache can use the soffice daemon. Maybe run apache2 as an other user as www-data

::

	# Command to be run as root

	# Get the Umask
	umask

	# Set a new one
	umask 0002

	# Create default structure
	mkdir upload files
	chown -R render:www-default {upload,files}
	chmod -R 775 {upload,files}

	# Protect the root page
	touch {upload,files}/index.html

Starting OO in headless with Version OpenOffice 3.0 ++
=========================================================
sudo apt-get install openoffice.org-headless

http://code.google.com/p/openmeetings/wiki/OpenOfficeConverter


Web server configuration
=========================

* Make sure the upload limit is not too low

::

	upload_max_filesize 20M


Phar editing could be enabled

	# na /etc/php5/cli/php.ini
	phar.readonly = Off

Apache security
=========================

Avoid PHP file to be executed. Probably more security would be good to avoid Apache to run a malicious script.

<Directory "/home/render/files">
        php_flag engine off
</Directory>
<Directory "/home/render/upload">
        php_flag engine off
</Directory>

Debug
=====

::

	# jump to the root directory
	j GetTheDocs
	cd Resources/Private/GetTheDocsClient/bin/
	php get-the-docs.php


Build a new Phar
=================

::

	# jump to the root directory
	j GetTheDocs

	# Generate a new Phar into Build
	php download.php

	ls -l Build

Feature Tests
=============

::

	./Tests/feature-tests.sh

	# To display the command
	./Tests/feature-tests.sh --dry-run