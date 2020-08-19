<?php

namespace App\Modules\Tecdoc;

use App\Entity\Item;

use App\Helper as H;
use App\System;
use App\Application as App;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Panel;
use Zippy\Html\Label;
use Zippy\Html\Form\Date;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\DataList\ArrayDataSource;
use \Zippy\Binding\PropertyBinding as Bind;

class Search extends \App\Pages\Base
{
    public $_ds = array();

    public function __construct() {
        parent::__construct();

        if (strpos(System::getUser()->modules, 'tecdoc') === false && System::getUser()->rolename != 'admins') {
            System::setErrorMsg(\App\Helper::l('noaccesstopage'));

            App::RedirectHome();
            return;
        }

        $this->add(new Panel('tpanel'));


        $this->tpanel->add(new ClickLink('tabl', $this, 'onTab'));
        $this->tpanel->add(new ClickLink('tabc', $this, 'onTab'));
        $this->tpanel->add(new ClickLink('tabb', $this, 'onTab'));

        $tablist = $this->tpanel->add(new Panel('tablist'));
        $tabcode = $this->tpanel->add(new Panel('tabcode'));
        $tabbarcode = $this->tpanel->add(new Panel('tabbarcode'));

        $tablist->add(new Form('search1form'));

        $tablist->search1form->add(new DropDownChoice('stype', array('passenger' => 'Легковая', 'commercial' => 'Грузовик', 'motorbike' => 'Мотоцикл',/* 'engine'=>'Двигатель', 'axle'=>'Ось'*/), 'passenger'))->onChange($this, 'onType');
        $tablist->search1form->add(new DropDownChoice('sbrand', array(), 0))->onChange($this, 'onBrand');
        $tablist->search1form->add(new DropDownChoice('smodel', array(), 0))->onChange($this, 'onModel');
        $tablist->search1form->add(new DropDownChoice('smodif', array(), 0))->onChange($this, 'onModif');
        $tablist->search1form->add(new Label('modifdetail'));

        $tablist->add(new \ZCL\BT\Tree("tree"))->onSelectNode($this, "onTree");

        $tabcode->add(new Form('search2form'))->onSubmit($this, 'onSearch1');
        $tabcode->search2form->add(new TextInput('searchcode'));
        $tabcode->search2form->add(new TextInput('searchbrand'));

        $tabbarcode->add(new Form('search3form'))->onSubmit($this, 'onSearch2');
        $tabbarcode->search3form->add(new TextInput('searchbarcode'));


        $this->add(new Panel('tlist'))->setVisible(false);
        $this->tlist->add(new ClickLink('back'))->onClick($this, 'onBack');

        $this->tlist->add(new DataView('itemlist', new ArrayDataSource(new Bind($this, "_ds")), $this, 'listOnRow'));
        $this->tlist->itemlist->setSelectedClass('table-success');
        $this->tlist->itemlist->setPageSize(H::getPG(10));
        $this->tlist->add(new \Zippy\Html\DataList\Paginator('pag', $this->tlist->itemlist));


        $this->add(new Panel('tview'))->setVisible(false);


        $this->onTab($this->tpanel->tabl);
        $this->onType($tablist->search1form->stype);

    }

    public function onTab($sender) {

        $this->_tvars['tablbadge'] = $sender->id == 'tabl' ? "badge badge-primary  badge-pill " : "badge badge-light  badge-pill  ";
        $this->_tvars['tabcbadge'] = $sender->id == 'tabc' ? "badge badge-primary  badge-pill " : "badge badge-light  badge-pill  ";;
        $this->_tvars['tabbbadge'] = $sender->id == 'tabb' ? "badge badge-primary  badge-pill " : "badge badge-light  badge-pill  ";;

        $this->tpanel->tablist->setVisible($sender->id == 'tabl');
        $this->tpanel->tabcode->setVisible($sender->id == 'tabc');
        $this->tpanel->tabbarcode->setVisible($sender->id == 'tabb');

        if ($sender->id == 'tabc') {
            $db = new DBHelper(0);
            foreach ($db->getAllBrands() as $name) {
                $this->_tvars['brandslist'][] = array('bname' => $name);
            }

        }

        $this->tlist->setVisible(false);
        $this->tview->setVisible(false);
    }

    public function onType($sender) {
        $db = new DBHelper($this->tpanel->tablist->search1form->stype->getValue());

        $list = $db->getBrands();
        $this->tpanel->tablist->search1form->sbrand->setOptionList($list);

        $this->tpanel->tablist->search1form->smodel->setOptionList(array());
        $this->tpanel->tablist->search1form->smodif->setOptionList(array());
        $this->tpanel->tablist->search1form->modifdetail->setText('');
        $this->tpanel->tablist->tree->removeNodes();


    }

    public function onBrand($sender) {
        $db = new DBHelper($this->tpanel->tablist->search1form->stype->getValue());

        $list = $db->getModels($this->tpanel->tablist->search1form->sbrand->getValue());
        $this->tpanel->tablist->search1form->smodel->setOptionList($list);

        $this->tpanel->tablist->search1form->smodif->setOptionList(array());
        $this->tpanel->tablist->search1form->modifdetail->setText('');
        $this->tpanel->tablist->tree->removeNodes();

    }

    public function onModel($sender) {
        $db = new DBHelper($this->tpanel->tablist->search1form->stype->getValue());

        $list = $db->getModifs($this->tpanel->tablist->search1form->smodel->getValue());
        $this->tpanel->tablist->search1form->smodif->setOptionList($list);
        $this->tpanel->tablist->search1form->modifdetail->setText('');
        $this->tpanel->tablist->tree->removeNodes();
    }

    public function onModif($sender) {
        $db = new DBHelper($this->tpanel->tablist->search1form->stype->getValue());

        $list = $db->getModifDetail($this->tpanel->tablist->search1form->smodif->getValue());
        $t = "<table  style='font-size:smaller;'>";
        foreach ($list as $k => $v) {

            if ($k == 'ConstructionInterval') {
                $t = $t . "<tr><td>Годы выпуска</td><td>{$v}</td></tr>";
            }
            if ($k == 'BodyType') {
                $t = $t . "<tr><td>Кузов</td><td>{$v}</td></tr>";
            }
            if ($k == 'DriveType') {
                $t = $t . "<tr><td>Привод</td><td>{$v}</td></tr>";
            }
            if ($k == 'EngineCode') {
                $t = $t . "<tr><td>Код двигателя</td><td>{$v}</td></tr>";
            }
            if ($k == 'EngineType') {
                $t = $t . "<tr><td>Двигатель</td><td>{$v}</td></tr>";
            }
            if ($k == 'NumberOfCylinders') {
                $t = $t . "<tr><td>Цилиндров</td><td>{$v}</td></tr>";
            }
            if ($k == 'Capacity') {
                $t = $t . "<tr><td>Обьем</td><td>{$v}</td></tr>";
            }
            if ($k == 'Power') {
                $t = $t . "<tr><td>Мощность</td><td>{$v}</td></tr>";
            }
            if ($k == 'BrakeSystem') {
                $t = $t . "<tr><td>Тормоз</td><td>{$v}</td></tr>";
            }
            if ($k == 'FuelType') {
                $t = $t . "<tr><td>Топливо</td><td>{$v}</td></tr>";
            }
            if ($k == 'PlatformType') {
                $t = $t . "<tr><td>Тип</td><td>{$v}</td></tr>";
            }
            if ($k == 'Tonnage') {
                $t = $t . "<tr><td>Тоннаж</td><td>{$v}</td></tr>";
            }
            //$t = $t ."<tr><td>{$k}</td><td>{$v}</td></tr>" ;
        }
        $t .= "</table>";
        $this->tpanel->tablist->search1form->modifdetail->setText($t, true);


        $tlist = array();
        $this->tpanel->tablist->tree->removeNodes();
        $this->tpanel->tablist->tree->selectedNodeId(-1);

        $root = new \ZCL\BT\TreeNode('//', 0);
        $tlist[0] = $root;
        $this->tpanel->tablist->tree->addNode($root);

        $list = $db->getTree($this->tpanel->tablist->search1form->smodif->getValue());

        while (true) {
            $wasadded = false;
            foreach ($list as $n) {
                if ($n->intree) {
                    continue;
                }

                if (array_key_exists($n->parentId, $list) == false) {
                    //если  вообще  нет парента
                    $node = new \ZCL\BT\TreeNode($n->description, $n->id);
                    $this->tpanel->tablist->tree->addNode($node, $root);
                    $n->intree = true;
                    $wasadded = true;
                    $tlist[$n->id] = $node;
                    continue;
                }
                if (array_key_exists($n->parentId, $tlist) == true) {
                    //если  парент  вставлен
                    $node = new \ZCL\BT\TreeNode($n->description, $n->id);
                    $this->tpanel->tablist->tree->addNode($node, $tlist[$n->parentId]);
                    $n->intree = true;
                    $wasadded = true;
                    $tlist[$n->id] = $node;
                    continue;
                }


            }
            if ($wasadded == false) {
                break;
            }
        }

    }

    public function onTree($sender, $id) {

        foreach ($sender->nodes as $n) {
            if ($n->zippyid == $id) {
                if (count($n->children) > 0) {
                    return; //если  есть дочерние не  выбираем
                };
            }
        }

        $db = new DBHelper($this->tpanel->tablist->search1form->stype->getValue());

        $this->_ds = $db->searchByCategory($id, $this->tpanel->tablist->search1form->smodif->getValue());

        $this->tlist->itemlist->Reload();

        if (count($this->_ds) > 0) {

            $this->tpanel->setVisible(false);
            $this->tlist->setVisible(true);

        } else {
            $this->setWarn('Ничего не  найдено');
        }
        $this->tview->setVisible(false);
    }

    public function onSearch1($sender) {

        $code = trim($sender->searchcode->getText());
        $brand = trim($sender->searchbrand->getText());

        $db = new DBHelper(0);

        $this->_ds = $db->searchByBrandAndCode($code, $brand);

        $this->tlist->itemlist->Reload();

        if (count($this->_ds) > 0) {
            $this->tpanel->setVisible(false);

            $this->tlist->setVisible(true);

        } else {
            $this->setWarn('Ничего не  найдено');
        }
        $this->tview->setVisible(false);

    }

    public function onSearch2($sender) {

        $code = trim($sender->searchbarcode->getText());


        $db = new DBHelper(0);

        $this->_ds = $db->searchByBarCode($code);

        $this->tlist->itemlist->Reload();

        if (count($this->_ds) > 0) {
            $this->tpanel->setVisible(false);
            $this->tlist->setVisible(true);

        } else {
            $this->setWarn('Ничего не  найдено');
        }
        $this->add(new Panel('tview'))->setVisible(false);

    }

    public function onBack($sender) {
        $this->tpanel->setVisible(true);
        $this->tlist->setVisible(false);
        $this->tview->setVisible(false);
    }

    public function listOnRow($row) {
        $item = $row->getDataItem();
        $row->add(new Label("lbrand", $item->supplier_name));
        $row->add(new Label("lcode", $item->part_number));
        $row->add(new Label("lname", $item->product_name));
        $item = Item::getFirst("manufacturer=" . Item::qstr($item->supplier_name) . " and item_code=" . Item::qstr($item->part_number));

        $row->add(new Label("qty"))->setVisible($item instanceof Item);
        $row->add(new Label("price"))->setVisible($item instanceof Item);
        if ($item instanceof Item) {
            $modules = System::getOptions("modules");
            $row->qty->setText(H::fqty($item->getQuantity($modules['td_store'])));
            $row->price->setText(H::fa($item->getPrice($modules['td_pricetype'], $modules['td_store'])));
        }
        $row->add(new ClickLink('show'))->onClick($this, 'showOnClick');
    }

    public function showOnClick($sender) {
        $modules = System::getOptions("modules");


        $this->tview->setVisible(true);
        $this->tlist->itemlist->setSelectedRow($sender->getOwner());
        $this->tlist->itemlist->Reload(false);
        $part = $sender->getOwner()->getDataItem();

        $db = new DBHelper(0);

        $list = $db->getAttributes($part->part_number, $part->brand_id);


        $this->_tvars['isattr'] = count($list) > 0;
        $this->_tvars['attr'] = array();
        foreach ($list as $k => $v) {
            $this->_tvars['attr'][] = array('k' => $k, 'v' => $v);
        }

        $this->_tvars['isimage'] = false;


        $image = $db->getImage($part->part_number, $part->brand_id);
        if (strlen($image['PictureName']) > 0) {
            $this->_tvars['isimage'] = true;
            $path = rtrim($modules['td_ipath'], '/');
            $path = $path . '/' . $part->brand_id . '/' . $image['PictureName'];
            //  $path = $path.'/'.$image['PictureName'];
            $this->_tvars['imagepath'] = $path;
            $this->_tvars['imagedesc'] = $image['Description'];
        }

        //Оригинальные  номера
        $this->_tvars['isoem'] = false;
        $this->_tvars['oem'] = array();
        $oem = $db->getOemNumbers($part->part_number, $this->tpanel->tablist->search1form->sbrand->getValue());
        if (count($oem) > 0) {
            $this->_tvars['isoem'] = true;
            foreach ($oem as $o) {
                $this->_tvars['oem'][] = array('oemnum' => $o);
            }
        }
        //Замены
        $this->_tvars['isrep'] = false;
        $this->_tvars['rep'] = array();
        $rp = $db->getReplace($part->part_number, $part->brand_id);
        if (count($rp) > 0) {
            $this->_tvars['isrep'] = true;
            foreach ($rp as $r) {

                $item = Item::getFirst("manufacturer=" . Item::qstr($r->supplier) . " and item_code=" . Item::qstr($r->replacenbr));

                if ($item instanceof Item) {
                    $modules = System::getOptions("modules");
                    $q = H::fqty($item->getQuantity($modules['td_store']));
                    $p = H::fa($item->getPrice($modules['td_pricetype'], $modules['td_store']));
                    $q = "<span  class=\"badge badge-info badge-pill\">{$q}</span>";
                    $p = "<span  class=\"badge badge-info badge-pill\">{$p}</span>";

                }


                $this->_tvars['rep'][] = array('sup' => $r->supplier, 'num' => $r->replacenbr, 'q' => $q, 'p' => $p);
            }
        }


        //Составные части
        $this->_tvars['ispart'] = false;
        $this->_tvars['part'] = array();
        $rp = $db->getArtParts($part->part_number, $part->brand_id);
        if (count($rp) > 0) {
            $this->_tvars['ispart'] = true;
            foreach ($rp as $r) {
                $this->_tvars['part'][] = array('Brand' => $r->Brand, 'partnumber' => $r->partnumber, 'Quantity' => $r->Quantity);
            }
        }


        //Аналоги
        $this->_tvars['crosslist'] = array();
        $this->_tvars['iscross'] = false;
        $this->_tvars['cross'] = array();
        $cr = $db->getArtCross($part->part_number, $this->tpanel->tablist->search1form->sbrand->getValue());
        if (count($cr) > 0) {
            $this->_tvars['iscross'] = true;
            foreach ($cr as $c) {
                $item = Item::getFirst("manufacturer=" . Item::qstr($c->description) . " and item_code=" . Item::qstr($c->cross));

                if ($item instanceof Item) {
                    $modules = System::getOptions("modules");
                    $q = H::fqty($item->getQuantity($modules['td_store']));
                    $p = H::fa($item->getPrice($modules['td_pricetype'], $modules['td_store']));
                    $q = "<span  class=\"badge badge-info badge-pill\">{$q}</span>";
                    $p = "<span  class=\"badge badge-info badge-pill\">{$p}</span>";

                }


                $this->_tvars['crosslist'][] = array('desc' => $c->description, 'cross' => $c->cross, 'q' => $q, 'p' => $p);
            }
        }

        //Применимость
        $this->_tvars['isapp'] = false;
        $this->_tvars['applist'] = array();
        $rp = $db->getArtVehicles($part->part_number, $part->brand_id);
        if (count($rp) > 0) {
            $this->_tvars['isapp'] = true;
            foreach ($rp as $r) {
                $this->_tvars['applist'][] = array('years' => $r->years, 'desc' => $r->desc);
            }
        }


    }
}