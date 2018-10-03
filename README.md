# skeleton-core

## Description

This library contains the core functionality of Skeleton.

## Installation

Installation via composer:

    composer require tigron/skeleton-core

## Howto

Initialize the application directory

    \Skeleton\Core\Config::$application_dir = $some_very_cool_directory;

Hostname configuration

	admin.test.example.be
	admin.\*.example.be
	admin.\*.example.be/admin

	If the uri is admin.test.example.be/admin,
	the first one will match because of the exact match of the hostname
