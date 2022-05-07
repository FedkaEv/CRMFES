<?php

namespace App\Entity;

/**
 * Класс-сущность  контрагент
 *
 * @table=customers
 * @view=customers_view
 * @keyfield=customer_id
 */
class Customer extends \ZCL\DB\Entity
{

    const STATUS_ACTUAL   = 0;  //актуальный
    const STATUS_DISABLED = 1; //не используется
    const STATUS_LEAD     = 2; //лид
   
    const TYPE_BAYER      = 1; //покупатель
    const TYPE_SELLER     = 2; //поставщик

    protected function init() {
        $this->customer_id = 0;
        $this->customer_name = '';
        $this->status = 0;
        $this->fromlead = 0;
        $this->createdon = time();
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->detail = "<detail><code>{$this->code}</code>";
        if ($this->discount > 0) {
            $this->detail .= "<discount>{$this->discount}</discount>";
        }


        $this->detail .= "<type>{$this->type}</type>";
        $this->detail .= "<fromlead>{$this->fromlead}</fromlead>";
        $this->detail .= "<jurid>{$this->jurid}</jurid>";
        $this->detail .= "<shopcust_id>{$this->shopcust_id}</shopcust_id>";
        $this->detail .= "<isholding>{$this->isholding}</isholding>";
        $this->detail .= "<holding>{$this->holding}</holding>";
        $this->detail .= "<viber>{$this->viber}</viber>";
        $this->detail .= "<nosubs>{$this->nosubs}</nosubs>";
        $this->detail .= "<allowedshop>{$this->allowedshop}</allowedshop>";
        $this->detail .= "<edrpou>{$this->edrpou}</edrpou>";

        $this->detail .= "<user_id>{$this->user_id}</user_id>";
        $this->detail .= "<pricetype>{$this->pricetype}</pricetype>";

        $this->detail .= "<holding_name><![CDATA[{$this->holding_name}]]></holding_name>";
        $this->detail .= "<firstname><![CDATA[{$this->firstname}]]></firstname>";
        $this->detail .= "<lastname><![CDATA[{$this->lastname}]]></lastname>";
        $this->detail .= "<address><![CDATA[{$this->address}]]></address>";
        $this->detail .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->detail .= "</detail>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->detail);

        $this->discount = doubleval($xml->discount[0]);

        $this->type = (int)($xml->type[0]);
        $this->jurid = (int)($xml->jurid[0]);
        $this->shopcust_id = (int)($xml->shopcust_id[0]);
        $this->isholding = (int)($xml->isholding[0]);
        $this->user_id = (int)($xml->user_id[0]);
        $this->pricetype = (string)($xml->pricetype[0]);
        $this->fromlead = (int)($xml->fromlead[0]);

        $this->allowedshop = (int)($xml->allowedshop[0]);
        $this->nosubs = (int)($xml->nosubs[0]);
        $this->holding = (int)($xml->holding[0]);
        $this->holding_name = (string)($xml->holding_name[0]);
        $this->address = (string)($xml->address[0]);
        $this->comment = (string)($xml->comment[0]);
        $this->viber = (string)($xml->viber[0]);
        $this->edrpou = (string)($xml->edrpou[0]);
        $this->firstname = (string)($xml->firstname[0]);
        $this->lastname = (string)($xml->lastname[0]);

        $this->createdon = strtotime($this->createdon);

        parent::afterLoad();
    }

    public function beforeDelete() {

        $conn = \ZDB\DB::getConnect();

        $sql = "  select count(*)  from  documents where   customer_id = {$this->customer_id}  ";
        $cnt = $conn->GetOne($sql);
        if ($cnt > 0) {
            return  \App\Helper::l("custisuseddoc");   
        }
        return "";
    }

    protected function afterDelete() {

        $conn = \ZDB\DB::getConnect();

        $conn->Execute("delete from eventlist where   customer_id=" . $this->customer_id);
        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_CUST . " and item_id=" . $this->customer_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_CUST . " and item_id=" . $this->customer_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");
    }

    public static function getByPhone($phone) {
        if (strlen($phone) == 0) {
            return null;
        }
        $conn = \ZDB\DB::getConnect();
        return Customer::getFirst(' phone = ' . $conn->qstr($phone));
    }

    public static function getByEmail($email) {
        if (strlen($email) == 0) {
            return null;
        }
        $conn = \ZDB\DB::getConnect();
        return Customer::getFirst(' email = ' . $conn->qstr($email));
    }

    /**
     * список  контрагентов  кроме  холдингов
     *
     * @param mixed $search
     * @param mixed $type
     * @param mixed $edrpou
     */
    public static function getList($search = '', $type = 0, $searchedrpou = false) {


        $where = "status=0 and detail not like '%<isholding>1</isholding>%' ";
        if (strlen($search) > 0) {
            $edrpou = Customer::qstr('%<edrpou>' . $search . '</edrpou>%');

            $search = Customer::qstr('%' . $search . '%');

            if ($searchedrpou) {
                $where .= " and  (customer_name like {$search}  or phone like {$search}  or email like {$search} or detail like {$edrpou}  ) ";
            } else {
                $where .= " and  (customer_name like {$search}  or phone like {$search}  or email like {$search} ) ";
            }


        }
        if ($type > 0) {
            $where .= " and  (detail like '%<type>{$type}</type>%'  or detail like '%<type>0</type>%' ) ";
        }

        return Customer::findArray("concat(customer_name,' ',phone)", $where, "customer_name");
    }

    public static function getHoldList($type = 0) {

        $conn = \ZDB\DB::getConnect();
        $where = "status=0 and detail like '%<isholding>1</isholding>%' ";
        if ($type > 0) {
            $where .= " and  (detail like '%<type>{$type}</type>%'  or detail like '%<type>0</type>%' ) ";
        }

        return Customer::findArray("customer_name", $where, "customer_name");
    }

    public static function getLeadSources() {
        $options = \App\System::getOptions('common');

        if (is_array($options['leadsources']) == false) {
            $options['leadsources'] = array();
        }

        $list = array();
        foreach ($options['leadsources'] as $item) {
            if (strlen($item->name) == 0) {
                continue;
            }
            $list[$item->name] = $item->name;
        }


        return $list;
    }

    public static function getLeadStatuses() {
        $options = \App\System::getOptions('common');

        if (is_array($options['leadstatuses']) == false) {
            $options['leadstatuses'] = array();
        }

        $list = array();
        foreach ($options['leadstatuses'] as $item) {
            if (strlen($item->name) == 0) {
                continue;
            }
            $list[$item->name] = $item->name;
        }


        return $list;
    }


    public function getBonus() {
        $conn = \ZDB\DB::getConnect();
        $sql = "select coalesce(sum(bonus),0) as bonus from paylist where  document_id in (select  document_id  from  documents where  customer_id={$this->customer_id})";

        return $conn->GetOne($sql);

    }
    
    public function getDolg() {
     $br = "";
        $c = \App\ACL::getBranchConstraint();
        if (strlen($c) > 0) {
            $br = " {$c} and ";
        }    
        $docs      = " and ( meta_name in('GoodsIssue','Invoice' ,'PosCheck','ServiceAct','Order','ReturnIssue')  or  (meta_name='IncomeMoney'  and content like '%<detail>1</detail>%'  )  or  (meta_name='OutcomeMoney'  and content like '%<detail>2</detail>%'  ))  ";
 
        $conn = \ZDB\DB::getConnect();
     $sql = "select  coalesce(sum(sam),0) as sam  from (
        select   (case when  ( meta_name='OutcomeMoney' or meta_name='ReturnIssue' ) then  (payed - payamount )   else  (payamount - payed)  end) as sam 
            from documents_view  
            where {$br}   customer_id={$this->customer_id} and (payamount >0  or  payed >0) {$docs}  and state not in (1,2,3,17,8)   and  ( (meta_name <>'POSCheck' and payamount <> payed) or(meta_name = 'POSCheck' and payamount > payed  ))
            ) t    ";

 
        return $conn->GetOne($sql);

    }
    
    public   function getID() {
        return $this->customer_id;
    }    
}
