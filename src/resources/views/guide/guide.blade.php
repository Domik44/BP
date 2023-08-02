@extends(!Request::ajax() ? 'layouts.app' : 'layouts.fake')

@section('content')

<h1>Nápověda</h1>
<hr>

<section id="basic_info">
    <h2 class="pt-2">Obecné</h2>
    <hr>
    <p>
        <b>GedHelp</b> je aplikace, jejíž smyslem je Vám pomoci se zpracováním GEDCOM souborů. Neznamená to ovšem, že jej vyřeší celý za Vás.
        Aplikace pracuje s daty, které jste již do souboru vyplnili a na základě těchto dat vyhodnocuje co Vám chybí a kde byste na to mohli najít odpovědi.
        Čím více informací poskytnete aplikaci v rámci souboru či odpovědí na dotazy, tím více budou její návhy přesnější. 
        Nikde ovšem není zaručeno, že hledanou odpověd v navržených matrikách naleznete.
    
        <br>
        <br>
        Tato aplikace vznikla v rámci bakalářské práce studenta VUT FIT.
    </p>
</section>
<section id="file_info">
    <h2 class="pt-2">Nahrávání souboru</h2>
    <hr>
    <p>
        Soubor můžete nahrát na hlavní stránce aplikace pomocí tlačítka <span class="green">přidat soubor.</span>
        Zobrazí se Vám okno prostřednictvím, kterého vyberete libovolný GEDCOM soubor. V rámci toho okna můžete také nastavit parametry sloužící 
        k určení časových rozsahů, ve kterých se mohla odehrát daná chybějící událost osobi či rodiny. 
    </p>
    <br>
    <p>
        Jednotlivé parametry znamenají: <br>
    </p>
    <ul>
        <li><b>MIN dítě</b> - minimální věk osoby, od kterého mohla mít děti</li>
        <li><b>MAX dítě (muž)</b> - maximální věk muže, ve kterém mohla mít dítě</li>
        <li><b>MAX dítě (žena)</b> - maximální věk ženy, ve kterém mohla mít dítě</li>
        <li><b>MAX věk</b> - maximální věk, kterého se osoba mohla dožít</li>
        <li><b>MIN svatba</b> - minimální věk osoby, potřebný na vstup do manželství (základně MIN dítě)</li>
        <li><b>MAX svatba</b> - maximální věk, ve kterém mohla osoba vstoupit do manželství (základně MAX věk)</li>
    </ul>
    <br>
    <p>
        Po stisknutí na tlačítko nahrát začne aplikace zpracovávat Váš soubor. Zapíše si všechny osoby a rodiny, vybere chybějící události a zkusí přiřadit 
        zadané území. Jestliže není schopná jednoznačně určit některé území budete vybídnuti k dospecifikování:
        <br><br>
    </p>
    <h4>Dospecifikace území</h4>
    <p>
        Každé území má vlastní kartičku po jejímž kliknutí se Vám zobrazí pole s možností doplnění území, tagy osob a rodin, kterých se území týká.
        Při výběru území si můžete vybrat mezi územími navrženými aplikací pomocí šedé šipečky, nebo si území vybrat sami a to tím, že začnete 
        do bílého pole psát název území a vyberete si jeden z návrhů.
    </p>
    <p>
        Pokud žádné území nevyberete bude pole území označeno <span class="orange">oranžovou barvou</span>. Znamená to, že jste se rozhodli území ignorovat.
        V tomto případě se území nebude používat k dalším výpočtům a <b>nebude prohlášeno za nevalidní informaci</b>.
        Jestliže u území použijete <span class="red">červený křížek</span> <b>prohlásíte tím tuto informaci za nevalidní</b> a ke každé osobě/rodině bude vytvořen záznam
        s chybějícím územím.
        Jestliže chcete území specifikovat u jednotlivé osoby/rodiny zvlášť klikněte na ikonu ozubeného kola a zobrazí se Vám možnost vyplnit 
        území konkrétní osoby/rodiny.
    </p>
    <p>
        Čím více území dospecifikujete, tím více bude aplikace pŕesnější.
    </p>
    <h4>Kontrola území</h4>
    <p>
        Aplikace nejdříve určí úplně jednoznačné území a následně se pokusí na základě lokality, ve které se osoby/rodiny pohybovali, doplnit nejasné
        území. Vzhledem k nárčnosti územného rozsahu ČR se ovšem může stát, že aplikace zvolí špatné místo. Také může nastat případ kdy se skutečně
        může jednat o území vzdálené dál, než stejnojmenné území, které se vyskysovalo blíže k lokalitě pohybu osob/rodin a místo bylo tedy chybně zvoleno.
        Kvůli těmto případům jsou Vám zobrazeny i přiřazené území a máte možnost je tedy zkontrolovat a případně opravit. 
    </p>
    <p>
        Po případném dospecifikování informací začne aplikace navrhovat vhodné matriky ke všem vytvořeným záznamům. Jakmile tento proces skončí
        budete přesměrování na detail konkrétního souboru.
    </p>
</section>
<section id="records_info">
    <h2 class="pt-2">Zobrazení záznamů</h2>
    <hr>
    <p>
        Záznamy vytvořené k osobám se Vám zobrazí pomocí kartiček, které v sobě nesou základní informace o dané osobě/rodině.
        Informace, které Vám chybí jsou zvýrazněni červenou barvou. Kartičky se filtrují podle události, které se týkají.
        Záznam je možné skrýt za pomocí <span class="red">červeného křížku</span>. K záznamu je také možné přidat poznámku za pomocí <span class="blue">modré tužky</span> (viz. Poznámky).
        Zobrazit matriky lze pomocí kliknutí na tlačítko "Zobrazit matriky" 
    
        <br><br>
        Navržené matriky se zobrazují pomocí řazené na základě jejich priority. Jsou rozdělené do 3 základních prioritních skupin.
        Označené jsou pomocí barev od nejvyšší po nejnižší <span class="green">zelená</span>, <span class="orange">oranžová</span>, <span class="red">červená</span>. V rámci prioritní skupiny jsou matriky seřazeny
        ze shora dolů podle největší pravděpodobnosti nálezu. Ke každé matrice je opět možnost napsat poznámku (viz. Poznámky).
    </p>
</section>

<section id="notes_info">
    <h2 class="pt-2">Poznámky</h2>
    <hr>
    <p>
        Aplikace Vám také umožňuje udržovat si poznámky k osobám/rodinám/matrikám. Vytvářet je můžete na stránce zobrazení konkrétního souboru
        za pomocí použití tlačítka s ikonou tužky. Při kliknutí na toto tlačítko se vytvoří poznámka, u které si můžete poznačit nějaké informace
        a text uložit pomocí zeleného tlačítka "Uložit".
    </p>
    <h4>Osoby/Rodiny</h4>
    <p>
        Poznámky pro konkrétní osoby/rodiny Vám kromě obecného textu umožňují k sobě přidat matriku a poznačit si informace k ní.
        Matriku můžete přidat vložením její URL do textového pole v sekci "Přidat matriku". Jestliže byla matrika nalezena odemkne se Vám
        tlačítko "Přidat" a vytvoří se Vám u poznámky podsekce s danou matrikou. Zde si můžete napsat text a poznámku uložit. Jestliže matrika nalezena 
        nebyla, tak Vám vstupní pole zčervená. 
        <br><br>
        * Doporučuji URL matriky kopírovat z adres, které Vám nabízí aplikace v rámci návrhu *
    </p>
    <h4>Matriky</h4>
    <p>
        Podobně jako u poznámky k osobě/rodině si i zde můžete kromě obecného textu přidat doplňující poznámky a to právě konkrétní osoby/rodiny 
        z jednotlivých souborů. V sekci "Přidat osobu/rodinu" si zvolte soubor, ze kterého budete vybírat, dále pak si pak zvolte jestli chcete vybírat
        osobu nebo rodinu. Jako poslední přidejte TAG osoby/rodiny, kterou chcete přidat a jestliže se Vám odemkne tlačítko "Přidat" tak jste zvolili
        existující osobu/rodinu. 
    </p>
</section>
@endsection