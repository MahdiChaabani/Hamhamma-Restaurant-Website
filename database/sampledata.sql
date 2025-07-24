SET SERVEROUTPUT ON;
DECLARE
  admin_id1 NUMBER;
  admin_id2 NUMBER;
  admin_id3 NUMBER;
  client_id1 NUMBER;
  client_id2 NUMBER;
  client_id3 NUMBER;
  item_id1 NUMBER;
  item_id2 NUMBER;
  item_id3 NUMBER;
  table_id1 NUMBER;
  table_id2 NUMBER;
BEGIN
  -- Admins
  INSERT INTO USR (USER_ID, FULL_NAME, EMAIL)
  VALUES (SEQ_USR.NEXTVAL, 'Souhail Akermi', 'souhail.admin@hamhamma.tn');
  admin_id1 := SEQ_USR.CURRVAL;
  INSERT INTO ADMIN_TAB (ADMIN_ID, USERNAME, PWD)
  VALUES (admin_id1, 'souhail', '1234');

  INSERT INTO USR (USER_ID, FULL_NAME, EMAIL)
  VALUES (SEQ_USR.NEXTVAL, 'Mahdi Chaabani', 'mahdi.admin@hamhamma.tn');
  admin_id2 := SEQ_USR.CURRVAL;
  INSERT INTO ADMIN_TAB (ADMIN_ID, USERNAME, PWD)
  VALUES (admin_id2, 'mahdi', '0000');

  INSERT INTO USR (USER_ID, FULL_NAME, EMAIL)
  VALUES (SEQ_USR.NEXTVAL, 'Ghaith Homrani', 'ghaith.admin@hamhamma.tn');
  admin_id3 := SEQ_USR.CURRVAL;
  INSERT INTO ADMIN_TAB (ADMIN_ID, USERNAME, PWD)
  VALUES (admin_id3, 'ghaith', '1234');

  -- Clients
  INSERT INTO USR (USER_ID, FULL_NAME, EMAIL)
  VALUES (SEQ_USR.NEXTVAL, 'Youssef Daghfous', 'youssef.daghfous@example.com');
  client_id1 := SEQ_USR.CURRVAL;
  INSERT INTO CLIENT (CLIENT_ID, FULL_NAME, NUM_PHONE, EMAIL)
  VALUES (client_id1, 'Youssef Daghfous', '21698765432', 'youssef.daghfous@example.com');

  INSERT INTO USR (USER_ID, FULL_NAME, EMAIL)
  VALUES (SEQ_USR.NEXTVAL, 'Sarra Mahfoudh', 'sarra.mahfoudh@example.com');
  client_id2 := SEQ_USR.CURRVAL;
  INSERT INTO CLIENT (CLIENT_ID, FULL_NAME, NUM_PHONE, EMAIL)
  VALUES (client_id2, 'Sarra Mahfoudh', '21699887766', 'sarra.mahfoudh@example.com');

  INSERT INTO USR (USER_ID, FULL_NAME, EMAIL)
  VALUES (SEQ_USR.NEXTVAL, 'Omar Jaziri', 'omar.jaziri@example.com');
  client_id3 := SEQ_USR.CURRVAL;
  INSERT INTO CLIENT (CLIENT_ID, FULL_NAME, NUM_PHONE, EMAIL)
  VALUES (client_id3, 'Omar Jaziri', '21691234567', 'omar.jaziri@example.com');

  -- Menu Items
  INSERT INTO MENUITEM (ITEM_ID, NAME_ITEM, DISPONIBLE, DESCRIPTION, IMAGEPATH, PRIX)
  VALUES (SEQ_MENUITEM.NEXTVAL, 'Couscous au Poulet', 1, 'Semoule cuite � la vapeur avec l�gumes et poulet �pic�', '/images/couscous.jpg', 18.5);
  item_id1 := SEQ_MENUITEM.CURRVAL;

  INSERT INTO MENUITEM (ITEM_ID, NAME_ITEM, DISPONIBLE, DESCRIPTION, IMAGEPATH, PRIX)
  VALUES (SEQ_MENUITEM.NEXTVAL, 'Brik � l''�uf', 1, 'Feuille de malsouka farcie avec �uf, thon et c�pres', '/images/brik.jpg', 6.5);
  item_id2 := SEQ_MENUITEM.CURRVAL;

  INSERT INTO MENUITEM (ITEM_ID, NAME_ITEM, DISPONIBLE, DESCRIPTION, IMAGEPATH, PRIX)
  VALUES (SEQ_MENUITEM.NEXTVAL, 'Ojja Merguez', 1, '�ufs poch�s dans une sauce tomate �pic�e avec merguez', '/images/ojja.jpg', 12);
  item_id3 := SEQ_MENUITEM.CURRVAL;

  -- Tables
  INSERT INTO RESTAURANTTABLE (TABLE_ID, NUM_TABLE, SEATS)
  VALUES (SEQ_TABLE.NEXTVAL, 1, 4);
  table_id1 := SEQ_TABLE.CURRVAL;

  INSERT INTO RESTAURANTTABLE (TABLE_ID, NUM_TABLE, SEATS)
  VALUES (SEQ_TABLE.NEXTVAL, 2, 6);
  table_id2 := SEQ_TABLE.CURRVAL;

  -- Reservations
  INSERT INTO RESERVATION (RESERVATION_ID, RESERVATION_DATETIME, NBR_PERSONNES, CHOIX_ITEM, CLIENT_ID, TABLE_ID)
  VALUES (SEQ_RESERVATION.NEXTVAL, TO_TIMESTAMP('2025-05-25 13:00:00', 'YYYY-MM-DD HH24:MI:SS'),
          2, 'Couscous au Poulet, Brik � l''�uf', client_id1, table_id1);

  INSERT INTO RESERVATION (RESERVATION_ID, RESERVATION_DATETIME, NBR_PERSONNES, CHOIX_ITEM, CLIENT_ID, TABLE_ID)
  VALUES (SEQ_RESERVATION.NEXTVAL, TO_TIMESTAMP('2025-05-25 20:00:00', 'YYYY-MM-DD HH24:MI:SS'),
          3, 'Ojja Merguez', client_id2, table_id2);

  -- Feedback
  INSERT INTO CLIENTFEEDBACK (FEEDBACK_ID, ITEM_ID, RATING, COMNT, DATE_INTERACTED)
  VALUES (SEQ_FEEDBACK.NEXTVAL, item_id1, 5, 'Couscous d�licieux !', SYSTIMESTAMP);

  INSERT INTO CLIENTFEEDBACK (FEEDBACK_ID, ITEM_ID, RATING, COMNT, DATE_INTERACTED)
  VALUES (SEQ_FEEDBACK.NEXTVAL, item_id2, 4, 'Brik croustillant et bien garni.', SYSTIMESTAMP);

  DBMS_OUTPUT.PUT_LINE('Sample data inserted successfully.');
END;
/
