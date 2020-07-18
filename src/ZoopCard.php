<?php

    namespace Aireset\Zoop;

    /**
     * Class ZoopCard
     * @package Aireset\Zoop
     */
    class ZoopCard extends Zoop
    {
        /**
         * @var
         */
        private $installments;

        /**
         * @var
         */
        private $total;

        /**
         * @var array
         */
        private $card;

        /**
         * @var string
         */
        private $cardNumber;

        /**
         * @var string
         */
        private $cardMonth;

        /**
         * @var string
         */
        private $cardYear;

        /**
         * @var string
         */
        private $cardName;

        /**
         * @var string
         */
        private $cardCsc;

        /**
         * @var
         */
        private $cart;

        /**
         * @var int
         */
        private $cartReferenceId;

        /**
         * @var string
         */
        private $cartDescription;

        /**
         * @return mixed
         */
        public function getCart()
        {
            return $this->cart;
        }

        /**
         * Define um carrinho.
         *
         * @param array $cart
         *
         * @return $this
         */
        public function setCart(array $cart)
        {
            $this->cart = (object)$cart;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getCard()
        {
            return $this->card;
        }

        /**
         * Define o a variavel de cartão.
         *
         * @param array $card
         *
         * @return $this
         */
        public function setCard($card)
        {
            $this->card = (object)$card;

            $this->validateCard($this->card);

            $this->setCardNumber(preg_replace('/\D/', '', $this->card->number));
            $this->setCardName($this->card->name);
            $this->setCardCsc($this->card->csc);
            // $this->setCardMonth(explode("/", $this->card->exp)[0]);
            // $this->setCardYear(explode("/", $this->card->exp)[1]);

            return $this;
        }

        /**
         * Valida os dados contidos na array de informações do portador do cartão de crédito.
         *
         * @param array $creditCardHolder
         */
        private function validateCard($CardHolder)
        {
            $expiration = explode('/', $CardHolder->exp);
            unset($CardHolder->exp);
            $CardHolder->month = $expiration[0];
            $CardHolder->year = $expiration[1];

            $this->setCardMonth($CardHolder->month);
            $this->setCardYear($CardHolder->year);

            $rules = [
                'name' => 'required|max:50',
                'month' => 'required|digits:2|between:1,12', // month expiration
                'year' => 'required|integer|digits:2|min:' . date('y'), // year expiration
                'csc' => 'required|min:3',
                'number' => 'required'
            ];

            $this->validate((array)$CardHolder, $rules);
        }

        /**
         *
         */
        public function transaction()
        {
            $cardToken = $this->cardToken();

            $data = [
                "source" => [
                    "card" => [
                        "holder_name" => $this->getCardName(),
                        "expiration_month" => $this->getCardMonth(),
                        "expiration_year" => "20" . $this->getCardYear(),
                        "security_code" => $this->getCardCsc()
                    ],
                    "type" => "card"
                ],
                "installment_plan" => [
                    "number_installments" => $this->getInstallments()
                ],
                "token" => (string)$cardToken,
                "on_behalf_of" => $this->getSellerId(),
                // "description" => "#{$cart->id} ".implode('- Produto: ', $productsNames),
                "reference_id" => $this->getCartReferenceId(),
                "description" => $this->getCartDescription(),
                "amount" => (int)round($this->getTotal() * 100)
            ];

            // Criar Validação

            $url = $this->getUrl()['transactions'];

            // try {
            //     $response = $this->sendJsonTransaction($data, $url, 'POST');
            // } catch (ZoopException $e) {
            //     dd($e);
            // //     \Log::error($e);
            // //     \Log::error('Zoop Estorno Error');
            // //     $response = $e;
            // }

            return $this->sendJsonTransaction($data, $url, 'POST');
        }

        /**
         * @return bool|mixed|string
         */
        public function cardToken()
        {
            $data = [
                "holder_name" => $this->getCardName(),
                "expiration_month" => $this->getCardMonth(),
                "expiration_year" => '20' . $this->getCardYear(),
                "card_number" => $this->getCardNumber(),
                "security_code" => $this->getCardCsc()
            ];

            $url = $this->getUrl()['cardsTokens'];

            try {
                $response = $this->sendJsonTransaction($data, $url, 'POST');
            } catch (ZoopException $e) {
                \Log::error($e);
                \Log::error('Zoop Estorno Error');
                $response = null;
            }

            return $response->id;
        }

        /**
         * @return string
         */
        public function getCardName(): string
        {
            return $this->cardName;
        }

        /**
         * @param string $cardName
         * @return ZoopCard
         */
        public function setCardName(string $cardName): ZoopCard
        {
            $this->cardName = $cardName;
            return $this;
        }

        /**
         * @return string
         */
        public function getCardMonth(): string
        {
            return $this->cardMonth;
        }

        /**
         * @param string $cardMonth
         * @return ZoopCard
         */
        public function setCardMonth(string $cardMonth): ZoopCard
        {
            $this->cardMonth = $cardMonth;
            return $this;
        }

        /**
         * @return string
         */
        public function getCardYear(): string
        {
            return $this->cardYear;
        }

        /**
         * @param string $cardYear
         * @return ZoopCard
         */
        public function setCardYear(string $cardYear): ZoopCard
        {
            $this->cardYear = $cardYear;
            return $this;
        }

        /**
         * @return string
         */
        public function getCardNumber(): string
        {
            return $this->cardNumber;
        }

        /**
         * @param string $cardNumber
         * @return ZoopCard
         */
        public function setCardNumber(string $cardNumber): ZoopCard
        {
            $this->cardNumber = $cardNumber;
            return $this;
        }

        /**
         * @return string
         */
        public function getCardCsc(): string
        {
            return $this->cardCsc;
        }

        /**
         * @param string $cardCsc
         * @return ZoopCard
         */
        public function setCardCsc(string $cardCsc): ZoopCard
        {
            $this->cardCsc = $cardCsc;
            return $this;
        }

        /**
         * @return mixed
         */
        public function getInstallments()
        {
            return $this->installments;
        }

        /**
         * Define o número de parcelas.
         *
         * @param string $installments
         *
         * @return $this
         */
        public function setInstallments($installments)
        {
            $this->installments = $installments;

            return $this;
        }

        /**
         * @return int
         */
        public function getCartReferenceId(): int
        {
            return $this->cartReferenceId;
        }

        /**
         * @param int $cartReferenceId
         * @return ZoopCard
         */
        public function setCartReferenceId(int $cartReferenceId): ZoopCard
        {
            $this->cartReferenceId = $cartReferenceId;
            return $this;
        }

        /**
         * @return string
         */
        public function getCartDescription(): string
        {
            return $this->cartDescription;
        }

        /**
         * @param string $cartDescription
         * @return ZoopCard
         */
        public function setCartDescription(string $cartDescription): ZoopCard
        {
            $this->cartDescription = $cartDescription;
            return $this;
        }

        /**
         * @return mixed
         */
        public function getTotal()
        {
            return $this->total;
        }

        /**
         * Define o valor total da compra.
         *
         * @param string $total
         *
         * @return $this
         */
        public function setTotal($total)
        {
            if ($this->getInstallments() == '1') {
                $total = $total / 100;
            }

            $this->total = $total;

            return $this;
        }
    }
