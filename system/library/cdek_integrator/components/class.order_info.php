<?php
class order_info extends cdek_integrator implements exchange
{
    protected $method = 'v2/orders/';

    public function getData()
    {
        return;
    }

    public function setMethod($method_info)
    {
        $this->method = $this->method . $method_info;
    }
}
