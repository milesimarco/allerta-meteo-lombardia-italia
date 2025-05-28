=== Allerta Meteo Lombardia ===
Contributors: Milmor
Version: 2.0.1
Stable tag: 2.0.1
Author: Marco Milesi
Author URI: https://profiles.wordpress.org/milmor/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F2JK36SCXKTE2
Tags: allerta, meteo, lombardia, italia, weather, alerts
Requires at least: 3.8
Tested up to: 6.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sistema avanzato per la visualizzazione delle allerte meteo ufficiali della Regione Lombardia.

== Description ==

> This plugin is developed and intended to be used in Italy.

Allerta Meteo Lombardia permette di visualizzare in tempo reale lo stato delle allerte meteo emesse da Regione Lombardia, tramite una tabella chiara che mostra i rischi per ogni zona omogenea:

- Idrogeologico
- Idraulico
- Temporali forti
- Vento forte

L'elenco dei comuni e delle relative zone omogenee è disponibile nella [D.g.r. del 17 dicembre 2015](https://goo.gl/ZnShtr).

**Funzionalità principali:**
- Shortcode personalizzabile per mostrare le allerte di una specifica zona
- Dashboard amministrativa per monitorare tutte le allerte
- Nuova codifica zone aggiornata secondo le direttive regionali
- Design moderno e responsive
- Sicurezza migliorata

**Risorse utili:**
- [Istruzioni di installazione](https://wordpress.org/plugins/allerta-meteo-lombardia-italia/installation)
- [FAQ e guida all'uso](https://wordpress.org/plugins/allerta-meteo-lombardia-italia/faq)
- Bot Telegram: [@AllertaMeteoLombardia_bot](http://t.me/AllertaMeteoLombardia_bot)

== Installation ==

1. Carica la cartella `allerta-meteo-lombardia-italia` nella directory `/wp-content/plugins/` del tuo sito WordPress.
2. Attiva il plugin dal menu 'Plugin' di WordPress.
3. Inserisci lo shortcode desiderato nelle tue pagine o articoli.

== Frequently Asked Questions ==

= Quali shortcode sono disponibili? =
Puoi mostrare i dati di allerta meteo con lo shortcode:
``[amli id="XX"]``
dove XX è il numero dell'area omogenea identificata da Regione Lombardia nel [D.g.r. del 17 dicembre 2015](https://goo.gl/ZnShtr) (es: 01, 02, ... 13, 14).

= Dove trovo la lista delle zone e dei comuni? =
Consulta la [D.g.r. del 17 dicembre 2015](https://goo.gl/ZnShtr) per l'elenco aggiornato.

= Il plugin funziona solo in Lombardia? =
Sì, il plugin è specifico per le allerte meteo della Regione Lombardia.

== Changelog ==

= 2.0 2025-05-28 =
* Compatibility check
* Security improvement
* New shortcode design
* New admin dashboard to check all weather alerts
* New zone codes (as per regional new codification)

= 1.5.3 2024-10-22 =
* Compatibility check

= 1.5.2 2022-08-17 =
* Compatibility check
* Under the hood improvements

= 1.5.1 2022-03-12 =
* Compatibility check
* Under the hood improvements

= 1.5 2020-11-15 =
* Support for new AllertaLOM
* Under the hood improvements

= 1.4.1 2020-05-15 =
* Improved custom functions handling
* Minor changes

= 1.4 2020-03-03 =
* Miglioramenti grafici
* Inserito bypass SSL
* Miglioramenti performance

= 1.3.1 2017-03-23 =
* Bugfix a seguito di cambiamenti del portale regionale

= 1.3 2017-03-05 =
* Aggiornato sistema di scraping per il nuovo portale regionale
* Miglioramenti vari e bugfix

= 1.0 2016-07-29 =
* First wordpress.org release
