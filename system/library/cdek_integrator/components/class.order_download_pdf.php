<?php
class order_download_pdf extends cdek_integrator implements exchange, exchange_parser
{

    protected $method = 'v2/print/orders/';

    public function setMethod($number)
    {
        $this->method = 'v2/print/orders/' . $number . '.pdf';
    }

    public function getData()
    {
        return;
    }

    public function getParser()
    {
        return new parser_original();
    }
}
