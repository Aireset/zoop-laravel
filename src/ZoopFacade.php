<?php

    namespace Aireset\Zoop;

    use Illuminate\Support\Facades\Facade;

    class ZoopFacade extends Facade
    {
        protected static function getFacadeAccessor()
        {
            return 'zoop';
        }
    }
