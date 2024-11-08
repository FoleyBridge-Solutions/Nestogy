<?php

class SyncNinjaOneTask
{
    public function execute()
    {
        // Get all the clients from NinjaOne
        $ninjaOneApi = new NinjaOneApi();
        $clients = $ninjaOneApi->getClients();
    }
}