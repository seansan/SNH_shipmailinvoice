<?php
class SNH_ShipMailInvoice_Model_Observer
{
    public function addMassAction($observer)
    {
        $block = $observer->getEvent()->getBlock();
        // Check if this block is a MassAction block
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction) {
            // Check if we're dealing with the Orders grid
            if ($block->getParentBlock() instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
                // The first parameter has to be unique, or you'll overwrite the old action.
                $block->addItem('ship_mail_invoice', array(
                        'label' => Mage::helper('sales')->__('Ship, Mail and Invoice'),
						'url' => $block->getUrl('*/*/shipmailinvoice'),
                    )
                );
				$block->addItem('ship_invoice', array(
                        'label' => Mage::helper('sales')->__('Ship and invoice (no email)'),
						'url' => $block->getUrl('*/*/shipinvoice'),
                    )
                );
				$block->addItem('pdf_invoice', array(
                        'label' => Mage::helper('sales')->__('Only print invoices'),
						'url' => $block->getUrl('*/*/pdfinvoice'),
                    )
                );
            }
        }
    }

}
