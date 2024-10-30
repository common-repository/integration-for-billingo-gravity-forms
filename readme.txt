=== Integration for Billingo & Gravity Forms ===
Contributors: passatgt
Tags: billingo, gravity forms, szamlazo, magyar
Requires at least: 5.0
Tested up to: 6.3.2
Stable tag: 1.0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Billingo összeköttetés Gravity Forms-hoz(nem hivatalos bővítmény)

== Description ==

> **PRO verzió**
> A bővítménynek elérhető a PRO verziója 10.000 Ft-ért, amelyet itt vásárolhatsz meg: [https://visztpeter.me/](https://visztpeter.me/)
> A licensz kulcs egy weboldalon aktiválható és 1 év emailes support is jár hozzá beállításhoz, testreszabáshoz, konfiguráláshoz.
> A vásárlással támogathatod a fejlesztést akkor is, ha esetleg a PRO verzióban elérhető funkciókra nincs is szükséged.

= Funkciók =

* **Manuális számlakészítés**
Minden bejegyzésnél a jobb oldalon megjelenik egy új gomb, rákattintáskor elküldi az adatokat Billingo-nak és legenerálja a számlát.
* **Automata számlakészítés**
Lehetőség van a számlát automatikusan elkészíteni bizonyos fizetési módoknál
* **Mennyiségi egység**
A tételek mellett a mennyiségi egységet is feltüntetni a számlát
* **Számlaértesítő**
Az ingyenes verzióban a Billingo küldi ki a számlaértesítőt a vásárlónak. Automata számlakészítéskor az űrlap visszaigazolásába beszúrható a számla linkje.
* **Nemzetközi számla**
Ha külföldre értékesítesz például euróban, lehetőség van a számla nyelv átállítására és az aktuális MNB árfolyam feltüntetésére a számlán.
* **Díjbekérő számla**
Fizetési módtól függően beállítható, hogy díjbekérő készüljön el automatikusan.
* **Teljesítettnek jelölés**
Automatán teljesítettnek jelölheti a számlát a beállított feltételek szerint
* **Naplózás**
Minden számlakészítésnél létrehoz egy megjegyzést a bejegyzéshez, hogy mikor, milyen néven készült el a számla
* **Sztornózás**
A számla sztornózható a bejegyzés oldalon
* **És még sok más**
Papír és elektronikus számla állítás, Áfakulcs állítás, Számlaszám előtag módosítása, figyelmeztetés hibás számlakészítésről stb...

= Használat =
Részletes dokumentációt [itt](https://visztpeter.me/dokumentacio/) találsz.
Telepítés után a Gravity Froms / Beállítások / Billingo oldalon meg kell adni az API kulcsot.
Az űrlap beállításainál a Billingo opció alatt hozz létre egy új Feed-et és állítsd be értelem szerűen. Itt kell például összepárosítanod az űrlapod mezőit a számlán megjelenő adatokkal.
Minden bejegyzésnél jobb oldalon megjelenik egy új doboz, ahol egy gombnyomással létre lehet hozni a számlát. Az Opciók gombbal felül lehet írni a beállításokban megadott értékeket 1-1 számlához.
A bejegyzés létrehozásakor, ha volt sikeres fizetés, automatán létrejöhet a számla.
Az elkészült számla a bejegyzés aloldalán és a bejegyzés listában az utolsó oszlopban található PDF ikonra kattintva letölthető.

**FONTOS:** Mindenen esetben ellenőrizd le, hogy a számlakészítés megfelelő e és konzultálj a könyvelőddel, neki is megfelelnek e a generált számlák. Sajnos minden esetet nem tudok tesztelni, különböző áfakulcsok, termékvariációk, kuponok stb..., így mindenképp teszteld le éles használat előtt és ha valami gond van, jelezd felém és megpróbálom javítani.
**FONTOS 2:** Ez nem egy hivatalos Billingo bővítmény, így ha valami hibát tapasztalsz a működésben, engem piszkálj, ne őket :)

= Fejlesztőknek =

A számlakészítés előtt a számla adatai módosítható a `gf_billingo_invoice` filterrel. Ez minden esetben az éppen aktív téma functions.php fájlban történjen, hogy az esetleges plugin frissítés ne törölje ki a módosításokat!

= GDPR =

A bővítmény HTTP hívásokkal kommunikál a Billingo [API rendszerével](https://billingo.readthedocs.io/). Az API hívások akkor futnak le, ha számla készül(pl rendelés létrehozásánál automatikus számlázás esetén, vagy manuális számlakészítéskor a Számlakészítés gombra nyomva).
A Billingo egy külső szolgáltatás, saját [adatvédelmi nyilatkozattal](https://www.billingo.hu/adatkezelesi-tajekoztato) és [felhasználási feltételekkel](https://www.billingo.hu/felhasznalasi-feltetelek).
This extension relies on making HTTP requests to the Billingo [API](https://billingo.readthedocs.io). API calls are made when an invoice is generated(for example on order creation in case of automatic invoicing, or when you press the create invoice button manually).
Billingo is an external service and has it's own [Terms of Service](https://www.billingo.hu/felhasznalasi-feltetelek) and [Privacy Policy](https://www.billingo.hu/adatkezelesi-tajekoztato), which you can review at those links.

== Installation ==

1. Töltsd le a bővítményt
2. Wordpress-ben bővítmények / új hozzáadása menüben fel kell tölteni
3. Gravity Forms / Beállítások / Billingo menüpontban találhatod a beállítások, itt az API kulcs mezőt kötelező kitölteni
4. Az űrlap beállításainál keresd meg a Billingo opciót és hozz létre egy új Feed-et hozzá. Itt kell megadnod, hogy az űrlapod mezői közül melyek a vásárlód, termékeid adatai.
4. Működik

== Frequently Asked Questions ==

= Mi a különbség a PRO verzió és az ingyenes között? =

Az ingyenes verzió minimális beállítási lehetőséget nyújt, így a teljeskörű használathoz ajánlott a PRO verzió, amiről [itt](https://visztpeter.me/) olvashatsz. Továbbá 1 éves emailes support is jár hozzá.

== Screenshots ==

1. Beállítások képernyő(Gravity Forms / Beállítások)
2. Űrlap Feed beállítások

== Changelog ==

= 1.0.6 =
* Fizetettnek jelölés javítás
* Kompatibilitás megjelölése új WP verzióval

= 1.0.5 =
* Áfakulcs számítás javítás

= 1.0.4 =
* PRO verzió aktiválás/deaktiválás biztonsági javítás

= 1.0.3 =
* PRO verzió hibajavítás
* Bankszámlaszám / számlatömb választó hibajavítás

= 1.0.2 =
* Feed létrehozás hibajavítás

= 1.0.1 =
* Hibajavítások

= 1.0.0 =
* WordPress.org-ra feltöltött plugin első verziója
