delete from  metadata  where  meta_name='CustActivity' ;
delete from  metadata  where  meta_name='EmpAccRep'    ;
delete from  metadata  where  meta_name='SalTypeRep'   ;


  
    
ALTER TABLE `empacc` ADD `createdon` date NULL  ;

DROP VIEW empacc_view  ;

CREATE VIEW empacc_view
AS
SELECT
  `e`.`ea_id` AS `ea_id`,
  `e`.`emp_id` AS `emp_id`,
  `e`.`document_id` AS `document_id`,
  `e`.`optype` AS `optype`,
  `e`.`notes` AS `notes`,
  `e`.`amount` AS `amount`,
  coalesce(`e`.`createdon`,d.document_date ) AS `createdon`,
  `d`.`document_number` AS `document_number`,
  `em`.`emp_name` AS `emp_name`
FROM ((`empacc` `e`
  LEFT JOIN `documents` `d`
    ON ((`d`.`document_id` = `e`.`document_id`)))
  JOIN `employees` `em`
    ON ((`em`.`employee_id` = `e`.`emp_id`)));    
    
    
    
    const  B_OUT_ITEMS = 1; //  ������  ����������
    const  B_IN_PAY = 2; //  ������ ��  ����������
    const  B_IN_ITEMS_RET = 3; //  ������ �� ���������� (�������)
    const  B_OUT_PAY_RET = 4; //  ������ ���������� (�������)
    const  B_OUT_SER = 5; //  ������
  
    const  S_IN_ITEMS = 51; //  ������ ��  ����������
    const  S_OUT_PAY = 52; //  ������ ����������
    const  S_OUT_ITEMS_RET = 53; //  ������ ���������� (�������)
    const  S_IN_PAY_RET = 54; //  ������ ��  ���������� (�������)
    const  S_IN_SER = 55; //  ������    