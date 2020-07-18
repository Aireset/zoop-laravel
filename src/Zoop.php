<?php

    namespace Aireset\Zoop;

    class Zoop extends ZoopClient
    {

        public function teste()
        {
            dd('teste');
        }

        public function estorno($transaction_id, int $total, $transction_url = null)
        {
            $data =  [
                'on_behalf_of' => $this->getSellerId(),
                'amount' => $total
            ];

            if(empty($transction_url)){
                $url = str_replace('[$transaction_id]', $transaction_id, $this->getUrl()['estorno']);
            } else {
                $url = 'https://api.zoop.ws'.$transction_url.'/void';
            }

            try {
                $response = $this->sendJsonTransaction($data, $url, 'POST');
            } catch (ZoopException $e) {
                \Log::error($e);
                \Log::error('Zoop Estorno Error');
                $response = null;
            }

            return $response;
        }
    }
