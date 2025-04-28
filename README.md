# Odkladiště

Jednoduchý správce souborů napsaný v PHP a Bootstrapu.

## Popis

Tento projekt je webová aplikace pro správu souborů na serveru.  
Umožňuje nahrávání, mazání, organizaci souborů do složek, přidávání poznámek a stažení souborů ve formátu ZIP.

Projekt je postaven bez databáze a používá čisté PHP, HTML5 a CSS (Bootstrap framework).

## Funkce

- Přihlášení uživatele pomocí hesla
- Nahrávání více souborů najednou (Drag & Drop)
- Vytváření, mazání a přejmenování složek
- Přidávání poznámek k souborům
- Stahování souborů i složek jako ZIP archiv
- Jednoduchá správa práv a více uživatelů
- Údržba systému (čištění dočasných souborů, zálohování)

## Požadavky

- Webový server s podporou PHP 7.4+ (např. Apache, Nginx)
- Povoleny PHP rozšíření:
  - `zip`
  - `fileinfo`
  - `mbstring`
  
Doporučeno: HTTPS pro bezpečný přístup.

## Instalace

1. Stažení projektu:
   - Přes Git:
     ```bash
     git clone https://github.com/radopere/Odkladiste.git
     ```
   - Nebo stáhnout jako ZIP a rozbalit.

2. Nahrát rozbalené soubory na server.

3. Otevřít webový prohlížeč a zadat adresu projektu.

4. Přihlásit se pomocí přednastavených údajů (viz `config.php`).

## Konfigurace

Veškeré základní nastavení najdete v souboru `config.php`.
