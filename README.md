# Google Calendar Bundle

This bundle use Google API for list, create, or update events in Google Calendar.

Please feel free to contribute, to fork, to send merge request and to create ticket.

## Requirement
### Create a API account

https://console.developers.google.com
Create an oauth ID. Do not forget the redirect Uri.
Click on "Download JSON" to get your client_secret.json


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

```yml
# app/config/parameters.yml

fungio_google_calendar:
    google_calendar:
        application_name: "Google Calendar"
        credentials_path: "%kernel.root_dir%/.credentials/calendar.json"
        client_secret_path: "%kernel.root_dir%/Resources/GoogleCalendarBundle/client_secret.json"
```


## Example

``` php
<?php
// in a controller
$request = $this->getMasterRequest();

$googleCalendar = $this->get('fungio.google_calendar');
$googleCalendar->setRedirectUri($redirectUri);

if ($request->query->has('code') && $request->get('code')) {
    $client = $googleCalendar->getClient($request->get('code'));
} else {
    $client = $googleCalendar->getClient();
}

if (is_string($client)) {
    return new RedirectResponse($client);
}

$events = $googleCalendar->getEventsForDate('primary', new \DateTime('now');
```