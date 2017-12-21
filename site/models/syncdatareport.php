<?php

/**
 * WebTVContenuto Model
 *
 * @package    Joomla.Components
 * @subpackage WebTV
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');
require_once JPATH_COMPONENT . '/models/contenuto.php';
require_once JPATH_COMPONENT . '/models/unita.php';
require_once JPATH_COMPONENT . '/models/coupon.php';
require_once JPATH_COMPONENT . '/models/users.php';


/**
 * WebTVContenuto Model
 *
 * @package    Joomla.Components
 * @subpackage WebTV
 */
class gglmsModelSyncdatareport extends JModelLegacy {
    protected $_db;
    private $_app;
    private $params;

    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->_app = JFactory::getApplication();
        $this->params = $this->_app->getParams();
        $this->_db = JFactory::getDbo();

        JHtml::_('stylesheet', 'components/com_gglms/libraries/css/debugg.css');


    }

    //INGRESSO
    /*    public function  sync(){

            $ora = date('Y-m-d h:i:s', time());
            $lastsync = $this->params->get('data_sync');

            $lastsync = strtotime($lastsync);
            $ora  = strtotime($ora);

            $secondi_ultima_syncro = $ora - $lastsync;

            if($secondi_ultima_syncro > 14000 ) {
                if ($this->sync_report_users()) {

    //            echo "sync_report_users completato <br>";
                    if ($this->sync_report()) {
                        $this->updateconfig();
                    }
                }

                $this->_app->close();
            }

        }

    */

    public function  sync(){
        try {


            $ora = date('Y-m-d h:i:s', time());
            $lastsync = $this->params->get('data_sync');
            $lastsync = strtotime($lastsync);
            $ora = strtotime($ora);
            $secondi_ultima_syncro = $ora - $lastsync;
            $data_sync_seconds_limit=$this->params->get('data_sync_seconds_limit');
            if ($secondi_ultima_syncro >$data_sync_seconds_limit ) {

                if (sync_report_users()) {

                    if (sync_report(0,0)) {
                        updateconfig();
                    }
                }

                $this->_app->close();
            }


        }catch (exceptions $ex){

            DEBUGG::log($ex->getMessage(),'ERRORE DA REPORT.SYNC',1,1);

        }
    }
    public function updateconfig(){

        //DEBUGG::log('inizio updateconfig','inizio updateconfig',0,1);
        try{
            $query = $this->_db->getQuery(true)
                ->update('#__gg_configs	')
                ->set('config_value = now()')
                ->where('config_key = "data_sync"');
            $this->_db->setQuery($query);
            $this->_db->execute();
            utilityHelper::setComponentParam('data_sync', date('Y-m-d G:i:s'));
            return "1";
        }
        catch (Exception $e){
            DEBUGG::log($e, 'updateconfig',1,1);
        }
    }

    //REPORT TRACCIAMENTO
    public function sync_report($limit,$offset){
        //DEBUGG::log('inizio sync_report','inizio sync_report',0,1);
        //ini_set('max_execution_time', 6000);
        try {
            $scormvar_list = $this->_getScormvarsVariation($limit,$offset);
            $quizdeluxe_list = $this->_getQuizDeluxeVariation($limit,$offset);
            $list = array_merge($scormvar_list, $quizdeluxe_list);
            //if($limit==200){$list=null;} //SIMULAZIONE DI FINE
            if(count($list)>0) {
                foreach ($list as $item) {
                    $data = new Stdclass();
                    $data->id_utente = $item->id_utente;
                    $data->id_contenuto = $item->id_contenuto;
                    $modelcontenuto = new gglmsModelContenuto();

                    $contenuto = $modelcontenuto->getContenuto($item->id_contenuto);
                    $stato = $contenuto->getStato($data->id_utente);
                    $data->data = $stato->data;
                    $data->stato = $stato->completato;
                    $data->visualizzazioni = $stato->visualizzazioni;
                    $data->id_unita = $contenuto->getUnitPadre();//se  questo fallisce non lo metto nel report

                    if (!isset($data->id_unita)) continue;
                    $modelunita = new gglmsModelUnita();
                    $unita = $modelunita->getUnita($data->id_unita);

                    $corso = $unita->find_corso($data->id_unita, false);
                    if($corso->pubblicato==0) continue;

                    $data->id_corso = $corso->id;
                    $data->id_event_booking = ($corso->id_event_booking)?$corso->id_event_booking:0;
                    $data->id_anagrafica = $this->_getAnagraficaid($data->id_utente, $data->id_event_booking);

                    // DEBUGG::log($data, 'Data to store_report' );

                    $this->store_report($data);
                    unset($modelunita);
                    unset($unita);
                    unset($data);

                    //var_dump($data);
                    unset($modelcontenuto);
                    unset($contenuto);
                }
                return true;
            }else{
                return false;
            }
        }
        catch (Exception $e) {
            //echo $e->getMessage();
            DEBUGG::log($e->getMessage(), 'error in sync_report' , 0,1);
        }
    }

    public function sync_report_count(){

        try {
            $scormvar_list = $this->_getScormvarsVariation(0,0);
            $quizdeluxe_list = $this->_getQuizDeluxeVariation(0,0);
            $list =($quizdeluxe_list==null)? $scormvar_list: array_merge($scormvar_list, $quizdeluxe_list);
            return count($list);
        }
        catch (Exception $e) {
            //echo $e->getMessage();
            DEBUGG::log($e->getMessage(), 'error in sync_report' , 0,1);
        }

    }

    private function _getScormvarsVariation($limit,$offset){

        try {
            $query = $this->_db->getQuery(true)
                ->select('DISTINCT s.scoid as id_contenuto, s.userid as id_utente')
                ->from('#__gg_scormvars as s');

            if($this->params->get('data_sync')>'1900-01-01')
                $query->where('timestamp > "' . $this->params->get('data_sync').'"');

            $query->setLimit($offset,$limit);

            $this->_db->setQuery($query);
            $data = $this->_db->loadObjectList();
            return $data;
        }
        catch (Exception $e) {
            // echo "_getScormvars ".$e->getMessage();
            DEBUGG::log($e->getMessage(), 'error in getScormVars',1,1);
            DEBUGG::query($query, '_getScormvarsVariation', 1);
        }
    }

    private function _getQuizDeluxeVariation($limit,$offset){

        try {
            $query = $this->_db->getQuery(true)
                ->select('DISTINCT  c.id as id_contenuto, q.c_student_id as id_utente')
                ->from('#__quiz_r_student_quiz as q')
                ->join('inner','#__gg_contenuti as c on q.c_quiz_id = c.id_quizdeluxe');

            if($this->params->get('data_sync'))
                $query->where('c_date_time > "' . $this->params->get('data_sync').'"');

            $query->setLimit($offset,$limit);

            $this->_db->setQuery($query);
            $data = $this->_db->loadObjectList();
            return $data;
        }
        catch (Exception $e) {
            //$data=array(array(id_contenuto=>'',id_utente=>''));
            $data=array();
            //echo "_quizdeluxe ".$e->getMessage();
            //DEBUGG::log($e->getMessage(), 'error in getQuizDeLuxe',0,1);
            //DEBUGG::query($query, '_getScormvarsVariation', 0);
            return $data;
        }
    }

    private function _getAnagraficaid($user_id, $event_id){

        try{
            $query = $this->_db->getQuery(true);
            $query->select('id');
            $query->from('#__gg_report_users as ru');
            $query->where('ru.id_user = '. $user_id);
            if($event_id){$query->where('ru.id_event_booking = '. $event_id);}
            $query->limit('1');
            $this->_db->setQuery($query);
            $res = $this->_db->loadResult();
            return $res ? $res : 0 ;

        }catch (Exception $e){
            DEBUGG::log($e->getMessage(), 'error get Anagrafica',0,1);
        }

    }

    private function store_report($data){

        try {

            $query = "
    INSERT INTO #__gg_report (id_corso, id_event_booking,id_unita, id_contenuto,  id_utente , id_anagrafica, stato, visualizzazioni, data ) 
    VALUES ($data->id_corso, $data->id_event_booking, $data->id_unita,$data->id_contenuto,$data->id_utente,$data->id_anagrafica,$data->stato, $data->visualizzazioni, '$data->data')";
            $query .= "ON DUPLICATE KEY UPDATE stato = $data->stato , visualizzazioni= $data->visualizzazioni, data='$data->data'  ";

            $this->_db->setQuery($query);
            $this->_db->execute();

        }catch (Exception $e){
            echo "storereport ".$e->getMessage();
            DEBUGG::log($e->getMessage(), 'error store report', 0,1);
        }
    }

    //REPORT UTENTI
    public function sync_report_users() {
        //DEBUGG::log('inizio sync_report_users','inizio sync_report_users',0,1);

        try {

            $users = $this->get_users_id($this->params->get('data_sync'));


            foreach ($users as $user) {
                $modelUser = new gglmsModelUsers();

                if (!$user->event_id)
                    $user->event_id = 0;

                $tmpuser = $modelUser->get_user($user->id, $user->event_id);

                $tmp = new stdClass();
                $tmp->id = $user->id;
                $tmp->id_event_booking = $user->event_id;
                $tmp->id_user = $user->id;
                $tmp->nome = $this->_db->quote($tmpuser->nome);
                $tmp->cognome = $this->_db->quote($tmpuser->cognome);
                $tmp->fields = $this->_db->quote(json_encode($tmpuser));
                $this->store_report_users($tmp);

            }

            return true;
        }catch (Exception $e){
            echo $e->getMessage();
            die;
            DEBUGG::log($e->getMessage(), 'error sync_report_users', 1,1);
            return false;
        }
    }

    public function get_users_id($from_date = null )
    {
        switch ($this->params->get('integrazione')) {
            case 'cb':
                $data =  $this->get_users_cb($from_date);
                break;
            case 'eb':
                $data =  $this->get_users_eb($from_date);
                break;
            default:
                $data =  $this->get_users_joomla($from_date);
                break;
        }


        return $data;
    }

    private function get_users_cb($from_date){

        try {
            $query = $this->_db->getQuery(true)
                ->select('u.id, 0 as event_id')
                ->from('#__comprofiler as r')
                ->join('inner', '#__users as u on u.id = r.id');

            if ($from_date) {
                $query->where('u.registerDate > ' . $this->_db->quote($from_date).
                    ' OR '.' r.lastupdatedate > ' . $this->_db->quote($from_date));
            }

            $this->_db->setQuery($query);
            $registrants = $this->_db->loadObjectList();
            return $registrants;
        }catch (Exception $e){

            DEBUGG::log($e->getMessage(), 'error get user cb', 1,1);
        }

    }

    private function get_users_eb($from_date){
        try {
            $query = $this->_db->getQuery(true)
                ->select('distinct user_id as id, event_id')
                ->from('#__eb_registrants as r');

            if($from_date) {
                $query->Where('r.register_date > ' . $this->_db->quote($from_date));
            }

            $query->where('user_id != "" ' );
            $this->_db->setQuery($query);
            $registrants = $this->_db->loadObjectList();
            return $registrants;
        }catch (Exception $e){
            DEBUGG::query($query, 'query_ error_ in_ get_users_eb');
            DEBUGG::log($e->getMessage(), 'error in get user eb', 1,1);
        }
    }

    private function get_users_joomla($from_date){
        try {
            $query = $this->_db->getQuery(true)
                ->select('u.id, 0 as event_id')
                ->from('#__users as u');

            if($from_date) {
                $query->Where('u.registerDate > "' . $from_date .'"');
            }

            $this->_db->setQuery($query);
            $registrants = $this->_db->loadObjectList();
            return $registrants;
        }catch (Exception $e){
            DEBUGG::query($query, 'query error in get_users_joomla');
            DEBUGG::log($e->getMessage(), 'error in get_users_joomla', 1,1);
        }
    }

    private function store_report_users($data){

        try {
            $query = "
            INSERT INTO #__gg_report_users (id_event_booking,id_user, nome, cognome, fields) 
            VALUES ($data->id_event_booking, $data->id_user, $data->nome,$data->cognome,$data->fields)";
            $query .= " ON DUPLICATE KEY UPDATE fields = $data->fields";
            $this->_db->setQuery($query);
            $this->_db->execute();

        }catch (Exception $e){

            DEBUGG::log($e, 'error store users  report', 1,1);
        }
    }


}

