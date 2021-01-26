<?php

/**
 * @version        1
 * @package        webtv
 * @author        antonio
 * @author mail    tony@bslt.it
 * @link
 * @copyright    Copyright (C) 2011 antonio - All rights reserved.
 * @license        GNU/GPL
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

jimport('joomla.application.component.helper');

require_once JPATH_COMPONENT . '/models/users.php';


class gglmsViewdettagliutente extends JViewLegacy
{

    protected $params;
    protected $_quote_iscrizione;
    protected $_html;
    protected $current_lang;

    function display($tpl = null)
    {

        try {
            JHtml::_('stylesheet', '/components/com_gglms/libraries/css/bootstrap.min.css');
            JHtml::_('script', '/components/com_gglms/libraries/js/bootstrap.min.js');
            JHtml::_('script', 'https://kit.fontawesome.com/dee2e7c711.js');

            $layout = JRequest::getWord('template', '');
            $lang = JFactory::getLanguage();
            $this->current_lang = $lang->getTag();

            if ($layout == 'quota_sinpe_anno') {

                JHtml::_('stylesheet', 'https://unpkg.com/bootstrap-table@1.18.1/dist/bootstrap-table.min.css');
                JHtml::_('script', 'https://unpkg.com/bootstrap-table@1.18.1/dist/bootstrap-table.min.js');
                JHtml::_('script', 'https://unpkg.com/bootstrap-table@1.18.1/dist/locale/bootstrap-table-' . $this->current_lang . '.min.js');

                $_user = new gglmsModelUsers();
                $_current_user = JFactory::getUser();
                $this->quote_iscrizione = $_user->get_quote_iscrizione($_current_user->id);
                $this->_html = outputHelper::get_dettaglio_pagamento_quote($this->quote_iscrizione);

                $this->setLayout("quota_sinpe_anno");

            }

            // Display the view
            parent::display($tpl);
        }
        catch (Exception $e){
            die("Access denied: " . $e->getMessage());
        }

    }
}
