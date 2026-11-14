<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\SavedList;
use App\Entity\User;
use App\Form\RestaurantListsType;
use App\Form\RestaurantNoteType;
use App\Form\SavedListNameType;
use App\Saved\SavedText;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Validator\Validation;

/**
 * Feature 17, T10 — die drei Formulartypen für Listennamen, Häkchen „In Listen“ und Notiz.
 *
 * Geprüft wird mit dem echten Validator, nicht dem Mock aus `ValidatorExtensionTrait` (der
 * meldet nie einen Verstoß). Fehler werden als **Meldungsschlüssel** verglichen
 * (`getMessageTemplate()`): Im Prüflauf läuft kein Übersetzer, und der Schlüssel ist, was im
 * `validators`-Katalog stehen muss.
 *
 * ⚠ **Jeder Fehler wird am Feld geprüft, nicht irgendwo im Formular** (AK-50). Ein Fehler, der
 * zur Wurzel hochblubbert, stünde nicht beim Feld; der Screenreader läse ihn dort nicht vor.
 */
final class SavedFormTypesTest extends TypeTestCase
{
    protected function setUp(): void
    {
        // Echter Dispatcher statt des Mocks, den TypeTestCase sonst anlegt: PHPUnit 12 meldet
        // einen Mock ohne Erwartung, und `failOnNotice` färbte damit jeden Fall rot.
        $this->dispatcher = new EventDispatcher();

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }

    // ── SavedListNameType ───────────────────────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string}>
     */
    public static function leereNamen(): iterable
    {
        yield 'leer' => [''];
        yield 'nur Leerzeichen' => ['   '];
        yield 'Tabulator und Umbruch' => ["\t \r\n "];
    }

    #[DataProvider('leereNamen')]
    public function testAk17LeererNameMeldetPflichtAmFeld(string $eingabe): void
    {
        $form = $this->factory->create(SavedListNameType::class);
        $form->submit(['name' => $eingabe]);

        self::assertFalse($form->isValid());
        self::assertSame(['saved.name_blank'], self::fehlerAm($form->get('name')));
        self::assertSame([], self::fehlerAm($form), 'AK-50: Die Meldung gehört ans Feld, nicht an die Wurzel.');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function fehlendeNamen(): iterable
    {
        yield 'Feld fehlt' => [[]];
        yield 'Feld null' => [['name' => null]];
    }

    /**
     * Ohne `'empty_data' => ''` käme hier `null` an — der Dienst verlangt `string`, und aus der
     * Meldung würde ein Serverfehler (BF-27).
     *
     * @param array<string, mixed> $absenden
     */
    #[DataProvider('fehlendeNamen')]
    public function testAk17FehlendesFeldWirdLeererTextNichtNull(array $absenden): void
    {
        $form = $this->factory->create(SavedListNameType::class);
        $form->submit($absenden);

        self::assertSame('', $form->getData()['name'] ?? 'null statt leerem Text');
        self::assertSame(['saved.name_blank'], self::fehlerAm($form->get('name')));
    }

    public function testAk17NameAlsListeIstFormularfehlerKeineAusnahme(): void
    {
        $form = $this->factory->create(SavedListNameType::class);
        $form->submit(['name' => ['Kirchberg']]);

        self::assertFalse($form->isValid());
        self::assertFalse($form->get('name')->isSynchronized());
    }

    public function testAk18EinundsechzigZeichenMeldenZuLangUndDieEingabeBleibt(): void
    {
        $eingabe = str_repeat('a', 61);

        $form = $this->factory->create(SavedListNameType::class);
        $form->submit(['name' => $eingabe]);

        self::assertSame(['saved.name_too_long'], self::fehlerAm($form->get('name')));
        self::assertSame([], self::fehlerAm($form));
        self::assertSame($eingabe, $form->createView()['name']->vars['value'], 'AK-18: Der Name steht weiter im Feld.');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function gueltigeLangeNamen(): iterable
    {
        yield '60 Buchstaben' => [str_repeat('a', 60)];
        yield '60 Umlaute (120 Byte)' => [str_repeat('ä', 60)];
        yield '60 Schriftzeichen' => [str_repeat('日', 60)];
        yield '60 Emoji' => [str_repeat('🍝', 60)];
        // NFC vor dem Zählen: zerlegt sind es 120 Codepunkte, gespeichert werden 60.
        yield '60 é aus je zwei Codepunkten' => [str_repeat("e\u{0301}", 60)];
        yield '58 Zeichen und 3 Leerzeichen am Rand' => ['  '.str_repeat('a', 58).' '];
    }

    #[DataProvider('gueltigeLangeNamen')]
    public function testAk18SechzigZeichenNachNormalisierungSindGueltig(string $eingabe): void
    {
        $form = $this->factory->create(SavedListNameType::class);
        $form->submit(['name' => $eingabe]);

        self::assertSame([], self::fehlerAm($form->get('name')));
        self::assertTrue($form->isValid());
    }

    public function testAk17NameIstPflichtfeldMitBeschriftungUndHinweis(): void
    {
        $feld = $this->factory->create(SavedListNameType::class)->createView()['name'];

        self::assertTrue($feld->vars['required']);
        self::assertSame('saved.lists.new_label', $feld->vars['label']);
        self::assertSame('saved.lists.new_help', $feld->vars['help']);
    }

    public function testAk28BeschriftungFuersUmbenennenLaesstSichSetzen(): void
    {
        $feld = $this->factory
            ->create(SavedListNameType::class, null, ['name_label' => 'saved.list.rename_label'])
            ->createView()['name'];

        self::assertSame('saved.list.rename_label', $feld->vars['label']);
    }

    // ── RestaurantListsType ─────────────────────────────────────────────────────────────────

    public function testAk21DreiListenErgebenDreiHaekchenMitIhrenNamenUnuebersetzt(): void
    {
        // „board.title_required“ sieht aus wie ein Übersetzungsschlüssel — ein Listenname darf
        // trotzdem nie durch den Übersetzer laufen.
        $listen = [self::liste(12, 'Kirchberg'), self::liste(14, 'Mit Oma'), self::liste(15, 'board.title_required')];

        $view = $this->factory
            ->create(RestaurantListsType::class, ['lists' => [14], 'newList' => ''], ['lists' => $listen])
            ->createView();

        self::assertSame('saved.panel.lists_heading', $view['lists']->vars['label']);
        self::assertFalse($view['lists']->vars['choice_translation_domain']);

        $haekchen = array_values($view['lists']->children);
        self::assertCount(3, $haekchen);
        self::assertSame(['Kirchberg', 'Mit Oma', 'board.title_required'], array_map(static fn ($h) => $h->vars['label'], $haekchen));
        self::assertSame(['12', '14', '15'], array_map(static fn ($h) => $h->vars['value'], $haekchen));
        self::assertSame([false, true, false], array_map(static fn ($h) => $h->vars['checked'], $haekchen), 'AK-21: das eine gesetzt.');
        foreach ($haekchen as $h) {
            self::assertFalse($h->vars['translation_domain'], 'Ein Listenname darf nicht übersetzt werden.');
        }

        self::assertArrayHasKey('newList', $view->children);
    }

    public function testAk24OhneListenKeineHaekchenAberFeldNeueListe(): void
    {
        $view = $this->factory->create(RestaurantListsType::class, null, ['lists' => []])->createView();

        self::assertCount(0, $view['lists']->children);
        self::assertSame('saved.panel.new_label', $view['newList']->vars['label']);
        self::assertSame('saved.panel.new_help', $view['newList']->vars['help']);
        self::assertFalse($view['newList']->vars['required']);
    }

    public function testAk22HaekchenKommenAlsListennummernZurueck(): void
    {
        $form = $this->factory->create(RestaurantListsType::class, null, [
            'lists' => [self::liste(12, 'Kirchberg'), self::liste(14, 'Mit Oma')],
        ]);
        $form->submit(['lists' => ['12', '14'], 'newList' => '']);

        self::assertTrue($form->isValid());
        self::assertSame(['lists' => [12, 14], 'newList' => ''], $form->getData());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function ohneNeueListe(): iterable
    {
        yield 'nichts abgeschickt' => [[]];
        yield 'newList null' => [['newList' => null]];
        yield 'newList leer' => [['newList' => '']];
    }

    /**
     * @param array<string, mixed> $absenden
     */
    #[DataProvider('ohneNeueListe')]
    public function testAk23NeueListeDarfLeerBleibenUndWirdNieNull(array $absenden): void
    {
        $form = $this->factory->create(RestaurantListsType::class, null, ['lists' => [self::liste(12, 'Kirchberg')]]);
        $form->submit($absenden);

        self::assertTrue($form->isValid());
        self::assertSame('', $form->getData()['newList'] ?? 'null statt leerem Text');
        self::assertSame([], $form->getData()['lists']);
    }

    public function testAk23NeueListeMitEinundsechzigZeichenMeldetAmFeld(): void
    {
        $form = $this->factory->create(RestaurantListsType::class, null, ['lists' => []]);
        $form->submit(['newList' => str_repeat('a', 61)]);

        self::assertFalse($form->isValid());
        self::assertSame(['saved.name_too_long'], self::fehlerAm($form->get('newList')));
        self::assertSame([], self::fehlerAm($form));
    }

    public function testAk23NeueListeZaehltLeerzeichenAmRandNicht(): void
    {
        $form = $this->factory->create(RestaurantListsType::class, null, ['lists' => []]);
        $form->submit(['newList' => '  '.str_repeat('a', 58).' ']);

        self::assertTrue($form->isValid());
    }

    /**
     * Eine fremde Nummer fängt der Controller vorher ab (404). Kommt sie trotzdem hier an, ist
     * sie ein gewöhnlicher Formularfehler am Feld — keine Ausnahme, kein Fehler an der Wurzel
     * (`error_bubbling` ist bei einem aufgeklappten ChoiceType sonst `true`).
     */
    public function testUnbekannteListennummerIstFormularfehlerAmFeld(): void
    {
        $form = $this->factory->create(RestaurantListsType::class, null, ['lists' => [self::liste(12, 'Kirchberg')]]);
        $form->submit(['lists' => ['999'], 'newList' => '']);

        self::assertFalse($form->isValid());
        self::assertNotSame([], self::fehlerAm($form->get('lists')));
        self::assertSame([], self::fehlerAm($form));
    }

    public function testListenSindPflichtoption(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->factory->create(RestaurantListsType::class);
    }

    // ── RestaurantNoteType ──────────────────────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string}>
     */
    public static function gueltigeLangeNotizen(): iterable
    {
        // 167 Zeilen „ab“: mit `\r\n` 666 Zeichen, mit `\n` genau 500. (Den Umbruch vereinheitlicht
        // schon Symfonys TextareaType; der Fall hält fest, dass es dabei bleibt.)
        yield '500 mit \r\n-Umbrüchen' => [implode("\r\n", array_fill(0, 167, 'ab'))];
        // Das leistet nur der Normalisierer: zerlegt 1000 Codepunkte, gespeichert 500.
        yield '500 é aus je zwei Codepunkten' => [str_repeat("e\u{0301}", 500)];
    }

    #[DataProvider('gueltigeLangeNotizen')]
    public function testAk35FuenfhundertZeichenNachNormalisierungSindGueltig(string $eingabe): void
    {
        self::assertGreaterThan(500, mb_strlen($eingabe));
        self::assertSame(500, SavedText::length(SavedText::note($eingabe)));

        $form = $this->factory->create(RestaurantNoteType::class, null, ['restaurant_name' => 'Brasserie du Grund']);
        $form->submit(['text' => $eingabe]);

        self::assertSame([], self::fehlerAm($form->get('text')));
        self::assertTrue($form->isValid());
    }

    public function testAk35FuenfhunderteinsZeichenMeldenZuLangUndDerTextBleibt(): void
    {
        $eingabe = str_repeat('x', 501);

        $form = $this->factory->create(RestaurantNoteType::class, null, ['restaurant_name' => 'Brasserie du Grund']);
        $form->submit(['text' => $eingabe]);

        self::assertFalse($form->isValid());
        self::assertSame(['saved.note_too_long'], self::fehlerAm($form->get('text')));
        self::assertSame([], self::fehlerAm($form));
        self::assertSame($eingabe, $form->createView()['text']->vars['value'], 'AK-35: Der Text steht weiter im Feld.');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function leereNotizen(): iterable
    {
        yield 'Feld fehlt' => [[]];
        yield 'Feld null' => [['text' => null]];
        yield 'leer' => [['text' => '']];
    }

    /**
     * AK-36: Eine leere Notiz ist kein Fehler, sondern das Löschen.
     *
     * @param array<string, mixed> $absenden
     */
    #[DataProvider('leereNotizen')]
    public function testAk36LeereNotizIstGueltigUndWirdNieNull(array $absenden): void
    {
        $form = $this->factory->create(RestaurantNoteType::class, null, ['restaurant_name' => 'Brasserie du Grund']);
        $form->submit($absenden);

        self::assertTrue($form->isValid());
        self::assertSame('', $form->getData()['text'] ?? 'null statt leerem Text');
    }

    public function testEc08NotizNurAusLeerraumIstGueltig(): void
    {
        $form = $this->factory->create(RestaurantNoteType::class, null, ['restaurant_name' => 'Brasserie du Grund']);
        $form->submit(['text' => str_repeat(" \r\n", 300)]);

        self::assertTrue($form->isValid());
    }

    public function testAk32NotizfeldTraegtDenNamenDesLokalsUndDenHinweis(): void
    {
        $feld = $this->factory
            ->create(RestaurantNoteType::class, null, ['restaurant_name' => 'Brasserie du Grund'])
            ->createView()['text'];

        self::assertSame('saved.panel.note_label', $feld->vars['label']);
        self::assertSame(['%name%' => 'Brasserie du Grund'], $feld->vars['label_translation_parameters']);
        self::assertSame('saved.panel.note_help', $feld->vars['help']);
        self::assertFalse($feld->vars['required']);
    }

    public function testNameDesLokalsIstPflichtoption(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->factory->create(RestaurantNoteType::class);
    }

    // ── alle drei ───────────────────────────────────────────────────────────────────────────

    /**
     * @return iterable<string, array{class-string, array<string, mixed>, string}>
     */
    public static function textfelder(): iterable
    {
        yield 'Listenname' => [SavedListNameType::class, [], 'name'];
        yield 'neue Liste auf der Detailseite' => [RestaurantListsType::class, ['lists' => []], 'newList'];
        yield 'Notiz' => [RestaurantNoteType::class, ['restaurant_name' => 'X'], 'text'];
    }

    /**
     * Kein `maxlength`: Der Browser schnitte eingefügten Text still ab — die Nutzerin sähe weder
     * die Meldung noch, dass etwas fehlt (AK-18, AK-35).
     *
     * @param class-string         $typ
     * @param array<string, mixed> $optionen
     */
    #[DataProvider('textfelder')]
    public function testAk18Ak35KeinMaxlengthAmFeld(string $typ, array $optionen, string $feld): void
    {
        $view = $this->factory->create($typ, null, $optionen)->createView()[$feld];

        self::assertArrayNotHasKey('maxlength', $view->vars['attr']);
    }

    // ── Hilfen ──────────────────────────────────────────────────────────────────────────────

    /**
     * Meldungsschlüssel der Fehler, die **direkt** an diesem Formular(feld) hängen.
     *
     * @return list<string>
     */
    private static function fehlerAm(FormInterface $form): array
    {
        $schluessel = [];
        foreach ($form->getErrors() as $fehler) {
            $schluessel[] = $fehler->getMessageTemplate();
        }

        return $schluessel;
    }

    private static function liste(int $id, string $name): SavedList
    {
        $liste = new SavedList(new User(), SavedText::name($name), SavedText::nameKey($name));
        (new \ReflectionProperty(SavedList::class, 'id'))->setValue($liste, $id);

        return $liste;
    }
}
