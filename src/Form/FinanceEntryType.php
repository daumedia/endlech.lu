<?php

namespace App\Form;

use App\Entity\FinanceEntry;
use App\Enum\FinanceCategory;
use App\Enum\FinanceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Eingabemaske für einen Posten der öffentlichen Finanzübersicht.
 *
 * Es gibt kein Feld für die Richtung (Einnahme/Ausgabe): Sie hängt an der
 * Kategorie und wird von FinanceEntry::setCategory() gesetzt. Zwei Felder für
 * dieselbe Aussage wären eine Gelegenheit, sie widersprüchlich zu füllen.
 */
class FinanceEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'admin.finance.field.date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new NotNull(message: 'finance.date_required')],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'admin.finance.field.category',
                'choices' => $this->groupedChoices(),
                'choice_label' => static fn (FinanceCategory $category) => $category->transKey(),
                'choice_value' => static fn (?FinanceCategory $category) => $category?->value,
                'placeholder' => false,
                'constraints' => [new NotNull(message: 'finance.category_required')],
                'help' => 'admin.finance.field.category_help',
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'admin.finance.field.amount',
                'currency' => 'EUR',
                'scale' => 2,
                // Immer positiv: Die Richtung steckt in der Kategorie. Ein
                // negativer Betrag würde die Summe doppelt invertieren.
                'constraints' => [
                    new NotBlank(message: 'finance.amount_required'),
                    new GreaterThan(value: 0, message: 'finance.amount_positive'),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'admin.finance.field.quantity',
                'required' => false,
                'help' => 'admin.finance.field.quantity_help',
                'attr' => ['min' => 1],
                'constraints' => [new Positive(message: 'finance.quantity_positive')],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'admin.finance.field.note',
                'required' => false,
                'help' => 'admin.finance.field.note_help',
                'attr' => ['rows' => 3],
                'constraints' => [new Length(max: 500, maxMessage: 'finance.note_max')],
            ]);
    }

    /**
     * Zwei Optionsgruppen statt einer flachen Liste – bei elf Kategorien ist
     * sonst nicht auf einen Blick erkennbar, ob ein Eintrag die Ausgaben- oder
     * die Einnahmenseite trifft.
     *
     * @return array<string, list<FinanceCategory>>
     */
    private function groupedChoices(): array
    {
        $choices = [];

        foreach (FinanceType::cases() as $type) {
            $choices[$type->transKey()] = FinanceCategory::casesFor($type);
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FinanceEntry::class,
            // Bewusst ein Fehler statt stillem Verwerfen: Wer eine Stückzahl
            // einträgt, meint sie auch. Sie kommentarlos zu löschen, weil die
            // Kategorie nicht passt, wäre für den Admin nicht nachvollziehbar.
            'constraints' => [
                new Callback(static function (?FinanceEntry $entry, ExecutionContextInterface $context): void {
                    if (null === $entry || null === $entry->getQuantity()) {
                        return;
                    }

                    if (!$entry->getCategory()->tracksQuantity()) {
                        $context->buildViolation('finance.quantity_not_allowed')
                            ->atPath('quantity')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }
}
