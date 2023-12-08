<?php
/*
 * PepipostLib
 *
 * This file was automatically generated by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace PepipostLib\Controllers;

use PepipostLib\APIException;
use PepipostLib\APIHelper;
use PepipostLib\Configuration;
use PepipostLib\Models;
use PepipostLib\Exceptions;
use PepipostLib\Utils\DateTimeHelper;
use PepipostLib\Http\HttpRequest;
use PepipostLib\Http\HttpResponse;
use PepipostLib\Http\HttpMethod;
use PepipostLib\Http\HttpContext;
use Unirest\Request;

/**
 * @todo Add a general description for this controller.
 */
class EventsController extends BaseController
{
    /**
     * @var EventsController The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     * @return EventsController The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }

    /**
     * Lets you to retrieve the email transaction logs.
     *
     * @param DateTime $startdate   The starting date of the statistics to retrieve. Must follow format YYYY-MM-DD.
     * @param string   $events      (optional) Filter based on different email events. If not passed, all events will
     *                              be fetched. Multiple comma separated events are allowed
     * @param string   $sort        (optional) Sort based on email sent time
     * @param DateTime $enddate     (optional) The end date of the statistics to retrieve. Defaults to today. Must
     *                              follow format YYYY-MM-DD
     * @param integer  $offset      (optional) The point in the list to begin retrieving results.
     * @param integer  $limit       (optional) The number of results to return.
     * @param string   $subject     (optional) Filter logs based on subject
     * @param string   $xapiheader  (optional) Filter logs based on recipient's email
     * @param string   $fromaddress (optional) Filter logs based on fromaddress
     * @param string   $email       (optional) Filter logs based on recipient's email
     * @return object response from the API call
     * @throws APIException Thrown if API call fails
     */
    public function getEventsGET(
        $startdate,
        $events = null,
        $sort = null,
        $enddate = null,
        $offset = 0,
        $limit = 10,
        $subject = null,
        $xapiheader = null,
        $fromaddress = null,
        $email = null
    ) {

        //prepare query string for API call
        $_queryBuilder = '/events';

        //process optional query parameters
        APIHelper::appendUrlWithQueryParameters($_queryBuilder, array (
            'startdate'   => DateTimeHelper::toSimpleDate($startdate),
            'events'      => $events,
            'sort'        => $sort,
            'enddate'     => DateTimeHelper::toSimpleDate($enddate),
            'offset'      => (null != $offset) ? $offset : 0,
            'limit'       => (null != $limit) ? $limit : 10,
            'subject'     => $subject,
            'xapiheader'  => $xapiheader,
            'fromaddress' => $fromaddress,
            'email'       => $email,
        ));

        //validate and preprocess url
        $_queryUrl = APIHelper::cleanUrl(Configuration::$BASEURI . $_queryBuilder);

        //prepare headers
        $_headers = array (
            'user-agent'    => BaseController::USER_AGENT,
            'api_key' => Configuration::$apiKey
        );

        //call on-before Http callback
        $_httpRequest = new HttpRequest(HttpMethod::GET, $_headers, $_queryUrl);
        if ($this->getHttpCallBack() != null) {
            $this->getHttpCallBack()->callOnBeforeRequest($_httpRequest);
        }

        //and invoke the API call request to fetch the response
        $response = Request::get($_queryUrl, $_headers);

        $_httpResponse = new HttpResponse($response->code, $response->headers, $response->raw_body);
        $_httpContext = new HttpContext($_httpRequest, $_httpResponse);

        //call on-after Http callback
        if ($this->getHttpCallBack() != null) {
            $this->getHttpCallBack()->callOnAfterRequest($_httpContext);
        }

        //Error handling using HTTP status codes
        if ($response->code == 400) {
            throw new APIException('API Response', $_httpContext);
        }

        if ($response->code == 401) {
            throw new APIException('API Response', $_httpContext);
        }

        if ($response->code == 403) {
            throw new APIException('API Response', $_httpContext);
        }

        if ($response->code == 405) {
            throw new APIException('Invalid input', $_httpContext);
        }

        //handle errors defined at the API level
        $this->validateResponse($_httpResponse, $_httpContext);

        return $response->body;
    }
}