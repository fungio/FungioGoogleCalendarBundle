<?php

namespace Fungio\GoogleCalendarBundle\Service;

/**
 * Class GoogleCalendar
 * @package Fungio\GoogleCalendarBundle\Service
 *
 * @author Pierrick AUBIN <pierrick.aubin@siqual.fr>
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
        $this->scopes = implode(' ', [\Google_Service_Calendar::CALENDAR_READONLY]);
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
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
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
        $eventOptions = array(
            'timeMin' => $timeMin,
            'timeMax' => $timeMax
        );
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