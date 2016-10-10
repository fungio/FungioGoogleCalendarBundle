# Google Calendar Bundle

This bundle use Google API for list, create, or update events in Google Calendar.

Please feel free to contribute, to fork, to send merge request and to create ticket.

## Requirement
### Create a API account

https://console.developers.google.com
Choose Server to Server type

## Installation
### Step 1: Install GoogleCalendarBundle

Run

```bash
composer require fungio/google-calendar-bundle:dev-master
```

### Step 2: Enable the bundle

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Fungio\GoogleCalendarBundle\FungioGoogleCalendarBundle()
    );
}
```

### Step 3: Configuration

Copy your client_secret.json file for example in app/Resources/GoogleCalendarBundle/client_secret.json

// app/config/parameters.yml

```yml
    fungio_google_calendar:
        google_calendar:
            application_name: "Google Calendar"
            credentials_path: "%kernel.root_dir%/.credentials/calendar.json"
            client_secret_path: "%kernel.root_dir%/Resources/GoogleCalendarBundle/client_secret.json"

```
