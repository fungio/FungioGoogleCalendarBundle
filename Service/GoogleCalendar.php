<?php

namespace Fungio\GoogleCalendarBundle\Service;

/**
 * Class GoogleCalendar
 * @package Fungio\GoogleCalendarBundle\Service
 *
 * @author Pierrick AUBIN <fungio76@gmail.com>
 */
class GoogleCalendar
{
    /**
     * @var string
     */
    protected $applicationName;

    /**
     * @var string
     */
    protected $credentialsPath;

    /**
     * @var string
     */
    protected $clientSecretPath;

    /**
     * @var string
     */
    protected $scopes;

    /**
     * @var string
     */
    protected $redirectUri;

    /**
     * construct
     */
    public function __construct()
    {
        $this->scopes = implode(' ', [\Google_Service_Calendar::CALENDAR]);
    }

    /**
     * @param $applicationName
     */
    public function setApplicationName($applicationName)
    {
        $this->applicationName = $applicationName;
    }

    /**
     * @param $credentialsPath
     */
    public function setCredentialsPath($credentialsPath)
    {
        $this->credentialsPath = $credentialsPath;
    }

    /**
     * @param $clientSecretPath
     */
    public function setClientSecretPath($clientSecretPath)
    {
        $this->clientSecretPath = $clientSecretPath;
    }

    /**
     * @param $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * @param null $authCode
     * @return \Google_Client|string
     */
    public function getClient($authCode = null)
    {
        $client = new \Google_Client();
        $client->setApplicationName($this->applicationName);
        $client->setScopes($this->scopes);
        $client->setAuthConfig($this->clientSecretPath);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->credentialsPath;
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            if ($this->redirectUri) {
                $client->setRedirectUri($this->redirectUri);
            }

            if ($authCode != null) {
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

                if (!file_exists(dirname($credentialsPath))) {
                    mkdir(dirname($credentialsPath), 0700, true);
                }
                file_put_contents($credentialsPath, json_encode($accessToken));
            } else {
                return $client->createAuthUrl();
            }
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
            } else {
                unlink($credentialsPath);
                return $client->createAuthUrl();
            }
        }
        return $client;
    }

    /**
     * Add an Event to the specified calendar
     *
     * @param string $calendarId Calendar's ID in which you want to insert your event
     * @param \DateTime $eventStart Event's start date
     * @param \DateTime $eventEnd Event's end date
     * @param string $eventSummary Event's title
     * @param string $eventDescription Event's description where you should put all your informations
     * @param array $eventAttendee Event's attendees : to use the invitation system you should add the calendar owner to the attendees
     * @param array $optionalParams Optional params
     *
     * @return object Event
     */
    public function addEvent(
        $calendarId,
        $eventStart,
        $eventEnd,
        $eventSummary,
        $eventDescription,
        $eventAttendee,
        $optionalParams = []
    )
    {
        // Your new GoogleEvent object
        $event = new \Google_Service_Calendar_Event();
        // Set the title
        $event->setSummary($eventSummary);
        // Set and format the start date
        $formattedStart = $eventStart->format(\DateTime::RFC3339);
        $formattedEnd = $eventEnd->format(\DateTime::RFC3339);
        $start = new \Google_Service_Calendar_EventDateTime();
        $start->setDateTime($formattedStart);
        $event->setStart($start);
        $end = new \Google_Service_Calendar_EventDateTime();
        $end->setDateTime($formattedEnd);
        $event->setEnd($end);
        // Default status for newly created event
        $event->setStatus('tentative');
        // Set event's description
        $event->setDescription($eventDescription);
        // Attendees - permit to manage the event's status
        $attendee = new \Google_Service_Calendar_EventAttendee();
        $attendee->setEmail($eventAttendee);
        $event->attendees = [$attendee];
        // Event insert
        return $this->getCalendarService()->events->insert($calendarId, $event, $optionalParams);
    }

    /**
     * Retrieve modified events from a Google push notification
     *
     * @param string $calendarId
     * @param string $syncToken Synchronised Token to retrieve last changes
     *
     * @return object
     */
    public function getEvents($calendarId, $syncToken)
    {
        // Option array
        $optParams = [];
        return $this->getCalendarService()->events->listEvents($calendarId, $optParams);
    }

    /**
     * Init a full list of events
     *
     * @param string $calendarId
     *
     * @return object
     */
    public function initEventsList($calendarId)
    {
        $eventsList = $this->getCalendarService()->events->listEvents($calendarId);
        return $eventsList->getItems();
    }

    /**
     * Delete an event
     *
     * @param string $calendarId
     * @param string $eventId
     */
    public function deleteEvent($calendarId, $eventId)
    {
        $this->getCalendarService()->events->delete($calendarId, $eventId);
    }

    /**
     * Update an event
     *
     * @param string $calendarId
     * @param \Google_Service_Calendar_Event $event
     */
    public function updateEvent($calendarId, $event)
    {
        $this->getCalendarService()->events->update($calendarId, $event->getId(), $event);
    }

    /**
     * Get an event
     *
     * @param $calendarId
     * @param $eventId
     * @param array $optParams
     * @return \Google_Service_Calendar_Event
     */
    public function getEvent($calendarId, $eventId, $optParams = [])
    {
        return $this->getCalendarService()->events->get($calendarId, $eventId, $optParams);
    }

    /**
     * List shared and available calendars
     *
     * @return object
     */
    public function listCalendars()
    {
        return $this->getCalendarService()->calendarList->listCalendarList();
    }

    /**
     * Retrieve Google events on a date range
     *
     * @param string $calendarId
     * @param \DateTime $start Range start
     * @param \DateTime $end Range end
     *
     * @return object
     */
    public function getEventsOnRange($calendarId, \Datetime $start, \Datetime $end)
    {
        $service = $this->getCalendarService();

        $timeMin = $start->format(\DateTime::RFC3339);
        $timeMax = $end->format(\DateTime::RFC3339);

        // Params to send to Google
        $eventOptions = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $timeMin,
            'timeMax' => $timeMax
        ];
        $eventList = $service->events->listEvents($calendarId, $eventOptions);
        return $eventList;
    }

    /**
     * Retrieve Google events for a date
     *
     * @param $calendarId
     * @param \Datetime $date
     * @return \Google_Service_Calendar_Events
     */
    public function getEventsForDate($calendarId, \Datetime $date)
    {
        $service = $this->getCalendarService();

        $start = clone $date;
        $start->setTime(0, 0, 0);
        $end = clone $date;
        $end->setTime(23, 59, 29);
        $timeMin = $start->format(\DateTime::RFC3339);
        $timeMax = $end->format(\DateTime::RFC3339);

        // Params to send to Google
        $eventOptions = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $timeMin,
            'timeMax' => $timeMax
        ];
        $eventList = $service->events->listEvents($calendarId, $eventOptions);
        return $eventList;
    }

    /**
     * Retrieve Google events filtered by parameters
     *
     * @param string $calendarId
     * @param array $eventOptions
     *
     * @return object
     */
    public function getEventsByParams($calendarId, $eventOptions)
    {
        $service = $this->getCalendarService();
        foreach (['timeMin', 'timeMax', 'updatedMin'] as $opt) {
            if (isset($eventOptions[$opt])) $eventOptions[$opt] = $eventOptions[$opt]->format(\DateTime::RFC3339);
        }
        $eventList = $service->events->listEvents($calendarId, $eventOptions);
        return $eventList;
    }

    /**
     * @return \Google_Service_Calendar|null
     */
    public function getCalendarService()
    {
        $client = $this->getClient();
        if (!is_string($client)) {
            return new \Google_Service_Calendar($this->getClient());
        }
        return null;
    }
}