<?php

namespace Kstmostofa\LaravelEsl;

use Kstmostofa\LaravelEsl\EslConnectionErrors;

class EslConnection
{
    protected $host;
    protected $port;
    protected $password;
    protected $socket;

    /**
     * EslConnection constructor.
     * @param string $host
     * @param int $port
     * @param string $password
     */
    public function __construct($host, $port, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
    }

    /**
     * Set the connection details for a one-off command.
     *
     * @param string $host
     * @param int $port
     * @param string $password
     * @return $this
     */
    public function connection($host, $port, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;

        return $this;
    }

    /**
     * Establish the socket connection and authenticate.
     *
     * @return $this
     * @throws EslConnectionException
     */
    public function connect()
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$this->socket) {
            $errorDetails = EslConnectionErrors::getDescription($errno, $errstr);
            throw new EslConnectionException("Unable to connect to FreeSWITCH ESL. $errorDetails");
        }

        $this->authenticate();

        return $this;
    }

    /**
     * Authenticate with the ESL server.
     *
     * @throws EslConnectionException
     */
    protected function authenticate()
    {
        $response = $this->readResponse();
        if (strpos($response, 'Content-Type: auth/request') === false) {
            throw new EslConnectionException('Failed to receive auth request from FreeSWITCH.');
        }

        $this->sendCommand('auth ' . $this->password);
        $response = $this->readResponse();

        if (strpos($response, 'Content-Type: command/reply') === false || strpos($response, 'Reply-Text: +OK accepted') === false) {
            throw new EslConnectionException('FreeSWITCH ESL authentication failed.');
        }
    }

    /**
     * Send a command to the ESL socket.
     *
     * @param string $command
     */
    public function sendCommand($command)
    {
        fwrite($this->socket, $command . "\n\n");
    }

    /**
     * Read the response from the ESL socket, handling Content-Length.
     *
     * @return string
     */
    public function readResponse()
    {
        $headers = '';
        $body = '';
        $contentLength = 0;

        while (true) {
            $buffer = fgets($this->socket);
            if ($buffer === false) {
                break;
            }
            $headers .= $buffer;
            if (strpos($buffer, 'Content-Length:') !== false) {
                $contentLength = (int) trim(substr($buffer, 15));
            }
            if (trim($buffer) === '') {
                break; // End of headers
            }
        }

        if ($contentLength > 0) {
            $body = fread($this->socket, $contentLength);
        }

        return $headers . $body;
    }

    /**
     * Execute an API command and return the response.
     *
     * @param string $command
     * @return string
     * @throws EslConnectionException
     */
    public function execute($command)
    {
        if (!$this->socket) {
            $this->connect();
        }

        $this->sendCommand('api ' . $command);
        return $this->parseResponse($this->readResponse());
    }

    /**
     * A wrapper for the 'status' API command.
     *
     * @return string
     * @throws EslConnectionException
     */
    public function status()
    {
        return $this->execute('status');
    }

    /**
     * A wrapper for the 'show channels' API command.
     *
     * @return string
     * @throws EslConnectionException
     */
    public function showChannels()
    {
        return $this->execute('show channels');
    }

    /**
     * A wrapper for the 'show calls' API command.
     *
     * @return string
     * @throws EslConnectionException
     */
    public function showCalls()
    {
        return $this->execute('show calls');
    }

    /**
     * A wrapper for the 'sofia status' API command.
     *
     * @return string
     * @throws EslConnectionException
     */
    public function sofiaStatus()
    {
        return $this->execute('sofia status');
    }

    /**
     * A wrapper for the 'sofia status profile <profile>' API command.
     *
     * @param string $profile
     * @return string
     * @throws EslConnectionException
     */
    public function sofiaStatusProfile($profile)
    {
        return $this->execute("sofia status profile {$profile}");
    }

    /**
     * Parse the raw ESL response to extract the body.
     *
     * @param string $response
     * @return string
     */
    protected function parseResponse($response)
    {
        $parts = explode("\n\n", $response, 2);
        return isset($parts[1]) ? trim($parts[1]) : '';
    }

    /**
     * Disconnect from the ESL socket.
     */
    public function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Ensure disconnection when the object is destroyed.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}