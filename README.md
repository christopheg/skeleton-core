# skeleton-core

## Description

This library contains the core functionality of `skeleton`. It handles the
bootstrapping and, when used in a web context, it will receive and handle the
HTTP request.

The package will automatically detect "applications", which are separate parts
of your project. Each application can implement its own user interface.
Applications are detected based on the URL requested by the user. Usually based
on the hostname, `skeleton-core` will search for the matching application.

For example, one could create one application for a public website while a
second application can handle the administrative interface. The public
application would for example listen on `www.example.com`, while the
administrative interface would listen for requests to `admin.example.com`.

The request will then be finalized by the matching module in the application. By
default, the module is matched by mapping the URI on the local filesystem path,
but this behaviour can be altered using `routes`.

One special case, is handling of so-called `media` files. When a media file is
requested, detection of which happens based on the requested file's extension,
it will be served from the `media/image`, `media/javascript` or `media/css` in
the matching application directory.

## Installation

Installation via composer:

    composer require tigron/skeleton-core

## Configuration

### Start

Initialize the application directory

    \Skeleton\Core\Config::$application_dir = $some_very_cool_directory;

From then on, every directory in your `/app/` folder will be seen as a fully
independent `\Skeleton\Core\Application`.

Within your `/app/<app name>`/ folder, you are expected to adhere to the
structure below.

    - app
      - your application's folder
        - config
          - Config.php
        - event
        - module
        - template
        - media
          - css
          - javascript
          - image

If you want media files to be served from an additional directory, for example
if you have a package manager such as `bower`, `yarn` or even the
`fxp/composer-asset-plugin`, you can specify an additional directory which will
be searched in addition to the default `media` one.

    \Skeleton\Core\Config::$asset_dir = $my_frontend_library_directory;


### Config.php

The application's configuration is done by means of the `Config.php` file, found
in `app/<app name>/config`. It should have the following structure:

    <?php
    /**
     * Sample application configuration class
     */

    class Config_Admin extends Config {

    	/**
    	 * Config array
    	 *
    	 * @var array
    	 * @access private
    	 */
    	protected $config_data = [

    		/**
    		 * Hostnames
    		 */
    		'hostnames' => ['*'],

    		/**
    		 * base_uri
    		 */
    		'base_uri' => '',

    		/**
    		 * Default language. If no language is requested
    		 */
    		'default_language' => 'en',

    		/**
    		 * Routes
    		 */
    		'routes' => []
    	];
    }

In addition to any setting you define yourself, the following options can be set
through this configuration file and will be taken into account by
`skeleton-core`:

#### hostnames

An array of hostnames for which this application will handle requests. An
asterisk can be used as a wildcard. Example:

    [ 'admin.test.example.be', 'admin.*.example.be' ]

#### base_uri

Specifies the base uri for the application. If a base_uri is specified, it will
be included in the reverse url generation. Example:

    '/admin'

#### default_language

This is the ISO 639-1 code for the default language to be used, if no more
specific one can be found.

#### routes

An array which maps `routes` to `modules`. A route definition cab be used to
generate pretty URL's, or even translated versions. Usage is best described by
an example.

    [
        'web_module_index' => [
            '$language/default/route/to/index',
            '$language/default/route/to/index/$action',
            '$language/default/route/to/index/$action/$id',
            '$language[en]/test/routing/engine',
            '$language[en]/test/routing/engine/$action',
            '$language[en]/test/routing/engine/$action/$id',
            ],
    ],

## Usage

### Routing to the correct application

Based on the `Host`-header in the request, the correct application will be
started. This is where the `hostnames` array in the application's configuration
file (shown above) will come into play.

If `skeleton-core` could find a matching application based on the `Host`-header
supplied in the request, this is the application that will be started.

If your application has `base_uri` configured, that will be taken into account
as well. For example: the application for a CMS can be distinguished by setting
its `base_uri` to `/admin`.

### Routing to the correct module

Requests that do not have a file extension and thus do not match a `media` file,
will be routed to a module and a matching method. The module is determined based
on the request URI, excluding all $_GET parameters. The module is a class that
should be derived from `\Skeleton\Core\Web\Module`.

This can be best explained with some examples:

| requested uri    | classname                  | filename             |
| ---------------- | -------------------------- | -------------------- |
| /user/management | Web_Module_User_Management | /user/management.php |
| /                | Web_Module_Index           | /index.php           |
| /user            | Web_Module_User            | /user.php            |
| /user            | Web_Module_User_Index      | /user/index.php      |

As you can see in the last two examples, the `index` modules are a bit special,
in that they can be used instead of the underlying one if they sit in a
subfolder.

### Routing to the correct method

A module can contain multiple methods that can handle the request. Each of
those requests have a method-name starting with 'display'. The method is defined
based on the $_GET['action'] variable.

Some examples:

| requested uri    | classname           | method               |
| -------------    | ---------           | --------             |
| /user            | Web_Module_User     | display()            |
| /user?action=test| Web_Module_User     | display_test()       |

### Handling of media files

If the requested url contains an extension which matches a known media type, the
requested file will be served from the `media/` directory of the application.

If the requested media file could not be found, `skeleton-core` will search for
a matching file in the folder specified by `Config::$asset_dir` (if any).

### Events

Events can be created to perform a task at specific key points during the
application's execution.

Events are defined in `Event` context classes. These classes are optional, but
when they are used, they should be located in the `event` directory. The filename
should be in the form of `Context_name.php`, for example `Application.php`.

The class should extend from `Skeleton\Core\Event` and the classname should be
within the namespace `\App\Your_application\Event\Context`, where
`Your_application` is the name of your application, and `Context` is one of the
available contexts:

- Application
- Media
- Module

Example of a `Module` event class for an application named `admin`:

    <?php
    /**
     * Module events for the "admin" application
     */

    namespace App\Admin\Event;

    class Module extends \Skeleton\Core\Event {

        /**
         * Access denied
         *
         * @access public
         */
        public function access_denied() {
            \Skeleton\Core\Web\Session::redirect('/reset');
        }

    }

The different contexts and their events are described below.

#### Application context

##### bootstrap

The bootstrap method is called before loading the application module.

    public function bootstrap(\Skeleton\Core\Web\Module $module) { }

##### teardown

The teardown method is called after the application's run is over.

    public function teardown(\Skeleton\Core\Web\Module $module) { }

#### detect

The detect method is called on every request to determine if the application
should handle the request, or if it should be skipped based on, for example, the
requested hostname and the request's URI.

This event should return `true` in order to proceed with this application.

    public function detect($hostname, $request_uri): bool { }

#### Module context

##### access_denied

The `access_denied` method is called whenever a module is requested which  can
not be accessed by the user. The optional `secure()` method in the module
indicates whether the user is granted access or not.

    public function access_denied(\Skeleton\Core\Web\Module $module) { }

##### not_found

The `not_found` method is called whenever a module is requested which does not
exist.

    public function not_found() { }

#### Media context

##### not_found

The `not_found` method is called whenever a media file is requested which could
not be found.

    public function not_found() { }

#### Error context

The error event context is not actually part of `skeleton-core`, but rather of
`skeleton-error`.

##### exception

The `exception` method is called whenever an exception has not been caught.

    public function exception() { }
