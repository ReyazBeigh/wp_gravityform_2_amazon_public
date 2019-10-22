<?php

class Amazon {

    public function __construct() {
        //include all sub classes
        $this->_includeDirectory(PLUGIN_DIR . '/lib/amazon');
    }

    private function _includeDirectory($dir) {
        $filesItr = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($filesItr as $file) {
            if (!$file->isDir()) {
                $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);

                if ($ext == 'php')
                    require_once $file->getPathname();
            }
        }
    }

    // gets full Order including order items
    public function getOrder($ordId) {
        $orderResp = $this->getOrderById($ordId);

        if ($orderResp->isSuccess) {
            $orderItemsResp = $this->getOrderItems($ordId);
            if ($orderItemsResp->isSuccess) {
                $orderResp->response['OrderItems'] = $orderItemsResp->response;
                return $orderResp;
            }

            return $orderItemsResp;
        } else {
            return $orderResp;
        }
    }

    // gets order by id
    public function getOrderById($ordId) {
        $serviceUrl = "https://mws.amazonservices.com/Orders/2013-09-01";

        $config = array(
            'ServiceURL' => $serviceUrl,
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'MaxErrorRetry' => 3,
        );

        $service = new MarketplaceWebServiceOrders_Client(
                AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AM_APPLICATION_NAME, APPLICATION_VERSION, $config);

        $request = new MarketplaceWebServiceOrders_Model_GetOrderRequest();
        $request->setSellerId(MERCHANT_ID);
        $request->setAmazonOrderId($ordId);

        try {
            $rawResp = $service->GetOrder($request);
            $response = $this->_processResponse($rawResp);

            if (isset($response['GetOrderResult']['Orders']['Order'])) {
                // DT_Common::debug($response);
                // exit;
                $order = $response['GetOrderResult']['Orders']['Order'];
                return new ApiResponse(true, $order);
            }
            // error in getting order
            return new ApiResponse(false, false, $rawResp);
        } catch (MarketplaceWebServiceOrders_Exception $ex) {
            // exception while getting order
            return new ApiResponse(false, false, $ex);
        }
    }

    // gets order items by ord id
    public function getOrderItems($ordId) {
        $serviceUrl = "https://mws.amazonservices.com/Orders/2013-09-01";

        $config = array(
            'ServiceURL' => $serviceUrl,
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'MaxErrorRetry' => 3,
        );

        $service = new MarketplaceWebServiceOrders_Client(
                AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, APPLICATION_NAME, APPLICATION_VERSION, $config);

        $request = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
        $request->setSellerId(MERCHANT_ID);
        $request->setAmazonOrderId($ordId);

        try {
            $rawResp = $service->ListOrderItems($request);
            $response = $this->_processResponse($rawResp);

            // print_r($response);
            // exit;

            if (isset($response['ListOrderItemsResult']['AmazonOrderId'])) {
                $orderItems = $response['ListOrderItemsResult']['OrderItems']['OrderItem'];

                // to keep consistent format
                if (isset($orderItems['ASIN']))
                    $orderItems = [$orderItems];

                return new ApiResponse(true, $orderItems);
            }

            return new ApiResponse(false, false, $rawResp);
        } catch (MarketplaceWebServiceOrders_Exception $ex) {
            return new ApiResponse(false, false, $ex);
        }
    }

    // get all orders
    public function listOrders() {
        
    }

    private function _processResponse($response) {
        try {
            $dom = new DOMDocument();
            $dom->loadXML($response->toXML());
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $xml = $dom->saveXML();
            $orderdata = new SimpleXMLElement($xml);
            return json_decode(json_encode($orderdata, TRUE), true);
        } catch (Exception $e) {
            // add logs
        }
    }

}


	
	
	
	class ApiResponse
	{
		var $isSuccess;
		var $response;
		var $errorMsg;
		
		public function __construct($isSuccess,$response=false,$errorMsg=false)
		{
			$this->isSuccess = $isSuccess;
			$this->response = $response;
			$this->errorMsg = $errorMsg;
		}
	}