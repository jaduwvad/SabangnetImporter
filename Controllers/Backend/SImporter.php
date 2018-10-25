<?php


class Shopware_Controllers_Backend_SImporter extends Shopware_Controllers_Backend_ExtJs {

    protected $uploadedFilePath;

    protected $customerRepository;

    protected $customerGroupRepository;

    protected $orderRepository;

    protected $productDetailRepository;

    protected $ordersMap;

    protected $codes = array();

    protected $manager = null;

    public function importAction(){ 
        try {
            @set_time_limit(0);
            $this->Front()->Plugins()->Json()->setRenderer(false);

            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(array(
                    'success' => false,
                    'message' => "Could not upload file",
                ));
                return;
            }

            $fileName  = basename($_FILES['file']['name']);
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($extension, array('csv'))) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Upload as csv',
                ));
                return;
            }

            $destPath = Shopware()->DocPath('media_' . 'temp');
            if (!is_dir($destPath)) {
                mkdir($destPath, 0777, true);
            }

            $destPath = realpath($destPath);
            $filePath = tempnam($destPath, 'import_');

            if (false === move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                echo json_encode(array(
                    'success' => false,
                    'message' => sprintf("Could not move %s to %s.", $_FILES['file']['tmp_name'], $filePath)
                ));
                return;
            }
            $this->uploadedFilePath = $filePath;
            chmod($filePath, 0644);

            $this->importProcess($filePath);
            return;

        } catch (\Exception $e) {
            // At this point any Exception would result in the import/export frontend "loading forever"
            // Append stack trace in order to be able to debug
            $message = $e->getMessage()."<br>\r\nStack Trace:".$e->getTraceAsString();
            echo json_encode(array(
                'success' => false,
                'message' => $message
            ));
            return;
        }
    }

    protected function importProcess($filePath) {
        $results = new Shopware_Components_CsvIterator($filePath, ';');
        $test = "";
        $order = array();
        $i = 0;
        foreach ($results as $orderData) {
            $orderData = $this->toUtf8($orderData);
            array_push($order, $this->getOrderSingleData($orderData));
        }

        //$order = $this->getOrderMergedData($order);

        echo json_encode(array(
            'success' => false,
            'message' => count($order),
        ));
        return;
    }

    protected function getOrderSingleData($orderData){
        $order['orderID'] = $orderData['Bestellnr Sabangnet'];
        $order['numberID'] = $orderData['bestellnr'];
        $order['customerId'] = $orderData['bestellnr'];
        $order['billing_firstname'] = $orderData['Kunden Name'];
        $order['billing_lastname'] = $orderData['Kunden Name'];
        $order['shipping_street'] = $orderData['Adresse'];
        $order['shipping_streetnumber'] = "";
        $order['shipping_zipcode'] = str_replace("-", "",  $orderData['POSTCODE']);
        $order['shippingCountryName'] = 'Südkorea';
        $order['shipping_countryID'] = 38;
        $order['cleared'] = 10;
        $order['dispatchID'] = 32;
        $order['subshopID'] = 3;
        $order['invoice_amount'] = $orderData[''];
        $order['invoiceShipping'] = "";
        $order['transactionID'] = "";
        $order['comment'] = "";
        $order['customerComment'] = $orderData['customercomments'];
        $order['net'] = "";
        $order['taxfree'] = "";
        $order['temporaryID'] = "";
        $order['referer'] = "";
        $order['languageIso'] = 3;
        $order['currency'] = "EUR";
        $order['currencyfactor'] = "";
        $order['remoteAddress'] = "";
        $order['orderDetailId'] = "";
        $order['articleId'] = "";
        $order['taxId'] = "";
        $order['taxRate'] = "";
        $order['statusId'] = "";
        $order['number'] = "";
        $order['quantity'] = $orderData['menge'];
        $order['shipped'] = "";
        $order['shippedGroup'] = "";
        $order['releaseDate'] = "";
        $order['mode'] = "";
        $order['esdArticle'] = "";
        $order['config'] = "";
        $order['spedition'] = "pantos";
        $order['deviceType'] = "";
        $order['attribute2'] = $orderData['Bestellnr Sabangnet'];
        $order['attribute3'] = $orderData['bestellnr'];
        $order['attribute13'] = $orderData['Name'];
        $order['cleareddate'] = date("d.m.Y");
        $order['orderTime'] = date("d.m.Y");
        $order['internalComment'] = "";

        if(substr($orderData['tel von Empfänger'], 0, 3) === "010"){
            $order['phone'] = "0082".strstr($orderData['tel von Empfänger'], '1');
            $order['mobile'] = $order['phone'];
        } else {
            $order['phone'] = "0082".$orderData['tel von Empfänger'];
            $order['mobile'] = "0082";
        }

        $shop = $orderData['market'];
        if($shop === '스토어팜'){
            $order['ustid'] = $orderData['Option6'];
            $order['partnerID'] = 'Naver';
            $order['paymentID'] = 7;
	    $order['articleNumber'] = $orderData['Option7'];
            $order['attribute1'] = $orderData['Naver/Gmarket Nr'];
        } 
        else if($shop === '지마켓'){
            $order['ustid'] = $orderData['Option8'];
            $order['partnerID'] = 'G9';
            $order['paymentID'] = 16;
            $order['articleNumber'] = $orderData['Option7'];
            $order['attribute1'] = $orderData['Naver/Gmarket Nr'];
        } 
        else if($shop === '옥션'){
            $order['ustid'] = $orderData['Option10'];
            $order['partnerID'] = 'eBay-Korea';
            $order['paymentID'] = 8;
	    $order['articleNumber'] = $orderData['Option7'];
            $order['attribute1'] = $orderData['Naver/Gmarket Nr'];
        } 
        else if($shop === '11번가'){
            $order['ustid'] = $orderData['Option4'];
            $order['partnerID'] = '11St';
            $order['paymentID'] = 9;
	    $order['articleNumber'] = $orderData['Option6'];
            $order['attribute1'] = $orderData['11St Versand Nr'];
        }

        $addr_attr = $this->getAddrAttr($order['shipping_zipcode']);
        $order['shipping_city'] = $addr_attr['center_name'];
        $order['attribute7'] = $addr_attr['center_num'];
        $order['attribute8'] = $addr_attr['center_name'];
        $order['attribute9'] = $addr_attr['del_center_num'];
        $order['attribute10'] = $addr_attr['del_center_name'];
        $order['attribute11'] = $addr_attr['center_team_num'];
        $order['attribute12'] = $addr_attr['center_local_num'];

        $currency = $this->getCurrency();

        $order['price'] = $orderData['Preis']/$currency;
        //$order['articleName'] = $this->getArticleName($orderData['articleNumber']);
        //select name from s_articles where id = (select articleID from s_articles_details where ordernumber=%s group by articleID)

        return $order;
    }

    protected function getOrderMergedData($orders) {
        $lastTrackingCode = $this->getLastTrackingCode();
        $lastNumberId = 0;
        $orderNum = count($orders);

        for($i = 0; $i<$orderNum; $i++) {
            if( $orders[$i]['internalComment'] !== "")
                continue;

            for($j=$i; $j<$orderNum; $j++){
                
            }
            
        }
    }

    protected function getCustomData($orders) {
        $customs = array();

        foreach($orders as $order){
            if($order['trackingcode'] === "")
                continue;

            $custom['customnumber'] = $order['numberId'];
            $custom['password'] = "$2y$10$SgvUYRixxJK/.c0UaXPfl/k000ecab75ab-d67b-11e6-a185-4061862b98fd";
            $custom['encoder'] = "bcrypt";
            $custom['billing_company'] = "";
            $custom['billing_department'] = "";
            $custom['billing_salutation'] = "ms";
            $custom['billing_firstname'] = $order['billing_firstname'];
            $custom['billing_lastname'] = $order['billing_lastname'];
            $custom['billing_street'] = $order['shipping_street'];
            $custom['billing_zipcode'] = $order['shipping_zipcode'];
            $custom['phone'] = $order['phone'];
            $custom['billing_countryID'] = 38;
            $custom['billing_stateID'] = "";
            $custom['ustid'] = $order['ustid'];
            $custom['paymentID'] = $order['paymentID'];
            $custom['customergroup'] = $order['partnerID'];

            $custom['language'] = 3;
            $custom['subshopID'] = 3;
            $custom['active'] = 0;

            if( $order['mobile'] === "0082" ) {
                $custom['mobile'] = "";
                $custom['mobile_Notifi'] = 0;
            }
            else{
                $custom['mobile'] = $order['mobile'];
                $custom['mobile_Notifi'] = 1;
            }

            else if($order
            if($order['partnerID'] === 'eBay-Korea'){
                $custom['email'] = $custom['customernumber']."@korea.com";
            }
            if($order['partnerID'] === 'Naver'){
                $custom['email'] = $custom['customernumber']."@naver.com";
            }
            if($order['partnerID'] === 'G9'){
                $custom['email'] = $custom['customernumber']."@gmarket.com";
            }
            if($order['partnerID'] === '11St'){
                $custom['email'] = $custom['customernumber']."@11st.com";
            }
        }
    }

    protected function getLastTrackingCode(){
        $tcQuery = "SELECT trackingcode FROM s_order o ORDER BY o.trackingcode DESC";
        $lastTrackingCode = Shopware()->Models()->getConnection()->fetchAll($tcQuery)[0]['trackingcode'];

        return $lastTrackingCode+1;
    }

    protected function getArticleName($an){
        $anQuery = "select name from s_articles where id = (select articleID from s_articles_details where ordernumber=".$an." group by articleID)";
        $articleName = Shopware()->Models()->getConnection()->fetchAll($anQuery)[0];
        return $this->toUtf8($articleName)['name'];
    }

    protected function getCurrency() {
        $currencyQuery = "select factor from s_core_currencies where id = 6";
        $currency = Shopware()->Models()->getConnection()->fetchAll($currencyQuery)[0]['factor'];

        return $currency;
    }

    protected function getAddrAttr($zipcode) {
        $attrQuery = "select * from shipping_addr_attr where zipcode = ".$zipcode;
        $attr = Shopware()->Models()->getConnection()->fetchAll($attrQuery);

        return $attr[0];
    }

    protected function toUtf8(array $input)
    {
        array_walk_recursive($input, function (&$value) {
            $isUtf8 = (mb_detect_encoding($value, 'UTF-8', true) !== false);
            if (!$isUtf8) {
                $value = utf8_encode($value);
            }
            return $value;
        });

        return $input;
    }
}
