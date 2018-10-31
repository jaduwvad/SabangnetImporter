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
        $orders = array();
        $i = 0;

        foreach ($results as $orderData) {
            $orderData = $this->toUtf8($orderData);
            array_push($orders, $this->getOrderSingleData($orderData));
        }

        $orders = $this->getOrderMergedData($orders);
        $customers = $this->getCustomerData($orders);

        $this->exportFiles($this->toUtf8($orders), ';', "Bestellung.csv");
        $this->exportFiles($this->toUtf8($customers), ';',  "Kunden.csv");

        //echo json_encode(array(
        //    'success' => false,
        //    'message' => $orders[0],
        //));
        return;
    }

    protected function exportFiles($orders, $delimiter, $filename) {
        $result = implode(";", array_keys($orders[0]))."\r\n";
        foreach ($orders as $order) 
            $result .= implode(";", $order)."\r\n";
        

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename='.$filename.';');

        echo $result;

        return;
    }

    protected function getOrderSingleData($orderData){
        $addr_attr = $this->getAddrAttr(str_replace("-", "",  $orderData['POSTCODE']));
        $currency = $this->getCurrency();

        $order['orderID'] = $orderData['Bestellnr Sabangnet'];
        $order['numberId'] = $orderData['bestellnr'];
        $order['customerId'] = $orderData['bestellnr'];
        $order['billing_firstname'] = $orderData['Kunden Name'];
        $order['billing_lastname'] = $orderData['Kunden Name'];
        $order['ustid'] = "";
        $order['shipping_street'] = $orderData['Adresse'];
        $order['shipping_streetnumber'] = "";
        $order['shipping_zipcode'] = "".str_replace("-", "",  $orderData['POSTCODE']);
        $order['shipping_city'] = $addr_attr['center_name'];
        $order['shippingCountryName'] = 'Südkorea';
        $order['shipping_countryID'] = 38;
        $order['status'] = "";
        $order['cleared'] = 10;
        $order['cleareddate'] = date("d.m.Y");
        $order['paymentID'] = "";
        $order['dispatchID'] = 32;
        $order['partnerID'] = "";
        $order['subshopID'] = 3;
        $order['invoice_amount'] = '';
        $order['invoice_amount_net'] = $orderData['Totalpreis'];
        $order['invoiceShipping'] = "";
        $order['invoiceShippingNet'] = number_format($orderData['Versandkosten']/$currency, 1)."0";
        $order['orderTime'] = date("d.m.Y");
        $order['transactionID'] = "";
        $order['comment'] = "";
        $order['customerComment'] = $orderData['customercomments'];
        $order['internalComment'] = "";

        $telNum = str_replace("-", "", $orderData['tel von Empfänger']);
        if(substr($orderData['tel von Empfänger'], 0, 3) === "010"){
            $order['phone'] = "0082".strstr($telNum, '1');
            $order['mobile'] = $order['phone'];
        } else {
            $order['phone'] = "0082".$telNum;
            $order['mobile'] = "0082";
        }

        $order['net'] = "1";
        $order['taxfree'] = "1";
        $order['temporaryID'] = "1";
        $order['referer'] = "";
        $order['trackingCode'] = "";
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
 
        $order['articleNumber'] = "";
        $order['price'] = number_format($orderData['Preis']/$currency, 1)."0";
        $order['quantity'] = $orderData['menge'];
        $order['articleName'] = "";
        $order['shipped'] = "";
        $order['shippedGroup'] = "";
        $order['releaseDate'] = "";
        $order['mode'] = "";
        $order['esdArticle'] = "";
        $order['config'] = "";
        $order['spedition'] = "pantos";
        $order['deviceType'] = "";
        $order['attribute1'] = "";
        $order['attribute2'] = $orderData['Bestellnr Sabangnet'];
        $order['attribute3'] = $orderData['bestellnr'];
        $order['attribute7'] = $addr_attr['center_num'];
        $order['attribute8'] = $addr_attr['center_name'];
        $order['attribute9'] = $addr_attr['del_center_num'];
        $order['attribute10'] = $addr_attr['del_center_name'];
        $order['attribute11'] = $addr_attr['center_team_num'];
        $order['attribute12'] = $addr_attr['center_local_num'];
        $order['attribute13'] = $orderData['Name'];


        $shop = $orderData['market'];
        if($shop === '스토어팜'){
            $order['ustid'] = $orderData['Option6'];
            $order['partnerID'] = 'Naver';
            $order['paymentID'] = 7;
	    $articleNumber = $orderData['Option7'];
            $order['invoice_amount_net'] -= $orderData['Option8']+$orderData['Option9'];
            $order['invoiceShippingNet'] *= 96/100;
            $order['attribute1'] = $orderData['Naver/Gmarket Nr'];
        } 
        else if($shop === '지마켓'){
            $order['ustid'] = $orderData['Option8'];
            $order['partnerID'] = 'G9';
            $order['paymentID'] = 16;
            $articleNumber = $orderData['Option7'];
            $order['invoice_amount_net'] -= str_replace(",", "", $orderData['Option6']);
            $order['attribute1'] = $orderData['Naver/Gmarket Nr'];
        } 
        else if($shop === '옥션'){
            $order['ustid'] = $orderData['Option10'];
            $order['partnerID'] = 'eBay-Korea';
            $order['paymentID'] = 8;
	    $articleNumber = $orderData['Option7'];
            $order['invoice_amount_net'] -= str_replace(",", "", $orderData['Option6']);
            $order['attribute1'] = $orderData['Naver/Gmarket Nr'];
        } 
        else if($shop === '11번가'){
            $order['ustid'] = $orderData['Option4'];
            $order['partnerID'] = '11St';
            $order['paymentID'] = 9;
	    $articleNumber = $orderData['Option6'];
            $order['invoice_amount_net'] -= str_replace(",", "", $orderData['Option7']);
            $order['attribute1'] = $orderData['11St Versand Nr'];
        }

        $order['invoice_amount_net'] = number_format($order['invoice_amount_net']/$currency, 1)."0";
        

        if(strpos($articleNumber, "_SET_") !== false){
            $temp = explode("_", $articleNumber);
            $articleNumber = $temp[2];
            $quantity = $temp[0];
            $order['quantity'] = $quantity;
            $order['price'] /= $quantity;
        }

        $order['articleNumber'] = $articleNumber;

        $articleName = $this->getArticleName($order['articleNumber']);

        if($articleName == ""){
            $anTemp = $this->getUpdateArticleNumber($order['articleNumber']);
            if($anTemp !== ""){
                $order['articleNumber'] = $anTemp;
                $articleName = $this->getArticleName($order['articleNumber']);
            }
        }
            

        $order['articleName'] = $articleName;

        return $order;
    }


    protected function getOrderMergedData($orders) {
        $lastTrackingCode = $this->getLastTrackingCode();
        $lastNumberId = 0;
        $orderNum = count($orders);

        $internalComment = "";
        $attr1 = "";
        $attr2 = "";
        $attr3 = "";

        for($i=0; $i<count($orders); $i++) {
            if($lastNumberId !== $orders[$i]['numberId']) {
                $lastNumberId = $orders[$i]['numberId'];
                $orders[$i]['trackingCode'] = $lastTrackingCode;
                $lastTrackingCode += 1;

                $internalComment = $orders[$i]['numberId'].','.','.$orders[$i]['attribute1'];
                $attr1 = $orders[$i]['attribute1'];
                $attr2 = $orders[$i]['attribute2'];
                $attr3 = $orders[$i]['attribute3'];
            

                for($j = $i+1; $j<count($orders); $j++) {
                    if($lastNumberId !== $orders[$j]['numberId'])
                        break;

                    $internalComment = $orders[$j]['numberId'].','.$internalComment.','.$orders[$j]['attribute1'];
                    $attr1 .= '|'.$orders[$j]['attribute1'];
                    $attr2 .= '|'.$orders[$j]['attribute2'];
                    $attr3 .= '|'.$orders[$j]['attribute3'];

                    $orders[$j]['trackingCode'] = "";
                    $orders[$j]['invoiceShippingNet'] = 0;
                }
            }

            $orders[$i]['internalComment'] = $internalComment;
            $orders[$i]['attribute1'] = $attr1;
            $orders[$i]['attribute2'] = $attr2;
            $orders[$i]['attribute3'] = $attr3;
        }

        return $orders;
    }

    protected function getCustomerData($orders) {
        $customs = array();

        foreach( $orders as $order){
            if($order['trackingCode'] === "")
                continue;

            $custom['customernumber'] = $order['numberId'];
            $custom['email'] = "";

            $custom['password'] = "$2y$10\$SgvUYRixxJK/.c0UaXPfl/k000ecab75ab-d67b-11e6-a185-4061862b98fd";
            $custom['encoder'] = "bcrypt";
            $custom['billing_company'] = "";
            $custom['billing_department'] = "";
            $custom['billing_salutation'] = "ms";
            $custom['billing_firstname'] = $order['billing_firstname'];
            $custom['billing_lastname'] = $order['billing_lastname'];
            $custom['billing_street'] = $order['shipping_street'];
            $custom['billing_zipcode'] = $order['shipping_zipcode'];
            $custom['billing_city'] = $order['shipping_city'];
            $custom['phone'] = $order['phone'];

            if( $order['mobile'] === "0082" ) {
                $custom['mobile'] = "";
                $custom['mobile_Notifi'] = 0;
            }
            else{
                $custom['mobile'] = $order['mobile'];
                $custom['mobile_Notifi'] = 1;
            }

            $custom['billing_countryID'] = 38;
            $custom['billing_stateID'] = "";
            $custom['ustid'] = $order['ustid'];
            $custom['paymentID'] = $order['paymentID'];
            
            $custom['customergroup'] = $order['partnerID'];

            $custom['language'] = 3;
            $custom['subshopID'] = 3;
            $custom['active'] = 0;

            if($order['partnerID'] === 'eBay-Korea'){
                $custom['email'] = $order['numberId']."@korea.com";
                $custom['customergroup'] = "G9";
            }
            else if($order['partnerID'] === 'Naver'){
                $custom['email'] = $order['numberId']."@naver.com";
                $custom['customergroup'] = "NK";
            }
            else if($order['partnerID'] === 'G9'){
                $custom['email'] = $order['numberId']."@gmarket.com";
                $custom['paymentID'] = 8;
            }
            else if($order['partnerID'] === '11St')
                $custom['email'] = $order['numberId']."@11st.com";

            array_push($customs, $this->toUtf8($custom));
        }

        return $customs;
    }

    protected function getUpdateArticleNumber($articleNumber){
        $anQuery = "select articleNumber_after from articleNumber_update where articleNumber_before = \"".$articleNumber."\"";
        $an = Shopware()->Models()->getConnection()->fetchAll($anQuery)[0]['articleNumber_after'];

        return $an;
    }

    protected function getArticleName($articleNumber) {
        $anQuery = "SELECT name FROM s_articles WHERE id = (SELECT articleID FROM s_articles_details WHERE ordernumber=\"".$articleNumber."\");";
        $articleName = Shopware()->Models()->getConnection()->fetchAll($anQuery)[0]['name'];

        return $articleName;
    }

    protected function getLastTrackingCode(){
        $tcQuery = "SELECT trackingCode FROM s_order o ORDER BY o.trackingCode DESC";
        $lastTrackingCode = Shopware()->Models()->getConnection()->fetchAll($tcQuery)[0]['trackingCode'];

        return $lastTrackingCode+1;
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
