<?php
/**
 * Adminhtml sales orders controller extension
 *
 * @author      SNH
 */


require_once "Mage/Adminhtml/controllers/Sales/OrderController.php";

class SNH_ShipMailInvoice_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{

public function shipinvoiceAction() {
  $this->_shipmailinvoice(false, true);
  }

public function shipmailinvoiceAction() {
  $this->_shipmailinvoice(true, true);
  }

public function pdfinvoiceAction() {
  $this->_shipmailinvoice(false, false);
  }

public function _shipmailinvoice($email=true, $ship=true) {
    
	$orderIds = $this->getRequest()->getPost('order_ids', array());

    $cnt_Orders		= count($orderIds);
    $cnt_Shipments	= 0;
    $cnt_Invoices	= 0;

    if (empty($orderIds)) {
      $this->_getSession()->addNotice('No orders selected. Nothing to do.');
      $this->_redirect('adminhtml/sales_order');
    }
    
		foreach ($orderIds as $orderId) {
			if (!$ship) continue;
			$order = Mage::getModel('sales/order')->load($orderId);
			//$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			//$itemQty = (int)$order->getItemsCollection()->count();
			//$shipment = $order->prepareShipment($itemQty);
			//$shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
			$shipment = $order->prepareShipment();
			if ($shipment && $order->canShip()) {
				$shipment->register();
				if ($email) { $shipment->setEmailSent($email); }
				$shipment->getOrder()->setIsInProcess(true);
				try {
					$transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($shipment)
					->addObject($shipment->getOrder())
					->save();
					if ($email) { $shipment->sendEmail($email, ''); }
					$cnt_Shipments++;
				} catch (Mage_Core_Exception $e) {
					$this->_getSession()->addError($e, 'Cannot create shipment');
				}
				unset($shipment);
			} else {
				if ($email) { $shipment->sendEmail($email, '')->setEmailSent(true)->save(); }
				$cnt_Shipments++;
			}

		}

    if ($cnt_Shipments > 0 && $cnt_Shipments < $cnt_Orders) {
     $this->_getSession()->addNotice(Mage::helper('sales')->__('Sent %s shipments and notications of %s requested. Not all shipments were sent.', $cnt_Shipments, $cnt_Orders));
    }
	
  $invoices = Mage::getResourceModel('sales/order_invoice_collection')
	->setOrderFilter($orderIds)
	->load();
	if ($invoices->getSize() > 0) {
		$cnt_Invoices++;
		if (!isset($pdf)) {
			$pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
		} else {
			$pages = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
			$pdf->pages = array_merge($pdf->pages, $pages->pages);
		}
	}

	if ($cnt_Invoices > 0) {
		return $this->_prepareDownloadResponse('invoices_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf', $pdf->render(),'application/pdf');
	} else {
		$this->_getSession()->addError($this->__('There are no printable documents related to selected orders.'));
		$this->_redirect('adminhtml/sales_order');
	}

	} else {
	$this->_getSession()->addError($this->__('There are no items selected.'));
	$this->_redirect('adminhtml/sales_order');
	}

	$this->_redirect('adminhtml/sales_order');
	//$this->_redirect('*/*/');
}
 
}    
