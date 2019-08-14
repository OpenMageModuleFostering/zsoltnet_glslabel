<?php
/**
 */
class ZsoltNet_GLSLabel_Block_Adminhtml_System_Config_Fieldset_Reports
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'glslabel/system/config/fieldset/reports.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
