<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once 'administrator/components/com_gglms/models/libs/debugg/debugg.php';
require_once JPATH_COMPONENT . '/helpers/output.php';
require_once JPATH_COMPONENT . '/helpers/utility.php';
require_once JPATH_COMPONENT . '/models/contenuto.php';
require_once JPATH_COMPONENT . '/models/unita.php';
require_once JPATH_COMPONENT . '/models/users.php';

jimport('joomla.application.component.controller');
jimport('joomla.access.access');


class gglmsController extends JControllerLegacy
{

    private $_user;
    private $_japp;
    public $_params;

    public function __construct($config = array())
    {
        parent::__construct($config);

        $this->_japp = JFactory::getApplication();


        define('PATH_PRINCIPALE', '../mediagg/');
        define('PATH_CONTENUTI', '../mediagg/contenuti/');

        JHtml::_('jquery.framework');


        JHtml::script(Juri::base() . 'components/com_gglms/libraries/js/mediaelement-and-player.js');
        JHtml::script(Juri::base() . 'components/com_gglms/libraries/js/bootstrap.min.js');


        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/bootstrap.min.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/unita.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/contenuto.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/coupon.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/coupondispenser.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/catalogo.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/adeguamento_old_gantry.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/mediaelementplayer.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/helpdesk.css');
        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/report.css');


        if (file_exists('gglms_custom.css'))
            JHtml::_('stylesheet', 'gglms_custom.css');

//        JHtml::_('script','components/com_gglms/js/mediaelement-and-player.js');


        $this->_params = $this->_japp->getParams();
        $this->_user = JFactory::getUser();


//        // CHECK UTENTE é SULLA PIATTAFORMA GIUSTA
//        $model_user = new gglmsModelUsers();
//        $info_piattaforma = $model_user->get_user_piattaforme($this->_user->id);
//
//        if ($info_piattaforma[0]->dominio !== DOMINIO) {
//
//
//            $uri = JUri::getInstance();
//            $return = $uri->toString();
//            $url = "https://" . $info_piattaforma[0]->dominio . '/home/accedi_registrati/accedi.html';
//
////            var_dump($url);
////            header("Location: http://www.yourwebsite.com/user.php");
////            exit();
//
//            $msg = JText::_('PER ACCEDERE ALLA TUA AREA FORMATIVA LOGGATI SULLA TUA PIATTAFORMA DI RIFERIMENTO <a href="' . $url . ' "> cliccando qui </a>');
//            $url = 'index.php?option=com_users&view=login';
//            $url .= '&return=' . base64_encode($return);
//            $this->_japp->redirect(JRoute::_($url), $msg);
//        }
//
//
//        // FINE CHECK UTENTE PIATTAFORMA

        //todo modifica francesca per rendere accedibile help desk anche da non loggati, ho copiato da catalogo ma non sono sicura sia giusto farlo così....
        if ($this->_user->guest
            && strpos(JUri::getInstance()->toString(), 'catalogo') === false
            && strpos(JUri::getInstance()->toString(), 'helpdesk') === false
            && strpos(JUri::getInstance()->toString(), 'dispenser') === false
            && strpos(JUri::getInstance()->toString(), 'prenota') === false
        ) {
//            $msg = "Per accedere al corso è necessario loggarsi";
            $msg = JText::_('COM_GGLMS_NOT_LOGGED');

            $uri = JUri::getInstance();
            $return = $uri->toString();
            $url = 'index.php?option=com_users&view=login';
            $url .= '&return=' . base64_encode($return);
            $this->_japp->redirect(JRoute::_($url), $msg);
        }


        // CHECK UTENTE é SULLA PIATTAFORMA GIUSTA
        $model_user = new gglmsModelUsers();
        $info_piattaforma = $model_user->get_user_piattaforme($this->_user->id);
        $is_super_admin = $model_user->is_user_superadmin($this->_user->id);
        $is_tutor_piattaforma = $model_user->is_tutor_piattaforma($this->_user->id);


        if (!$is_super_admin && !$is_tutor_piattaforma) {


            if (!empty($info_piattaforma) && $info_piattaforma[0]->dominio !== DOMINIO) {


                $uri = JUri::getInstance();
                $return = $uri->toString();
                $url = "https://" . $info_piattaforma[0]->dominio . '/home/accedi_registrati/accedi.html';

//            var_dump($url);
//            header("Location: http://www.yourwebsite.com/user.php");
//            exit();

                $msg = JText::_('PER ACCEDERE ALLA TUA AREA FORMATIVA LOGGATI SULLA TUA PIATTAFORMA DI RIFERIMENTO <a href="' . $url . ' "> cliccando qui </a>');
                $url = 'index.php?option=com_users&view=login';
                $url .= '&return=' . base64_encode($return);
                $this->_japp->redirect(JRoute::_($url), $msg);
            }
        }

        // FINE CHECK UTENTE PIATTAFORMA

        $this->registerTask('returnfromjoomlaquiz', 'returnfromjoomlaquiz');
        $this->registerTask('attestato', 'attestato');

    }


    public function sync()
    {
        $model = $this->getModel('syncdatareport');
        $model->sync();
    }

    public function returnfromjoomlaquiz()
    {

        $db = &JFactory::getDbo();
        $app = &JFactory::getApplication();

        $getdata = $app->input->get;
        $quiz_id = $getdata->get('quiz_id');


        $query = $db->getQuery(true)
            ->select('u.alias')
            ->from('#__gg_unit u')
            ->innerJoin('#__gg_unit_map m on m.idunita=u.id')
            ->innerJoin('#__gg_contenuti c on c.id=m.idcontenuto')
            ->where("c.path = " . $quiz_id);


        $db->setQuery((string)$query);
        $res = $db->loadResult();

        $msg = "";
        $url = 'index.php?option=com_gglms&view=unita&alias=' . $res;
        $app->redirect(JRoute::_($url), $msg);

        // echo json_encode($query);
        $app->close();
    }



//    public function sync_report(){
//
//        $report = $this->getModel('report');
//        $report->sync();
//        $this->_japp->close();
//    }


}
