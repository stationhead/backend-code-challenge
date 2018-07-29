<?php

namespace App\Responses;

use App;
use App\Responses\STHResponseBuilder;
use \Exception;
use League\Fractal\Manager;
use League\Fractal\Pagination\Cursor;

use App\Models\Queue;

use App\Resources\STHCollection;
use App\Resources\STHItem;

class STHResponse {

    /**
     * StatusCode
     *
     */
    protected $statusCode = 200;

    /**
     * Headers array
     *
     */
    protected $headers = [];

    /**
     * body
     *
     */
    protected $body;

    /**
     * Queue.
     * Debounces Queue (rotation) changes so that only one push is made per request cycle.
     * Queue argument is intercepted in Middlewear and dispatched.
     */
    protected $queue;

    /**
    * Add a Header to the Response.
    *
    */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
    * Set the status code of the Response.
    *
    */
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
    * Set a generic success body/
    *
    */
    public function success()
    {
        $this->body = ['success'];
        return $this;
    }

    /**
    * Sets response code and body to match Exception.
    *
    */
    public function error(Exception $e)
    {
        $this->statusCode = $e->getStatusCode();
        $this->body = [
            'error' => [
                'status' => $this->statusCode,
                'code' => $e->getDomainCode(),
                'title' => $e->getMessage() ?? class_basename($e),
                'detail' => $e->getDetails(),
                'end_user_title' => $e->endUserTitle(),
                'end_user_message' => $e->endUserMessage()
            ]
        ];


        return $this;
    }

    public function notImplemented($message = "Not Implemented")
    {
        $this->statusCode = 501;
        $this->body = [$message];
        return $this;
    }

    public function noData()
    {
        $this->statusCode = 204;
        return $this;
    }

    /**
     * Set the data for the body of the response.
     *
     */
    public function data(STHResponseBuilder $data)
    {
        $this->body = $data->getResponse();
        return $this;
    }

    /**
     * Set the data for the body of the response directly.
     *
     */
    public function arrayData(array $body)
    {
        $this->body = $body;
        return $this;
    }

    public function addChangedQueue(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function getChangedQueue()
    {
        return $this->queue;
    }

     /**
      * Actually respond to the request.
      *
      */
     public function send()
     {
         return response()->json($this->body, $this->statusCode, $this->headers);
     }
}
