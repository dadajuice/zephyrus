<?php namespace Zephyrus\Utilities;

use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\PayPalException;

class PayPal
{
    const AUTH_3TOKEN = '3TOKEN';
    const AUTH_FIRSTPARTY = 'FIRSTPARTY';
    const AUTH_THIRDPARTY = 'THIRDPARTY';
    const AUTH_PERMISSION = 'PERMISSION';
    const CALL_INIT_CHECKOUT = 'SetExpressCheckout';
    const CALL_DETAIL_CHECKOUT = 'GetExpressCheckoutDetails';
    const CALL_DO_TRANSACTION = 'DoExpressCheckoutPayment';

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $returnUrl;

    /**
     * @var double
     */
    private $shippingPrice = 0.00;

    /**
     * @var double
     */
    private $handlingPrice = 0.00;

    /**
     * @var string
     */
    private $clientEmail;

    /**
     * @var array
     */
    private $items = [];

    public function __construct()
    {
        $this->config = Configuration::getConfiguration("paypal");
    }

    public function call()
    {
        if (!isset($_GET['token'])) {

            // Si l'identification PayPal n'existe pas nous sommes donc dans la phase
            // d'initialisation de la connexion avec PayPal. La deuxième fois que le
            // client retournera sur cette page, un TOKEN lui sera attribué en GET de
            // PayPal.
            $this->initializeConnection();

        } else {

            // Le TOKEN est présent et par conséquent, une connexion a été établie avec
            // PayPal. Il est maintenant possible d'extraire les détails de la commande
            // depuis la réponse de PayPal.
            $this->orderReview();
        }
    }

    public function doTransaction()
    {
        $token = urlencode($_SESSION['PAYPAL']['TOKEN']);
        $payment_amount = urlencode ($_SESSION['PAYPAL']['TOTAL_AMT']);
        $payer_id = urlencode($_SESSION['PAYPAL']['PAYER_ID']);
        $server_name = urlencode($_SERVER['SERVER_NAME']);

        // Chaine NVP
        $nvpstr = '&TOKEN=' . $token .
            '&PAYERID=' . $payer_id .
            '&PAYMENTACTION=sale' .
            '&AMT=' . $payment_amount .
            '&CURRENCYCODE=CAD' .
            '&IPADDRESS=' . $server_name;

        // Effectuer un appel à l'API
        $response_array = $this->hashCall(self::CALL_DO_TRANSACTION, $nvpstr);

        // Résultat de l'appel
        $ack = strtoupper($response_array['ACK']);

        if($ack != 'SUCCESS' && $ack != 'SUCCESSWITHWARNING') {

            $exception = new PayPalException($response_array);
            if ($exception->isFundingFailure()) {
                redirect($this->config['url'] . $token . '&PAYERID=' . $payer_id);
            }

            // Erreur lors de la communication avec l'API
            throw $exception;
        }

        return (object) [
            'payerId' => $payer_id,
            'email' => $_SESSION['PAYPAL']['PAYER_EMAIL'],
            'transactionId' => $response_array['TRANSACTIONID'],
            'responseHash' => $response_array
        ];
    }

    public function addItem($name, $price, $quantity, $description = "", $code = "")
    {
        $this->items[] = [
            'code' => $code,
            'name' => $name,
            'price' => $price,
            'description' => $description,
            'quantity' => $quantity
        ];
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    /**
     * @return double
     */
    public function getShippingPrice()
    {
        return $this->shippingPrice;
    }

    /**
     * @param double $shippingPrice
     */
    public function setShippingPrice($shippingPrice)
    {
        $this->shippingPrice = $shippingPrice;
    }

    /**
     * @return double
     */
    public function getHandlingPrice()
    {
        return $this->handlingPrice;
    }

    /**
     * @param double $handlingPrice
     */
    public function setHandlingPrice($handlingPrice)
    {
        $this->handlingPrice = $handlingPrice;
    }

    /**
     * @return string
     */
    public function getClientEmail()
    {
        return $this->clientEmail;
    }

    /**
     * @param string $clientEmail
     */
    public function setClientEmail($clientEmail)
    {
        $this->clientEmail = $clientEmail;
    }

    private function initializeConnection()
    {
        // Obtention des articles du panier
        $items = $this->items;

        // Création dynamique des variables
        for ($i = 0; $i < count($items); $i++) {
            ${"L_NAME$i"} = $items[$i]['name'];
            ${"L_AMT$i"} = $items[$i]['price'];
            ${"L_QTY$i"} = $items[$i]['quantity'];
            ${"L_NUMBER$i"} = $items[$i]['code'];
            ${"L_DESC$i"} = $items[$i]['description'];
        }

        // Création des URLs de retour
        $return_url = urlencode($this->returnUrl);
        $cancel_url = urlencode($this->config['cancel_url']);

        // Variables de taxe
        $tps_rate = 0;
        $tvq_rate = 0;

        // Création des paramètre NVP
        $nvpstr_items = '';
        $item_amt = 0.00;
        $handling_amt = $this->handlingPrice;
        $shipping_amt = $this->shippingPrice;

        // Calcul du montant des articles
        $i = 0;
        while (isset(${"L_NAME$i"})) {
            $item_amt += (${"L_QTY$i"} * ${"L_AMT$i"});
            $i++;
        }

        // Calculer les taxes
        $tps = ($item_amt + $shipping_amt + $handling_amt) * ($tps_rate / 100);
        $tvq = ($item_amt + $shipping_amt + $handling_amt + $tps) * ($tvq_rate / 100);
        $tax = $tps + $tvq;

        // Formatter les montants
        $amt = number_format($item_amt + $tax + $shipping_amt + $handling_amt, 2);
        $max_amt = number_format($item_amt + $tax + $shipping_amt + $handling_amt + 25, 2);
        $item_amt = number_format($item_amt, 2);
        $tax = number_format($tax, 2);

        // Appliquer les totaux
        $_SESSION['PAYPAL']['TAX_AMT'] = $tax;
        $_SESSION['PAYPAL']['ITEM_AMT'] = $item_amt;
        $_SESSION['PAYPAL']['AMT'] = $amt;
        $_SESSION['PAYPAL']['TPS_RATE'] = $tps_rate;
        $_SESSION['PAYPAL']['TVQ_RATE'] = $tvq_rate;
        $_SESSION['PAYPAL']['TPS'] = $tps;
        $_SESSION['PAYPAL']['TVQ'] = $tvq;

        // Génération du NVP des articles
        $i = 0;
        while (isset(${"L_NAME$i"})) {
            $nvpstr_items .= "L_NAME$i=" . urlencode(${"L_NAME$i"}) .
                /* "&L_NUMBER$i=" . (${"L_NUMBER$i"}) . */
                /* "&L_DESC$i=" . (${"L_DESC$i"}) . */
                "&L_AMT$i=" . (${"L_AMT$i"}) .
                "&L_QTY$i=" . (${"L_QTY$i"}) . "&";
            $i++;
        }

        // Paramètres du NVP
        $nvpstr = 'EMAIL=' . $this->clientEmail . '&' .
            'ALLOWNOTE=0&' .
            'HDRIMG=' . $this->config['logo'] . '&' .
            'LOCALECODE=CA&' .
            'ADDRESSOVERRIDE=1&' .
            /* 'SHIPTONAME=' . $_SESSION['PAYPAL']['PERSON_NAME'] . '&' . */
            /* 'SHIPTOSTREET=' . $_SESSION['PAYPAL']['SHIP_STREET'] . '&' . */
            /* 'SHIPTOCITY=' . $_SESSION['PAYPAL']['SHIP_CITY'] . '&' . */
            /* 'SHIPTOSTATE=' . $_SESSION['PAYPAL']['SHIP_STATE'] . '&' . */
            /* 'SHIPTOCOUNTRYCODE=' . $_SESSION['PAYPAL']['SHIP_COUNTRY'] . '&' . */
            /* 'SHIPTOZIP=' . $_SESSION['PAYPAL']['SHIP_ZIP'] . '&' . */
            /* 'SHIPTOPHONE=' . $_SESSION['PAYPAL']['SHIP_PHONE'] . '&' . */
            $nvpstr_items .
            'AMT=' . $amt . '&' .
            'MAXAMT=' . $max_amt . '&' .
            'ITEMAMT=' . $item_amt . '&' .
            'SHIPPINGOPTIONAMOUNT=' . $shipping_amt . '&' .
            /* 'SHIPPINGOPTIONNAME=' . $shipping_label . '&' . */
            'SHIPPINGOPTIONISDEFAULT=true&' .
            'SHIPPINGAMT=' . $shipping_amt . '&' .
            'HANDLINGAMT=' . $handling_amt . '&' .
            'TAXAMT=' . $tax . '&' .
            'ReturnUrl=' . $return_url . '&' .
            'CANCELURL=' . $cancel_url . '&' .
            'CURRENCYCODE=' . $this->config['currency'] . '&' .
            'PAYMENTACTION=sale';

        // Appliquer le header NVP si disponible
        $nvpstr = '&' . $nvpstr;

        // Effectuer l'appel a PayPal pour la création d'un token
        $response_array = $this->hashCall(self::CALL_INIT_CHECKOUT, $nvpstr);
        $_SESSION['PAYPAL']['RESPONSE_HASH'] = $response_array;

        if (isset($response_array['ACK']) && strtoupper($response_array['ACK']) == 'SUCCESS') {

            // Redirection vers PayPal
            $token = urldecode($response_array['TOKEN']);
            redirect($this->config['url'] . $token);

        } else  {

            // Erreur lors de la communication avec l'API
            throw new PayPalException($response_array);
        }
    }

    private function orderReview()
    {
        // Obtention de l'identifiant de session PayPal
        $token = urlencode($_GET['token']);

        // Création d'une 2e requête à PayPal en utilisant l'IDentifiant de session
        $nvpstr = '&TOKEN=' . $token;

        // Effectuer l'appel a PayPal pour la procédure de payment
        $response_array = $this->hashCall(self::CALL_DETAIL_CHECKOUT, $nvpstr);
        $_SESSION['PAYPAL']['RESPONSE_HASH'] = $response_array;

        // Résultat de l'appel
        $ack = strtoupper($response_array['ACK']);

        if ($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING') {

            // Appliquer les nouveaux éléments dans la SESSION
            $_SESSION['PAYPAL']['TOKEN'] = $token;
            $_SESSION['PAYPAL']['PAYER_ID'] = $response_array['PAYERID'];
            $_SESSION['PAYPAL']['TOTAL_AMT'] = $response_array['AMT'] + $response_array['SHIPDISCAMT'];
            $_SESSION['PAYPAL']['PAYER_EMAIL'] = $response_array['EMAIL'];

            // Redirection
            if (!empty($this->config['review_url'])) {
                redirect($this->config['review_url']);
            } else {
                redirect($this->config['complete_url']);
            }

        } else {

            // Erreur lors de la communication avec l'API
            throw new PayPalException($response_array);
        }
    }


    /**
     * Effectue des appels à l'API PayPal (nvp) en utilisant la bibliothèque
     * cURL.
     *
     * @param $method
     * @param $nvp
     * @return array
     */
    private function hashCall($method, $nvp)
    {
        // Création de l'entête
        $nvp_header = $this->nvpHeader();

        // Initialisation de cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->config['api.endpoint']);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Désactiver la vérification serveur et peer (TrustManager Concept)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        if($this->config['auth.mode'] == self::AUTH_PERMISSION) {

            // Dans le cas d'une permission API, utiliser HTTPheaders
            $headers_array[] = "X-PP-AUTHORIZATION: " . $nvp_header;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
            curl_setopt($ch, CURLOPT_HEADER, false);

        } else {

            // Appliquer le header NVP si disponible pour les autres cas
            // d'authentification.
            $nvp = $nvp_header . $nvp;
        }

        if ($this->config['proxy.use']) {
            // Appliquer un proxy si nécessaire
            curl_setopt(
                $ch, CURLOPT_PROXY,
                $this->config['proxy.server'] . ':' . $this->config['proxy.port']
            );
        }

        if (strlen(str_replace('VERSION=', '', strtoupper($nvp))) == strlen($nvp)) {

            // Vérifier la version dans le NVP
            $nvp = '&VERSION=' . urlencode($this->config['api.version']) . $nvp;
        }

        // Création de la requête NVP
        $nvp_request = 'METHOD=' . urlencode($method) . $nvp;

        // Appliquer la requête NVP au POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvp_request);

        // Obtention de la réponse du serveur API
        $nvp_response = curl_exec($ch);

        // Convertir la réponse en tableau associatif
        $response_array = $this->nvpToArray($nvp_response);

        // Convertir la requête en tableau associatif
        //$request_array = $this->nvpToArray($nvp_request);

        // Appliquer la requête dans la session
        // $_SESSION['PAYPAL']['REQUEST_HASH'] = $request_array;

        // Fermer cURL
        curl_close($ch);
        return $response_array;
    }

    /**
     * Défini un entête pour le NVP selon les autorisations d'API. Connexion
     * forcée par le type « 3TOKEN » qui nécessite seulement le nom
     * d'utilisateur, le mot de passe et la signature.
     *
     * @return string
     */
    private function nvpHeader()
    {
        // Variable d'application
        $nvp_header_str = '';

        // Selon le mode d'authentification, construire l'entête NVP en vu
        // d'une transaction avec PayPal.
        switch($this->config['auth.mode']) {

            // Construction entête selon 3TOKEN
            case self::AUTH_3TOKEN :
                $nvp_header_str = '&PWD=' . urlencode($this->config['api.password']) .
                    '&USER=' . urlencode($this->config['api.username']).
                    '&SIGNATURE=' . urlencode($this->config['api.signature']);
                break;

            // Construction entête selon FIRSTPARTY
            case self::AUTH_FIRSTPARTY :
                $nvp_header_str = '&SUBJECT=' . urlencode($this->config['api.subject']);
                break;

            // Construction entête selon 3PARTY
            case self::AUTH_THIRDPARTY :
                $nvp_header_str = '&PWD=' . urlencode($this->config['api.password']) .
                    '&USER=' . urlencode($this->config['api.username']) .
                    '&SIGNATURE=' . urlencode($this->config['api.signature']) .
                    '&SUBJECT='.urlencode($this->config['api.subject']);
                break;

            // Construction entête selon PERMISSION
            case self::AUTH_PERMISSION :
                $nvp_header_str = 'token=' . $this->config['auth.token'] . ',' .
                    'signature=' . $this->config['auth.signature'] . ',' .
                    'timestamp=' . $this->config['auth.timestamp'];
                break;
        }

        // Retourner l'entête
        return $nvp_header_str;
    }

    /**
     * Conversion d'une chaine NVP en tableau associatif.
     *
     * @param $nvp
     * @return array
     */
    private function nvpToArray($nvp)
    {
        $intial = 0;
        $nvpArray = [];

        // Tant que la chaine existe
        while (strlen($nvp)) {

            // Position de la clé
            $keypos = strpos($nvp, '=');

            // Position de la valeur
            $valuepos = ((strpos($nvp, '&') !== false)
                ? strpos($nvp, '&')
                : strlen($nvp));

            // Obtenir la clé et la valeur
            $keyval = substr($nvp, $intial, $keypos);
            $valval = substr($nvp, $keypos + 1, $valuepos - $keypos - 1);

            // Décoder la réponse
            $nvpArray[urldecode($keyval)] = urldecode($valval);

            // Supprimer de la chaine
            $nvp = substr($nvp, $valuepos + 1, strlen($nvp));
        }

        return $nvpArray;
    }
}