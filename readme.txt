[#SEARCHME]
Opis funkcionalnosti moje aplikacije.

Web aplikacija za Pretraživanje teksta u relacijskim bazama podataka i napredni SQL 
[akademska godina 2019./2020.]
[Matea Stanišić, jmbag:1191236619]

/* FUNKCIONALNOST APLIKACIJE */
Moje predloženo riješenje zadanog projekta napisala sam kao web aplikaciju koristeći HTML, CSS, PHP I javascript jezike. 
Kod je strukturiran u "arhitektualni obrazac" MVC (Model-View-Controller).
[U Modelu se povezujemo na bazu i dohvaćamo tražene podatke, u Viewu se nalazi HTML kod, dok je Controller čisti php kod koji povezuje View i Model, odnosno bazu.]

Prilikom otvaranja početne stranice možemo birati što želimo 
	- dodati novi film u bazu (nije dozvoljeno ostaviti prazna polja); 
	- pretraživati filmove (tj. pretraživati bazu imena i opisa filmova i sadrže li one odgovarajuće riječi);
		Autocomplite je podržan kako je traženo (ako se dio napisanog teksta nalazi u sadržaju filma, predlaže se prvih pet takvih filmova)
		Pritiskom na gumb [>>] povezujemo se sa bazom i ispisuje se traženi rezultati:
			* query string s kojim smo dobili rezultat ispod
			* pronađeni filmovi sortirani po ranku, silazno
			* riječi po kojima smo dobili određeni film kao rezultat pretraživanja su podebljane
	- vidjeti (u odabranom vremenskom roku) analizu pretraživanja u aplikaciji;
	(Kao što je traženo, možemo vidjeti analizu po satima ili po danima.)


/* GITHUB */
Povijest pisanja koda, kao i sam kod može se vidjeti i na githubu: 
https://github.com/mateastanisic/searchme 

/* GIF ANIMACIJA FUNCIONALNOSTI */
Kratki pregled funkcionalnosti aplikacije može se vidjeti i u gifu: short_intro.gif



/* GDJE SE MOŽE PRISTUPITI */
Aplikacija trenutno nije postavljena niti na jedan server tako da je, trenutno, pokretanje jedino moguće lokalno uz korištenje localhost servera za povezivanje na postgresql bazu.
(Za testiranje potrebno je izmjeniti liniju 17 u app/boot/db.class.php i u model/db.class.php
	DB::$db = $myPDO = new PDO("pgsql:host=localhost;port=5432;dbname=searchme;user=postgres;password=pass");
sa odgovarjućim vrijednostima - promjena lozinke, ime baze i slično.)



/* PRETPOSTVKE BAZE */
Također, neke pretpostvke na bazu prije pokretanja su:
	- kreirana tablica search_history
	   CREATE TABLE search_history (
                search_input text,
                search_date date,
                search_time time without time zone
            );
	- za autocomplition (korištenje % i funkcije similarity)
		CREATE EXTENSION pg_trgm;
		CREATE INDEX title_index ON movie USING GIST(title gist_trgm_ops);
	- za pivotiranje
		CREATE EXTENSION tablefunc;
	- za pivotiranje, kreirana je tablica hours
		CREATE TABLE hours( hour int);
		INSERT INTO hours VALUES(0);
		INSERT INTO hours VALUES(1);
		INSERT INTO hours VALUES(2);
		INSERT INTO hours VALUES(3);
		INSERT INTO hours VALUES(4);
		INSERT INTO hours VALUES(5);
		INSERT INTO hours VALUES(6);
		INSERT INTO hours VALUES(7);
		INSERT INTO hours VALUES(8);
		INSERT INTO hours VALUES(9);
		INSERT INTO hours VALUES(10);
		INSERT INTO hours VALUES(11);
		INSERT INTO hours VALUES(12);
		INSERT INTO hours VALUES(13);
		INSERT INTO hours VALUES(14);
		INSERT INTO hours VALUES(15);
		INSERT INTO hours VALUES(16);
		INSERT INTO hours VALUES(17);
		INSERT INTO hours VALUES(18);
		INSERT INTO hours VALUES(19);
		INSERT INTO hours VALUES(20);
		INSERT INTO hours VALUES(21);
		INSERT INTO hours VALUES(22);
		INSERT INTO hours VALUES(23);
