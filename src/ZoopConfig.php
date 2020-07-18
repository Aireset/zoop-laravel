<?php

    namespace Aireset\Zoop;

    use Illuminate\Validation\Factory as Validator;
    use Psr\Log\LoggerInterface as Log;

    class ZoopConfig
    {
        /**
         * Log instance.
         *
         * @var object
         */
        protected $log;

        /**
         * Validator instance.
         *
         * @var object
         */
        protected $validator;

        /**
         * Ambiente.
         *
         * @var string
         */
        protected $enviroment;

        /**
         * Client Secret ID.
         *
         * @var string
         */
        protected $clientSecretId;

        /**
         * Marketplace ID
         *
         * @var string
         */
        protected $marketplaceId;

        /**
         * Seller ID
         *
         * @var string
         */
        protected $sellerId;

        /**
         * Token Base 64
         *
         * @var string
         */
        protected $token;

        /**
         * Armazena as url's para conexão com o PagSeguro.
         *
         * @var array
         */
        protected $url = [];

        /**
         * @param $log
         * @param $validator
         */
        public function __construct(Log $log, Validator $validator)
        {
            $this->log = $log->channel('zoop');
            $this->validator = $validator;
            $this->setEnvironment();
            $this->setUrl();
        }

        /**
         * Define o ambiente de trabalho.
         */
        private function setEnvironment()
        {
            $this->enviroment = env('ZOOP_ENV', 'development');

            if ($this->enviroment === 'production') {
                $this->setClientSecretId(env('ZOOP_CLIENT_SECRET'));
                $this->setMarketplaceId(env('ZOOP_MARKETPLACE_ID'));
                $this->setSellerId(env('ZOOP_SELLER_ID'));
            } else {
                $this->setClientSecretId(env('ZOOP_CLIENT_SECRET_DEV'));
                $this->setMarketplaceId(env('ZOOP_MARKETPLACE_ID_DEV'));
                $this->setSellerId(env('ZOOP_SELLER_ID_DEV'));
            }

            $validateRequest = [
                'client_secret' => $this->getClientSecretId(),
                'marketplace_id' => $this->getMarketplaceId(),
                'seller_id' => $this->getSellerId(),
            ];

            $rules = [
                'client_secret' => 'required',
                'marketplace_id' => 'required',
                'seller_id' => 'required',
            ];

            $this->validate($validateRequest, $rules);

            $this->setToken($this->getClientSecretId());
        }

        /**
         * @return string
         */
        public function getClientSecretId(): string
        {
            return $this->clientSecretId;
        }

        /**
         * @param string $clientSecretId
         */
        public function setClientSecretId(string $clientSecretId): void
        {
            $this->clientSecretId = $clientSecretId;
        }

        /**
         * @return string
         */
        public function getMarketplaceId(): string
        {
            return $this->marketplaceId;
        }

        /**
         * @param string $marketplaceId
         */
        public function setMarketplaceId(string $marketplaceId): void
        {
            $this->marketplaceId = $marketplaceId;
        }

        /**
         * @return string
         */
        public function getSellerId(): string
        {
            return $this->sellerId;
        }

        /**
         * @param string $sellerId
         */
        public function setSellerId(string $sellerId): void
        {
            $this->sellerId = $sellerId;
        }

        /**
         * Retorna o array de url's.
         */
        public function getUrl()
        {
            return $this->url;
        }

        /**
         * Define as Urls que serão utilizadas de acordo com o ambiente.
         */
        public function setUrl($array = null)
        {
            $url = [
                'estorno' => 'https://api.zoop.ws/v2/marketplaces/' . $this->marketplaceId . '/transactions/[$transaction_id]/void',
                'cardsTokens' => 'https://api.zoop.ws/v1/marketplaces/' . $this->marketplaceId . '/cards/tokens',
                'transactions' => 'https://api.zoop.ws/v2/marketplaces/' . $this->marketplaceId . '/transactions',
            ];

            if (is_array($array) && count($array) > 0) {
                $url = array_merge_recursive($url, $array);
            }

            $this->url = $url;
        }

        /**
         * @return string
         */
        public function getEnviroment(): string
        {
            return $this->enviroment;
        }

        /**
         * @param string $enviroment
         */
        public function setEnviroment(string $enviroment): void
        {
            $this->enviroment = $enviroment;
        }

        /**
         * @return string
         */
        public function getToken(): string
        {
            return $this->token;
        }

        /**
         * @param string $token
         * @return ZoopConfig
         */
        public function setToken(string $token): ZoopConfig
        {
            $this->token = base64_encode($token . ':');
            return $this;
        }

    }
