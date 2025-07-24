-- ========================================================
-- Restaurant Management Database Script - Oracle 11g PL/SQL
-- ========================================================
SET SERVEROUTPUT ON;
DECLARE
    table_not_exists EXCEPTION;
    sequence_not_exists EXCEPTION;
    PRAGMA EXCEPTION_INIT(table_not_exists, -00942);
    PRAGMA EXCEPTION_INIT(sequence_not_exists, -02289);
BEGIN
    -- ========================================================
    -- DROP EXISTING TABLES AND SEQUENCES (RESET DATABASE)
    -- ========================================================
    
    DBMS_OUTPUT.PUT_LINE('Starting database reset...');
    
    -- Drop tables in reverse order of dependencies
    BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE RESERVATION CASCADE CONSTRAINTS';
        DBMS_OUTPUT.PUT_LINE('Dropped table: RESERVATION');
    EXCEPTION
        WHEN table_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Table RESERVATION does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE CLIENTFEEDBACK CASCADE CONSTRAINTS';
        DBMS_OUTPUT.PUT_LINE('Dropped table: CLIENTFEEDBACK');
    EXCEPTION
        WHEN table_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Table CLIENTFEEDBACK does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE ADMIN_TAB CASCADE CONSTRAINTS';
        DBMS_OUTPUT.PUT_LINE('Dropped table: ADMIN_TAB');
    EXCEPTION
        WHEN table_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Table ADMIN_TAB does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE CLIENT CASCADE CONSTRAINTS';
        DBMS_OUTPUT.PUT_LINE('Dropped table: CLIENT');
    EXCEPTION
        WHEN table_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Table CLIENT does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE MENUITEM CASCADE CONSTRAINTS';
        DBMS_OUTPUT.PUT_LINE('Dropped table: MENUITEM');
    EXCEPTION
        WHEN table_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Table MENUITEM does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE RESTAURANTTABLE CASCADE CONSTRAINTS';
        DBMS_OUTPUT.PUT_LINE('Dropped table: RESTAURANTTABLE');
    EXCEPTION
        WHEN table_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Table RESTAURANTTABLE does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USR CASCADE CONSTRAINTS';
        DBMS_OUTPUT.PUT_LINE('Dropped table: USR');
    EXCEPTION
        WHEN table_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Table USR does not exist - skipping');
    END;
    
    -- Drop sequences
    BEGIN
        EXECUTE IMMEDIATE 'DROP SEQUENCE SEQ_RESERVATION';
        DBMS_OUTPUT.PUT_LINE('Dropped sequence: SEQ_RESERVATION');
    EXCEPTION
        WHEN sequence_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Sequence SEQ_RESERVATION does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP SEQUENCE SEQ_CLIENTFEEDBACK';
        DBMS_OUTPUT.PUT_LINE('Dropped sequence: SEQ_CLIENTFEEDBACK');
    EXCEPTION
        WHEN sequence_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Sequence SEQ_CLIENTFEEDBACK does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP SEQUENCE SEQ_RESTAURANTTABLE';
        DBMS_OUTPUT.PUT_LINE('Dropped sequence: SEQ_RESTAURANTTABLE');
    EXCEPTION
        WHEN sequence_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Sequence SEQ_RESTAURANTTABLE does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP SEQUENCE SEQ_MENUITEM';
        DBMS_OUTPUT.PUT_LINE('Dropped sequence: SEQ_MENUITEM');
    EXCEPTION
        WHEN sequence_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Sequence SEQ_MENUITEM does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP SEQUENCE SEQ_CLIENT';
        DBMS_OUTPUT.PUT_LINE('Dropped sequence: SEQ_CLIENT');
    EXCEPTION
        WHEN sequence_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Sequence SEQ_CLIENT does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP SEQUENCE SEQ_ADMIN_TAB';
        DBMS_OUTPUT.PUT_LINE('Dropped sequence: SEQ_ADMIN_TAB');
    EXCEPTION
        WHEN sequence_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Sequence SEQ_ADMIN_TAB does not exist - skipping');
    END;
    
    BEGIN
        EXECUTE IMMEDIATE 'DROP SEQUENCE SEQ_USR';
        DBMS_OUTPUT.PUT_LINE('Dropped sequence: SEQ_USR');
    EXCEPTION
        WHEN sequence_not_exists THEN
            DBMS_OUTPUT.PUT_LINE('Sequence SEQ_USR does not exist - skipping');
    END;
    
    DBMS_OUTPUT.PUT_LINE('Database reset completed successfully!');
    DBMS_OUTPUT.PUT_LINE('----------------------------------------');
    
    -- ========================================================
    -- TABLE CREATION
    -- ========================================================
    
    DBMS_OUTPUT.PUT_LINE('Creating tables...');
    
    -- Create USR table (Base user table)
    EXECUTE IMMEDIATE '
        CREATE TABLE USR (
            USER_ID NUMBER(*,0) NOT NULL,
            FULL_NAME VARCHAR2(255 BYTE) NOT NULL,
            EMAIL VARCHAR2(255 BYTE),
            TIME_CREATED DATE DEFAULT SYSDATE NOT NULL,
            CONSTRAINT PK_USR PRIMARY KEY (USER_ID)
        )';
    DBMS_OUTPUT.PUT_LINE('Created table: USR');
    
    -- Create ADMIN_TAB table
    EXECUTE IMMEDIATE '
        CREATE TABLE ADMIN_TAB (
            ADMIN_ID NUMBER(*,0) NOT NULL,
            USERNAME VARCHAR2(100 BYTE) NOT NULL,
            PWD VARCHAR2(100 BYTE) NOT NULL,
            TIME_CREATED TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT PK_ADMIN_TAB PRIMARY KEY (ADMIN_ID),
            CONSTRAINT FK_ADMIN_USR FOREIGN KEY (ADMIN_ID) REFERENCES USR(USER_ID)
        )';
    DBMS_OUTPUT.PUT_LINE('Created table: ADMIN_TAB');
    
    -- Create CLIENT table
    EXECUTE IMMEDIATE '
        CREATE TABLE CLIENT (
            CLIENT_ID NUMBER(*,0) NOT NULL,
            FULL_NAME VARCHAR2(100 BYTE) NOT NULL,
            NUM_PHONE VARCHAR2(20 BYTE) NOT NULL,
            EMAIL VARCHAR2(100 BYTE) NOT NULL,
            TIME_CREATED TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT PK_CLIENT PRIMARY KEY (CLIENT_ID),
            CONSTRAINT FK_CLIENT_USR FOREIGN KEY (CLIENT_ID) REFERENCES USR(USER_ID)
        )';
    DBMS_OUTPUT.PUT_LINE('Created table: CLIENT');
        
    -- Create MENUITEM table
    EXECUTE IMMEDIATE '
        CREATE TABLE MENUITEM (
            ITEM_ID NUMBER NOT NULL,
            NAME_ITEM VARCHAR2(100 BYTE) NOT NULL,
            DISPONIBLE NUMBER(1,0) DEFAULT 1,
            DESCRIPTION VARCHAR2(1000 BYTE),
            IMAGEPATH VARCHAR2(1000 BYTE),
            PRIX NUMBER(10,2) NOT NULL,
            CONSTRAINT PK_MENUITEM PRIMARY KEY (ITEM_ID),
            CONSTRAINT CHK_DISPONIBLE CHECK (DISPONIBLE IN (0, 1))
        )';
    DBMS_OUTPUT.PUT_LINE('Created table: MENUITEM');
    
    -- Create RESTAURANTTABLE table
    EXECUTE IMMEDIATE '
        CREATE TABLE RESTAURANTTABLE (
            TABLE_ID NUMBER NOT NULL,
            NUM_TABLE NUMBER NOT NULL,
            SEATS NUMBER NOT NULL,
            CONSTRAINT PK_RESTAURANTTABLE PRIMARY KEY (TABLE_ID),
            CONSTRAINT CHK_SEATS CHECK (SEATS BETWEEN 1 AND 10)
        )';
    DBMS_OUTPUT.PUT_LINE('Created table: RESTAURANTTABLE');
    
    -- Create CLIENTFEEDBACK table
    EXECUTE IMMEDIATE '
        CREATE TABLE CLIENTFEEDBACK (
            FEEDBACK_ID NUMBER(*,0) NOT NULL,
            ITEM_ID NUMBER(*,0) NOT NULL,
            RATING NUMBER(*,0),
            COMNT VARCHAR2(1000 BYTE),
            DATE_INTERACTED TIMESTAMP(6),
            CONSTRAINT PK_CLIENTFEEDBACK PRIMARY KEY (FEEDBACK_ID),
            CONSTRAINT FK_CLIENTFEED_ITEM FOREIGN KEY (ITEM_ID) REFERENCES MENUITEM(ITEM_ID),
            CONSTRAINT CHK_RATING CHECK (RATING BETWEEN 1 AND 5)
        )';
    DBMS_OUTPUT.PUT_LINE('Created table: CLIENTFEEDBACK');
    
    -- Create RESERVATION table
    EXECUTE IMMEDIATE '
        CREATE TABLE RESERVATION (
            RESERVATION_ID NUMBER NOT NULL,
            RESERVATION_DATETIME TIMESTAMP(6) NOT NULL,
            NBR_PERSONNES NUMBER NOT NULL,
            CHOIX_ITEM VARCHAR2(255 BYTE),
            TIME_CREATED TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP,
            CLIENT_ID NUMBER(*,0),
            TABLE_ID NUMBER(*,0),
            CONSTRAINT PK_RESERVATION PRIMARY KEY (RESERVATION_ID),
            CONSTRAINT FK_RESERVATION_CLIENT FOREIGN KEY (CLIENT_ID) REFERENCES CLIENT(CLIENT_ID),
            CONSTRAINT FK_RESERVATION_TABLE FOREIGN KEY (TABLE_ID) REFERENCES RESTAURANTTABLE(TABLE_ID)
        )';
    DBMS_OUTPUT.PUT_LINE('Created table: RESERVATION');
    
    -- ========================================================
    -- SEQUENCES CREATION
    -- ========================================================
    
    DBMS_OUTPUT.PUT_LINE('Creating sequences...');
    
    EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_USR START WITH 1 INCREMENT BY 1';
    DBMS_OUTPUT.PUT_LINE('Created sequence: SEQ_USR');
    
    EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_ADMIN_TAB START WITH 1 INCREMENT BY 1';
    DBMS_OUTPUT.PUT_LINE('Created sequence: SEQ_ADMIN_TAB');
    
    EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_CLIENT START WITH 1 INCREMENT BY 1';
    DBMS_OUTPUT.PUT_LINE('Created sequence: SEQ_CLIENT');
    
    EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_MENUITEM START WITH 1 INCREMENT BY 1';
    DBMS_OUTPUT.PUT_LINE('Created sequence: SEQ_MENUITEM');
    
    EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_RESTAURANTTABLE START WITH 1 INCREMENT BY 1';
    DBMS_OUTPUT.PUT_LINE('Created sequence: SEQ_RESTAURANTTABLE');
    
    EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_CLIENTFEEDBACK START WITH 1 INCREMENT BY 1';
    DBMS_OUTPUT.PUT_LINE('Created sequence: SEQ_CLIENTFEEDBACK');
    
    EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_RESERVATION START WITH 1 INCREMENT BY 1';
    DBMS_OUTPUT.PUT_LINE('Created sequence: SEQ_RESERVATION');
    
    DBMS_OUTPUT.PUT_LINE('----------------------------------------');
    DBMS_OUTPUT.PUT_LINE('Restaurant database created successfully!');
    DBMS_OUTPUT.PUT_LINE('All tables and sequences are ready for use.');
    
EXCEPTION
    WHEN OTHERS THEN
        DBMS_OUTPUT.PUT_LINE('Error occurred: ' || SQLERRM);
        RAISE;
END;
/