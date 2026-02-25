<?php
interface exchange
{
    public function getMethod();
    public function getData();
}

interface exchange_parser
{
    public function getParser();
}

interface exchange_preparer
{
    public function prepareResponse($response, &$error);
}
