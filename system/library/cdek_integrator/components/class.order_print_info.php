<?php
class order_print_info extends cdek_integrator implements exchange
{

    protected $method = 'v2/print/orders/';

    public function getData()
    {
        return;
    }

    public function setMethod($method_pdf)
    {
        $this->method = $this->method . $method_pdf;
    }
}
