<?php

    namespace Aireset\Zoop;

    use GuzzleHttp\Exception\ClientException;
    use GuzzleHttp\Exception\ServerException;

    class ZoopClient extends ZoopConfig
    {
        /**
         * Envia a transação JSON.
         *
         * @param array $parameters
         * @param string $url Padrão $this->url['transactions']
         * @param string $method
         * @param array $headers
         *
         * @return \SimpleXMLElement
         * @throws \Aireset\Zoop\ZoopException
         *
         */
        public function sendJsonTransaction(array $parameters, $url, $method = 'POST', array $headers = null)
        {
            if (empty($headers) || count($headers) <= 0) {
                $options = [
                    'headers' => [
                        "authorization" => "Basic " . $this->getToken(),
                        'content-type' => 'application/json',
                    ]
                ];
            }

            $parameters = $this->array_filter_recursive($parameters);

            array_walk_recursive($parameters, function (&$value, $key) {
                $value = utf8_encode($value);
            });

            // $parameters = json_encode($parameters);
            //
            if ($method == 'GET') {
                $options['query'] = $parameters;
                // } elseif ($method == 'POST') {
                // $options['form_params'] = $parameters;
            } else {
                $options['json'] = $parameters;
            }

            $guzzleClient = new \GuzzleHttp\Client();

            try {
                $result = $guzzleClient->request($method, $url, $options);
                return $this->formatResultJson($result);
            } catch (ClientException $e) {
                $result = $e;
                // $result = $e->getResponse()->getBody()->getContents();
                // $this->log->error('Erro na transação Zoop', ['Retorno:' => $e->getResponse()->getBody()->getContents()]);
                // dd($result->getResponse()->getBody()->getContents());
                $this->error($result);
                // throw new ZoopException($result, $e->getCode());
            } catch (ServerException $e) {
                $result = $e;
                // $result = $e->getResponse()->getBody()->getContents();
                // $this->log->error('Erro na transação Zoop', ['Retorno:' => $e->getResponse()->getBody()->getContents()]);
                // dd($result->getResponse()->getBody()->getContents());
                $this->error($result);
                // throw new ZoopException($result, $e->getCode());
            }

            return $result;
        }

        /**
         * Aplica um array_filter recursivamente em um array.
         *
         * @param array $input
         *
         * @return array
         */
        public function array_filter_recursive(array $input)
        {
            foreach ($input as &$value) {
                if (is_array($value)) {
                    $value = $this->array_filter_recursive($value);
                }
            }

            return array_filter($input);
        }

        public function formatResultJson($result)
        {

            $result = json_decode($result->getBody()->getContents());

            if (isset($result->error) && $result->error === true) {
                $errors = $result->errors;

                $message = reset($errors);
                $code = key($errors);

                $this->log->error($message, ['Retorno:' => json_encode($result)]);

                throw new ZoopException($message, (int)$code);
            }

            return $result;
        }

        /**
         * Formata o resultado e trata erros.
         *
         * @param array $result
         *
         * @return mixed
         * @throws \Aireset\Zoop\ZoopException
         *
         */
        public function error($result)
        {
            switch ($result->getCode()) {
                case 400:
                    $message = __("A requisição foi invalida ou não atingiu o servidor. Muitas vezes, falta um parâmetro obrigatório.");
                    break;
                case 401:
                    $message = __("As credenciais de autenticação estavam faltando ou foram incorretas.");
                    break;
                case 402:
                    $message = __("Os parâmetros foram válidos mas a requisição falhou.");
                    break;
                case 403:
                    $message = __("A requisição foi ok, mas foi recusado ou o acesso não foi permitido. Uma mensagem de erro que acompanha a mensagem explica o porquê.");
                    break;
                case 404:
                    $message = __("A URI solicitada é inválida ou o recurso solicitado, como por exemplo, um vendedor não existe ou foi excluído.");
                    break;
                case 500:
                    $message = __("Algo está quebrado. Por favor, assegure-se de que a equipe Zoop esteja investigando.");
                    break;
                case 502:
                    $message = __("A Zoop caiu ou está sendo atualizada.");
                    break;
                default:
                    $message = null;
            }

            if (!empty($message)) {
                $this->log->error($message);
                //     $this->log->error($result->getResponse()->getBody()->getContents());
                //     throw new ZoopException($message, $result->getCode());
            }

            $data = json_decode($result->getResponse()->getBody()->getContents());

            if (isset($data->error)) {
                $error = $data->error;
                $message = $error->message;
                $this->log->error($message, ['Retorno:' => json_encode($data)]);
                throw new ZoopException($message, $error->status_code);
            }

            return $result;
        }

        /**
         * Inicia a Session do Zoop.
         *
         * @return string
         * @throws ZoopException
         *
         */
        public function startSessionApp()
        {
            return (string)$this->sendTransaction([
                'appId' => $this->appId,
                'appKey' => $this->appKey,
                'email' => $this->email,
                'token' => $this->token,
            ], $this->url['session'])->id;
        }

        /**
         * Envia a transação HTML.
         *
         * @param array $parameters
         * @param string $url Padrão $this->url['transactions']
         * @param bool $post
         * @param array $headers
         *
         * @return \SimpleXMLElement
         * @throws \Aireset\Zoop\ZoopException
         *
         */
        public function sendTransaction(array $parameters, $url = null, $post = true, array $headers = ['Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1'])
        {
            if ($url === null) {
                $url = $this->url['transactions'];
            }

            $parameters = $this->array_filter_recursive($parameters);

            $data = '';
            foreach ($parameters as $key => $value) {
                $data .= $key . '=' . $value . '&';
            }
            $parameters = rtrim($data, '&');

            $method = 'POST';

            if (!$post) {
                $url .= '?' . $parameters;
                $parameters = null;
                $method = 'GET';
            }

            $result = $this->executeGuzzle($parameters, $url, $headers, $method);

            return $this->formatResult($result);
        }

        /**
         * Executa o Curl.
         *
         * @param array|string $parameters
         * @param string $url
         * @param array $headers
         * @param $method
         *
         * @return \SimpleXMLElement
         * @throws ZoopException
         *
         */
        public function executeGuzzle($parameters, $url, array $headers, $method)
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_ENCODING, "");
            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 0);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, true);
            } elseif ($method == 'PUT') {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            }

            if ($parameters !== null) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
            }

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, !$this->sandbox);
            $result = curl_exec($curl);

            $getInfo = curl_getinfo($curl);
            if (isset($getInfo['http_code']) && $getInfo['http_code'] == '503') {
                $this->log->error('Serviço em manutenção.', ['Retorno:' => $result]);

                throw new ZoopException('Serviço em manutenção.', 1000);
            }
            if ($result === false) {
                $this->log->error('Erro ao enviar a transação', ['Retorno:' => $result]);

                throw new ZoopException(curl_error($curl), curl_errno($curl));
            }

            curl_close($curl);

            return $result;
        }

        /**
         * Formata o resultado e trata erros.
         *
         * @param array $result
         *
         * @return \SimpleXMLElement
         * @throws \Aireset\Zoop\ZoopException
         *
         */
        public function formatResult($result)
        {
            if ($result === 'Unauthorized' || $result === 'Forbidden') {
                $this->log->error('Erro ao enviar a transação', ['Retorno:' => $result]);

                throw new ZoopException($result . ': Não foi possível estabelecer uma conexão com o Zoop.', 1001);
            }
            if ($result === 'Not Found') {
                $this->log->error('Notificação/Transação não encontrada', ['Retorno:' => $result]);

                throw new ZoopException($result . ': Não foi possível encontrar a notificação/transação no Zoop.', 1002);
            }

            $result = simplexml_load_string($result);

            if (isset($result->error) && isset($result->error->message)) {
                $this->log->error($result->error->message, ['Retorno:' => $result]);

                throw new ZoopException($result->error->message, (int)$result->error->code);
            }

            return $result;
        }

        /**
         * Inicia a Session do Zoop.
         *
         * @return string
         * @throws ZoopException
         *
         */
        public function startSession()
        {
            return (string)$this->sendTransaction([
                'email' => $this->email,
                'token' => $this->token,
            ], $this->url['session'])->id;
        }

        /**
         * Retorna a transação da notificação.
         *
         * @param string $notificationCode
         * @param string $notificationType
         *
         * @return \SimpleXMLElement
         * @throws ZoopException
         *
         */
        public function notification($notificationCode, $notificationType = 'transaction')
        {
            if ($notificationType == 'transaction') {
                return $this->sendTransaction([
                    'email' => $this->email,
                    'token' => $this->token,
                ], $this->url['notifications'] . $notificationCode, false);
            } elseif ($notificationType == 'preApproval') {
                return $this->sendTransaction([
                    'email' => $this->email,
                    'token' => $this->token,
                ], $this->url['preApprovalNotifications'] . $notificationCode, false);
            } elseif ($notificationType == 'applicationAuthorization') {
                return $this->sendTransaction([
                    'appId' => $this->appId,
                    'appKey' => $this->appKey,
                ], $this->url['authorizationsNotification'] . $notificationCode, false);
            }
        }

        /**
         * Valida os dados.
         *
         * @param array $data
         * @param array $rules
         *
         * @throws \Aireset\Zoop\ZoopException
         */
        public function validate($data, $rules)
        {
            $data = array_filter($data);

            $validator = $this->validator->make($data, $rules);

            if ($validator->fails()) {
                throw new ZoopException($validator->messages()->first(), 1003);
            }
        }

        /**
         * Limpa um valor deixando apenas números.
         *
         * @param mixed $value
         * @param string $key
         *
         * @return null|mixed
         */
        public function sanitizeNumber($value, $key = null)
        {
            return $this->sanitize($value, $key, '/\D/', '');
        }

        /**
         * Limpa um valor removendo espaços duplos.
         *
         * @param mixed $value
         * @param string $key
         * @param string $regex
         * @param string $replace
         *
         * @return null|mixed
         */
        public function sanitize($value, $key = null, $regex = '/\s+/', $replace = ' ')
        {
            $value = $this->checkValue($value, $key);

            return $value == null ? null : utf8_decode(trim(preg_replace($regex, $replace, $value)));
        }

        /**
         * Verifica a existência de um valor.
         *
         * @param mixed $value
         * @param string $key
         *
         * @return null|mixed
         */
        public function checkValue($value, $key = null)
        {
            if ($value != null) {
                if ($key !== null) {
                    return isset($value[$key]) ? $value[$key] : null;
                }

                return $value;
            }
        }

        /**
         * Limpa um valor deixando no formato de moeda.
         *
         * @param mixed $value
         * @param string $key
         *
         * @return null|number
         */
        public function sanitizeMoney($value, $key = null)
        {
            $value = $this->checkValue($value, $key);

            return $value == null ? $value : number_format($value, 2, '.', '');
        }

        /**
         * Verifica a existência de um valor, e utiliza outro caso necessário.
         *
         * @param mixed $value
         * @param mixed $fValue
         * @param string $fKey
         *
         * @return null|mixed
         */
        public function fallbackValue($value, $fValue, $fKey)
        {
            return $value != null ? $value : $this->checkValue($fValue, $fKey);
        }
    }
