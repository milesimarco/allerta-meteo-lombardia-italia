=== Allerta Meteo Lombardia ===
Contributors: Milmor
Version: 2.1
Stable tag: 2.1
Author: Marco Milesi
Author URI: https://profiles.wordpress.org/milmor/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F2JK36SCXKTE2
Tags: allerta, meteo, lombardia, italia, protezione civile
Requires at least: 5.0
Tested up to: 6.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sistema avanzato e completo per la visualizzazione delle allerte ufficiali di Regione Lombardia: Idro-Meteo, Neve, Valanghe e Incendi Boschivi.

== Description ==

> Questo plugin è sviluppato per essere utilizzato in Italia, specificamente per la Regione Lombardia.

**Allerta Meteo Lombardia** integra nel tuo sito WordPress i dati ufficiali di Protezione Civile Regione Lombardia, offrendo una panoramica completa e aggiornata dei rischi ambientali.

Supporta **tutti i rischi monitorati**:

*   ⛈️ **Idro-Meteo:** Idrogeologico, Idraulico, Temporali forti, Vento forte
*   ❄️ **Neve:** Criticità per nevicate in pianura e montagna
*   🏔️ **Valanghe:** Pericolo valanghe nelle zone alpine
*   🔥 **Incendi Boschivi:** Rischio incendi

### Nuove Funzionalità (v2.1)
*   **Shortcode Builder Visivo:** Crea i tuoi shortcode facilmente dal pannello di amministrazione con anteprima in tempo reale.
*   **Supporto Multi-Rischio:** Combina più rischi (es. Meteo + Neve) in un'unica tabella compatta.
*   **Previsioni:** Visualizza l'evoluzione dell'allerta con indicazioni di trend (in peggioramento/miglioramento) e orari di validità.
*   **Dashboard Rinnovata:** Monitora la situazione a colpo d'occhio direttamente dal backend, con modali per copiare rapidamente gli shortcode.
*   **Gestione Zone Semplificata:** Supporto completo per le diverse zonazioni (Zone IM, Zone NV, Zone Incendi, etc.).

L'elenco dei comuni e delle relative zone omogenee è disponibile sui canali ufficiali di Regione Lombardia.

**Risorse utili:**
- [Istruzioni di installazione](https://wordpress.org/plugins/allerta-meteo-lombardia-italia/installation)
- [FAQ e guida all'uso](https://wordpress.org/plugins/allerta-meteo-lombardia-italia/faq)
- Bot Telegram: [@AllertaMeteoLombardia_bot](http://t.me/AllertaMeteoLombardia_bot)

== Installation ==

1. Carica la cartella `allerta-meteo-lombardia-italia` nella directory `/wp-content/plugins/` del tuo sito WordPress.
2. Attiva il plugin dal menu 'Plugin' di WordPress.
3. Vai nel menu "Allerta Meteo" -> "Shortcode Builder" per generare il codice da inserire nelle tue pagine.

== Frequently Asked Questions ==

= Come genero gli shortcode? =
Il modo più semplice è usare lo **Shortcode Builder** incluso nel plugin. Seleziona le zone, i rischi e le opzioni grafiche e copia il codice generato.

= Qual è la sintassi manuale dello shortcode? =
Puoi usare il nuovo formato semplificato.
Esempi:
*   Solo Meteo (Idro-Meteo): `[amli idrometeo="09"]` (dove 09 è il codice zona, es. Nodo Idraulico di Milano)
*   Meteo + Neve: `[amli idrometeo="01" neve="01"]`
*   Tutti i rischi: `[amli idrometeo="03" neve="03" valanghe="15" incendi="03"]`
*   Con previsioni: Aggiungi `previsioni="1"`

= Il vecchio parametro `id="..."` funziona ancora? =
Sì, per retrocompatibilità `id="XX"` viene interpretato come `idrometeo="XX"`. Tuttavia consigliamo di aggiornare i vecchi shortcode.

= Il plugin funziona solo in Lombardia? =
Sì, il plugin è specifico per i dati emessi dalla Protezione Civile della Regione Lombardia.

== Changelog ==

= 2.1 2026-02-02 =
* NEW: Shortcode Builder with Live Preview
* NEW: Support for Snow (Neve), Avalanches (Valanghe), and Forest Fires (Incendi) alerts
* NEW: Unified table visualization for mixed risks
* NEW: Forecast integration with trend indicators
* NEW: Admin Dashboard revamp with quick-copy Modals
* UPDATE: Refactored codebase structure
* FIX: Improved scraper reliability for "Incendi" data

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