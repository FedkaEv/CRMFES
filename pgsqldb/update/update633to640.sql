
INSERT INTO metadata ( meta_type, description, meta_name, menugroup, disabled) VALUES( 2, '��� �����', 'CompareAct', '�����������', 0);
INSERT INTO metadata ( meta_type, description, meta_name, menugroup, disabled) VALUES( 2, '������������ ������', 'Reserved', '�����', 0);


    
    
delete  from  options where  optname='version' ;
insert  into options (optname,optvalue) values('version','6.4.0');
     