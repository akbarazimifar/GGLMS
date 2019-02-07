<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Controller for single contact view
 *
 * @since  1.5.19
 */
class gglmsControllerCoupon extends JControllerLegacy
{

    public $_params;


    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->_japp = JFactory::getApplication();
        $this->_params = $this->_japp->getParams();
    }

    public function check_coupon()
    {

        $japp = JFactory::getApplication();

        $coupon = JRequest::getVar('coupon');
        $model = $this->getModel('coupon');
        $dettagli_coupon = $model->check_Coupon($coupon);

        if (empty($dettagli_coupon)) {
            $results['report'] = "<p>". $this->_params->get('messaggio_inserimento_wrong')."</p>";
            $results['valido'] = 0;
        } else {
            if (!$dettagli_coupon['abilitato']) {
                $results['report'] = "<p>". $this->_params->get('messaggio_inserimento_pending')."</p>";
                $results['valido'] = 0;
            } else {
                $model->assegnaCoupon($coupon);

                if ($dettagli_coupon['id_gruppi'])
                    $model->setUsergroupUserGroup($dettagli_coupon['id_gruppi']);

                $results['valido'] = 1;
                $results['report'] = "<p> Coupon valido. (COD.04)</p>";

                if ($dettagli_coupon['corsi_abilitati'])
                    $results['report'] .= $model->get_listaCorsiFast($dettagli_coupon['corsi_abilitati']);
                else
                    $results['report'] = $this->_params->get('messaggio_inserimento_success');
            }
        }

        echo json_encode($results);
        $japp->close();
    }

}
