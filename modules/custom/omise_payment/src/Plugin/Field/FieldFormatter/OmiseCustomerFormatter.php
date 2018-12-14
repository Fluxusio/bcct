<?php

namespace Drupal\omise_payment\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'omise_customer_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "omise_customer_formatter",
 *   label = @Translation("Omise customer formatter"),
 *   field_types = {
 *     "omise_customer_type"
 *   }
 * )
 */
class OmiseCustomerFormatter extends EntityReferenceLabelFormatter
{

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = parent::viewElements($items, $langcode);
        $values = $items->getValue();

        foreach ($elements as $delta => $entity) {
            $elements[$delta]['#suffix'] = ' (Default card: ' . $values[$delta]['omise_default_card'] . ')';
        }

        return $elements;
    }
}