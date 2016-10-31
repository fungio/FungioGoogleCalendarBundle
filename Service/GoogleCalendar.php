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
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * @var bool
     */
    protected $fromFile = true;

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
     * @param $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        if ($accessToken != "") {
            $this->accessToken = $accessToken;
        }
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        if ($refreshToken != "") {
            $this->refreshToken = $refreshToken;
        }
    }

    /**
     * @param $inputStr
     * @return string
     */
    public static function base64UrlEncode($inputStr)
    {
        return strtr(base64_encode($inputStr), '+/=', '-_,');
    }

    /**
     * @param $inputStr
     * @return string
     */
    public static function base64UrlDecode($inputStr)
    {
        return base64_decode(strtr($inputStr, '-_,', '+/='));
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param null $authCode
     * @param bool|true $fromFile
     * @return \Google_Client|string
     */
    public function getClient($authCode = null, $fromFile = true)
    {
        $this->fromFile = $fromFile;

        $client = new \Google_Client();
        $client->setApplicationName($this->applicationName);
        $client->setScopes($this->scopes);
        $client->setAuthConfig($this->clientSecretPath);
        $client->setAccessType('offline');
        $client->setState($this->base64UrlEncode(json_encode($this->parameters)));

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->credentialsPath;
        if ($fromFile) {
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
        } else {
            if ($this->accessToken != null) {
                $accessToken = json_decode($this->accessToken, true);
            } else {
                // Request authorization from the user.
                if ($this->redirectUri) {
                    $client->setRedirectUri($this->redirectUri);
                }

                if ($authCode != null) {
                    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                    $this->accessToken = json_encode($accessToken);
                } else {
                    return $client->createAuthUrl();
                }
            }
        }
        $client->setAccessToken($accessToken);

        if ($client->getRefreshToken()) {
            $this->refreshToken = $client->getRefreshToken();
        }

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            if ($this->refreshToken) {
                $refreshToken = $this->refreshToken;
            } else {
                $refreshToken = $client->getRefreshToken();
            }

            if ($refreshToken) {
                $res = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                if (!isset($res['access_token'])) {
                    return $client->createAuthUrl();
                }
                if ($fromFile) {
                    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
                } else {
                    $this->accessToken = json_encode($client->getAccessToken());
                }
            } else {
                if ($fromFile) {
                    unlink($credentialsPath);
                } else {
                    $this->accessToken = null;
                }
                return $client->createAuthUrl();
            }
        }
        return $client;
    }

    /**
     * Add an Event to the specified calendar
     *
     * @param $calendarId
     * @param $eventStart
     * @param $eventEnd
     * @param $eventSummary
     * @param $eventDescription
     * @param $eventAttendee
     * @param string $location
     * @param array $optionalParams
     * @return \Google_Service_Calendar_Event
     */
    public function addEvent(
        $calendarId,
        $eventStart,
        $eventEnd,
        $eventSummary,
        $eventDescription,
        $eventAttendee = "",
        $location = "",
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
        if ($eventAttendee != "") {
            $attendee = new \Google_Service_Calendar_EventAttendee();
            $attendee->setEmail($eventAttendee);
            $event->attendees = [$attendee];
        }
        if ($location != "") {
            $event->setLocation($location);
        }
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
        $client = $this->getClient(null, $this->fromFile);
        if (!is_string($client)) {
            return new \Google_Service_Calendar($client);
        }
        return null;
    }
}