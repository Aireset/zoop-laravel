<?php

    namespace Aireset\Zoop;

    use Illuminate\Support\Facades\Facade;

    class ZoopCardFacade extends Facade
    {
        protected static function getFacadeAccessor()
        {
            return 'zoop_card';
        }
    }
