<?php

namespace App\Entity\Doc;

use \App\Entity\Entry;
use \App\Helper as H;

/**
 * Класс-сущность  локумент акт  о  выполненных работах
 *
 *
 */
class ServiceAct extends Document {

    public function generateReport() {

        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            $detail[] = array("no" => $i++,
                "servicename" => $value['service_name'],
                "desc" => $value['desc'],
                "price" => H::fa($value['price'])
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->headerdata["customer_name"],
      
            "gar" => $this->headerdata['gar'],
            "isdevice" => strlen($this->headerdata["device"]) > 0,
            "device" => $this->headerdata["device"],
            "devsn" => $this->headerdata["devsn"],
            "document_number" => $this->document_number,
            "payamount" => H::fa($this->payamount),
            "payed" => H::fa($this->payed),
            "total" => H::fa($this->amount)
        );
        $report = new \App\Report('serviceact.tpl');

        $html = $report->generate($header);

        return $html;
    }

    public function Execute() {
        $conn = \ZDB\DB::getConnect();


        foreach ($this->detaildata as $row) {

            $sc = new Entry($this->document_id, 0 - $row['price'], 0);
            $sc->setService($row['service_id']);
            $sc->setExtCode($row['price']); //Для АВС 
            //$sc->setCustomer($this->customer_id);
            $sc->save();
        }
        if ($this->headerdata['payment'] > 0 && $this->payed > 0) {
             \App\Entity\Pay::addPayment($this->document_id, $this->payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_BASE_OUTCOME);
        }


        return true;
    }

    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF, self::EX_POS);
    }

    protected function getNumberTemplate() {
        return 'АКТ-000000';
    }

    public function generatePosReport() {

        $printer = \App\System::getOptions('printer');
        $firm = H::getFirmData(\App\Session::getSession()->branch_id);
        $wp = 'style="width:40mm"';
        if(strlen($printer['pwidth'])>0) {
           $wp = 'style="width:'.$printer['pwidth'].'mm"';
        }
        
        $header = array('printw' => $wp, 'date' => date('d.m.Y', time()),
            "document_number" => $this->document_number,
            "firmname" => $firm['firmname'],
            "address" => $firm['address'],
            "phone" => $firm['phone'],
            "customer_name" => $this->headerdata['customer_name'] ,
            "isdevice" => strlen($this->headerdata["device"]) > 0,
            "device" => $this->headerdata["device"] .(strlen($this->headerdata["devsn"])>0 ? ', с/н ' .$this->headerdata["devsn"]  : '')  ,
            "total" => H::fa($this->amount )
            
            );
            if(strlen($this->headerdata['gar'])>0){
               $header['gar']=  'Гарантия: ' .$this->headerdata['gar'];
            }
            $detail = array();
            foreach ($this->detaildata as $value) {
                $detail[] = array( 
                    "service" => $value['service_name'],
                    "price" => H::fa($value['price'])
                );
            }         
            $header['slist']=   $detail;
           
            $pays = \App\Entity\Pay::getPayments($this->document_id); 
            if(count($pays)>0) {
                $header['plist']  = array();
                foreach($pays as $pay){
                   $header['plist'][] = array('pdate'=>date('d.m.Y', $pay->paydate),'ppay'=>H::fa($pay->amount))     ;
                }
            }  
            $header['ispay']=   count($pays)>0;
            
        $report = new \App\Report('serviceact_bill.tpl');

        $html = $report->generate($header);

        return $html;
    }

}
