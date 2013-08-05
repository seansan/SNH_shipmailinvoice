<?php
/**
 * Adminhtml sales orders controller extension
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */


require_once "Mage/Adminhtml/controllers/Sales/OrderController.php";

class SNH_ShipMailInvoice_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{

    public function shipmailinvoiceAction()
    {
    $orderIds = $this->getRequest()->getPost('order_ids', array());

	$size = count($orderIds);
    $shipped = 0;
	$not_shipped = 0;
	$invoiced = 0;

	if (!empty($orderIds)) {
		foreach ($orderIds as $orderId) {

			// SNH 26.1.12 Call to shipment and send email (step 1 and 2)
			$order = Mage::getModel('sales/order')->load($orderId);

			if($order->canShip()) {
				try {
				$itemQty = $order->getItemsCollection()->count();
				$shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
				$shipment = new Mage_Sales_Model_Order_Shipment_Api();
				// API needs OrderIncrementId, so change
				$lastOrderId = $order->getIncrementId();
				$order->setIsInProcess(true);
				// true sends email
				$shipmentId = $shipment->create($lastOrderId, array(), 'Shipment created through ShipMailInvoice', true, false);
				$order->addStatusHistoryComment('Shipment created by ShipMailInvoice', false);
				$shipped++;
				$order->save();
				} catch (Exception $e) { Mage::logException($e); }
			} else {
				$not_shipped++;
			}

			// SNH 26.1.12 Call to invoice (step 3)
			$invoices = Mage::getResourceModel('sales/order_invoice_collection')->setOrderFilter($orderId)->load();
			try {
				if ($invoices->getSize() > 0) {
					$invoiced++;
					if (!isset($pdf)){
					$pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
					} else {
					$pages = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
					$pdf->pages = array_merge ($pdf->pages, $pages->pages);
					}
				}
			} catch (Exception $e) { Mage::logException($e); }
		}
	
	} else {

	$this->_getSession()->addError(Mage::helper('sales')->__('No shipments and invoices created, none selected.'));

	}

	if (!empty($invoiced)) {
		// Wish we could send some feedback on how many were shipped and invoiced (refresh the sceen and then download)
		return $this->_prepareDownloadResponse('invoice'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
	} else {
		$this->_getSession()->addError(Mage::helper('sales')->__('Sent %s shipments and notications of %s requested. Cannot create all invoices.', $shipped, $size));
	}

	$this->_redirect('*/*/');

	}

public function shipinvoiceAction()
    {
    $orderIds = $this->getRequest()->getPost('order_ids', array());

	$size = count($orderIds);
    $shipped = 0;
	$not_shipped = 0;
	$invoiced = 0;

	if (!empty($orderIds)) {
		foreach ($orderIds as $orderId) {

			// SNH 26.1.12 Call to shipment and send email (step 1 and 2)
			$order = Mage::getModel('sales/order')->load($orderId);

			if($order->canShip()) {
				try {
				$itemQty = $order->getItemsCollection()->count();
				$shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
				$shipment = new Mage_Sales_Model_Order_Shipment_Api();
				// API needs OrderIncrementId, so change
				$lastOrderId = $order->getIncrementId();
				// true sends email
				$shipmentId = $shipment->create($lastOrderId, array(), 'Shipment created through ShipMailInvoice', true, false);
				$order->addStatusHistoryComment('Shipment created by ShipMailInvoice', false);
				$shipped++;
				} catch (Exception $e) { Mage::logException($e); }
			} else {
				$not_shipped++;
			}

			// SNH 26.1.12 Call to invoice (step 3)
			$invoices = Mage::getResourceModel('sales/order_invoice_collection')->setOrderFilter($orderId)->load();
			try {
				if ($invoices->getSize() > 0) {
					$invoiced++;
					if (!isset($pdf)){
					$pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
					} else {
					$pages = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
					$pdf->pages = array_merge ($pdf->pages, $pages->pages);
					}
				}
			} catch (Exception $e) { Mage::logException($e); }
		}
	
	} else {

	$this->_getSession()->addError(Mage::helper('sales')->__('No shipments and invoices created, none selected.'));

	}

	if (!empty($invoiced)) {
		return $this->_prepareDownloadResponse('invoice'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(), 'application/pdf');
		// Wish we could send some feedback on how many were shipped and invoiced
	} else {
		$this->_getSession()->addError(Mage::helper('sales')->__('Sent %s shipments of %s requested (no e-mail). Cannot create all invoices.', $shipped, $size));
	}

	$this->_redirect('*/*/');

	}

}
