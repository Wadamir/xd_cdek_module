<?php

class cdek_logger
{
    private $handle;

    /**
     * Constructor
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->handle = fopen(DIR_LOGS . $filename, 'a');
    }

    /**
     * Write message to log file
     *
     * @param string $message
     */
    public function write($message)
    {
        if (!$this->handle) {
            return;
        }

        fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
    }

    /**
     * Close log file handle
     */
    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
